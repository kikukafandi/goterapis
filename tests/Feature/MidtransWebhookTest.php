<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Jobs\RefundLatePayment;
use App\Models\Order;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MidtransWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $serverKey = 'SB-Mid-server-test';

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        config(['services.midtrans.server_key' => $this->serverKey]);
    }

    private function pendingOrder(): Order
    {
        $user = User::factory()->create(['role' => 'user']);
        $therapistUser = User::factory()->create(['role' => 'therapist']);
        $profile = TherapistProfile::create([
            'user_id' => $therapistUser->id, 'verification_status' => 'anggota',
            'serves_call' => true, 'city' => 'Yogyakarta', 'is_available' => true,
        ]);
        $service = Service::create(['name' => 'Pijat', 'slug' => 'pijat-'.uniqid(), 'category' => 'pijat']);

        $order = Order::create([
            'code' => 'GT-'.strtoupper(uniqid()),
            'user_id' => $user->id, 'therapist_profile_id' => $profile->id, 'service_id' => $service->id,
            'model' => 'panggilan', 'scheduled_at' => now()->addDay(), 'duration_min' => 60,
            'price' => 100_000, 'transport_fee' => 15_000, 'service_fee' => 3_000,
            'total' => 118_000, 'commission' => 15_000, 'payout' => 100_000,
            'status' => 'pending_payment',
        ]);
        $order->payment()->create(['gateway' => 'midtrans', 'gateway_ref' => $order->code, 'amount' => $order->total, 'status' => 'pending']);

        return $order;
    }

    private function payload(Order $order, string $transaction, string $statusCode = '200'): array
    {
        $gross = '118000.00';
        $signature = hash('sha512', $order->code.$statusCode.$gross.$this->serverKey);

        return [
            'order_id' => $order->code,
            'status_code' => $statusCode,
            'gross_amount' => $gross,
            'signature_key' => $signature,
            'transaction_status' => $transaction,
            'payment_type' => 'qris',
        ];
    }

    public function test_notifikasi_settlement_menandai_pesanan_lunas(): void
    {
        $order = $this->pendingOrder();

        $this->postJson(route('midtrans.webhook'), $this->payload($order, 'settlement'))->assertOk();

        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertSame('paid', $order->payment->status);
        $this->assertSame(118_000, $order->payment->amount);
        $this->assertNotNull($order->payment->paid_at);
    }

    public function test_nominal_yang_tidak_sesuai_ditolak(): void
    {
        $order = $this->pendingOrder();
        $payload = $this->payload($order, 'settlement');
        $payload['gross_amount'] = '1.00';
        $payload['signature_key'] = hash('sha512', $order->code.$payload['status_code'].$payload['gross_amount'].$this->serverKey);

        $this->postJson(route('midtrans.webhook'), $payload)->assertStatus(422);

        $this->assertSame('pending_payment', $order->fresh()->status);
        $this->assertSame('pending', $order->payment->fresh()->status);
    }

    public function test_signature_salah_ditolak(): void
    {
        $order = $this->pendingOrder();
        $payload = $this->payload($order, 'settlement');
        $payload['signature_key'] = 'palsu';

        $this->postJson(route('midtrans.webhook'), $payload)->assertStatus(403);
        $this->assertSame('pending_payment', $order->fresh()->status);
    }

    public function test_notifikasi_ditolak_saat_server_key_belum_dikonfigurasi(): void
    {
        config(['services.midtrans.server_key' => null]);
        $order = $this->pendingOrder();

        $this->postJson(route('midtrans.webhook'), $this->payload($order, 'settlement'))->assertStatus(503);

        $this->assertSame('pending_payment', $order->fresh()->status);
        $this->assertSame('pending', $order->payment->fresh()->status);
    }

    public function test_notifikasi_expire_tidak_meluluskan(): void
    {
        $order = $this->pendingOrder();

        $this->postJson(route('midtrans.webhook'), $this->payload($order, 'expire', '407'))->assertOk();

        $order->refresh();
        $this->assertSame('pending_payment', $order->status);
        $this->assertSame('expired', $order->payment->status);
    }

    public function test_notifikasi_lama_tidak_menurunkan_pembayaran_lunas(): void
    {
        $order = $this->pendingOrder();

        $this->postJson(route('midtrans.webhook'), $this->payload($order, 'settlement'))->assertOk();
        $this->postJson(route('midtrans.webhook'), $this->payload($order, 'expire', '407'))->assertOk();

        $this->assertSame('paid', $order->fresh()->payment->status);
        $this->assertSame(1, $order->payment()->count());
    }

    public function test_settlement_setelah_batas_waktu_mengantre_pengembalian_dana(): void
    {
        Queue::fake();
        $order = $this->pendingOrder();
        $order->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancel_reason' => 'Pembayaran tidak diselesaikan sampai batas waktu.',
        ]);

        $this->postJson(route('midtrans.webhook'), $this->payload($order, 'settlement'))->assertOk();

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('paid', $order->payment->status);
        Queue::assertPushed(RefundLatePayment::class, fn (RefundLatePayment $job) => $job->orderId === $order->id && $job->amount === 118_000);
        Http::assertNothingSent();
    }

    public function test_job_pengembalian_dana_terlambat_dapat_dicoba_ulang(): void
    {
        Http::fakeSequence()
            ->push(['status_code' => '500'], 500)
            ->push(['status_code' => '200']);
        $order = $this->pendingOrder();
        $order->update(['status' => 'cancelled']);
        $order->payment()->update([
            'gateway' => 'midtrans',
            'gateway_ref' => $order->code,
            'amount' => 118_000,
            'status' => 'paid',
            'paid_at' => now(),
            'refund_amount' => 118_000,
            'refund_requested_at' => now(),
        ]);
        $job = new RefundLatePayment($order->id, 118_000);
        $gateway = app(PaymentGateway::class);

        try {
            $job->handle($gateway);
            $this->fail('Refund pertama seharusnya gagal.');
        } catch (\Throwable) {
            $this->assertSame('paid', $order->payment->fresh()->status);
        }

        $job->handle($gateway);

        $this->assertSame('refunded', $order->payment->fresh()->status);
        Http::assertSentCount(2);
    }

    public function test_job_tidak_mengulang_pengembalian_dana_yang_sudah_selesai(): void
    {
        Http::fake();
        $order = $this->pendingOrder();
        $order->payment()->update([
            'gateway' => 'midtrans',
            'gateway_ref' => $order->code,
            'amount' => 118_000,
            'status' => 'refunded',
        ]);

        (new RefundLatePayment($order->id, 118_000))->handle(app(PaymentGateway::class));

        Http::assertNothingSent();
    }
}
