<?php

namespace Tests\Feature;

use App\Jobs\RefundLatePayment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminTransactionTest extends TestCase
{
    use RefreshDatabase;

    private function failedRefund(): Payment
    {
        $customer = User::factory()->create(['role' => 'user']);
        $therapist = TherapistProfile::create([
            'user_id' => User::factory()->create(['role' => 'therapist'])->id,
            'verification_status' => 'anggota', 'serves_call' => true, 'city' => 'Yogyakarta', 'is_available' => true,
        ]);
        $service = Service::create(['name' => 'Pijat', 'slug' => 'pijat-'.uniqid(), 'category' => 'pijat']);
        $order = Order::create([
            'code' => 'GT-'.strtoupper(uniqid()), 'user_id' => $customer->id, 'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id, 'model' => 'panggilan', 'scheduled_at' => now()->addDay(), 'duration_min' => 60,
            'price' => 100000, 'transport_fee' => 15000, 'service_fee' => 3000, 'total' => 118000,
            'commission' => 15000, 'payout' => 100000, 'status' => 'cancelled',
        ]);

        return $order->payment()->create([
            'gateway' => 'midtrans', 'gateway_ref' => $order->code, 'amount' => 118000, 'status' => 'paid',
            'paid_at' => now(), 'refund_amount' => 118000, 'refund_requested_at' => now(),
            'refund_failed_at' => now(), 'refund_error' => 'Gateway tidak tersedia.', 'refund_attempts' => 1,
        ]);
    }

    public function test_hanya_admin_dapat_melihat_transaksi(): void
    {
        $payment = $this->failedRefund();

        $this->actingAs(User::factory()->create(['role' => 'user']))->get(route('admin.transactions.index'))->assertRedirect(route('home'));
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.transactions.index'))->assertOk()->assertSee($payment->order->code);
    }

    public function test_admin_dapat_melihat_detail_dan_mengantrekan_retry_refund_gagal(): void
    {
        Queue::fake();
        $payment = $this->failedRefund();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.transactions.show', $payment))
            ->assertOk()->assertSee('Gateway tidak tersedia.')->assertSee('Coba ulang refund');
        $this->actingAs($admin)->post(route('admin.transactions.retry-refund', $payment))->assertRedirect();

        Queue::assertPushed(RefundLatePayment::class, fn (RefundLatePayment $job) => $job->orderId === $payment->order_id);
        $this->assertNull($payment->fresh()->refund_failed_at);
    }

    public function test_refund_yang_tidak_gagal_tidak_dapat_dicoba_ulang(): void
    {
        Queue::fake();
        $payment = $this->failedRefund();
        $payment->update(['refund_failed_at' => null, 'refund_error' => null]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.transactions.retry-refund', $payment))->assertStatus(422);
        Queue::assertNothingPushed();
    }
}
