<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MidtransWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $serverKey = 'SB-Mid-server-test';

    protected function setUp(): void
    {
        parent::setUp();
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

        return Order::create([
            'code' => 'GT-'.strtoupper(uniqid()),
            'user_id' => $user->id, 'therapist_profile_id' => $profile->id, 'service_id' => $service->id,
            'model' => 'panggilan', 'scheduled_at' => now()->addDay(), 'duration_min' => 60,
            'price' => 100_000, 'transport_fee' => 15_000, 'service_fee' => 3_000,
            'total' => 118_000, 'commission' => 15_000, 'payout' => 100_000,
            'status' => 'pending_payment',
        ]);
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

    public function test_signature_salah_ditolak(): void
    {
        $order = $this->pendingOrder();
        $payload = $this->payload($order, 'settlement');
        $payload['signature_key'] = 'palsu';

        $this->postJson(route('midtrans.webhook'), $payload)->assertStatus(403);
        $this->assertSame('pending_payment', $order->fresh()->status);
    }

    public function test_notifikasi_expire_tidak_meluluskan(): void
    {
        $order = $this->pendingOrder();

        $this->postJson(route('midtrans.webhook'), $this->payload($order, 'expire', '407'))->assertOk();

        $order->refresh();
        $this->assertSame('pending_payment', $order->status);
        $this->assertSame('failed', $order->payment->status);
    }
}
