<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TherapistLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_completion_credits_payout_once_and_releases_it_after_24_hours(): void
    {
        $completedAt = now()->startOfSecond();
        [$customer, $order] = $this->order();

        $this->actingAs($customer)->patch(route('pesanan.complete', $order))->assertRedirect();

        $earning = $order->earning()->firstOrFail();
        $this->assertSame(85000, $earning->amount);
        $this->assertTrue($earning->available_at->equalTo($completedAt->copy()->addDay()));
        $this->assertDatabaseCount('earnings', 1);
        $this->assertStringContainsString('Rp0', $this->actingAs($order->therapistProfile->user)->get(route('mitra.saldo'))->getContent());

        $order->changeStatus('completed', 'Selesai.', ['completed_at' => now()]);
        $this->assertDatabaseCount('earnings', 1);

        $this->travelTo($completedAt->copy()->addDay());
        $this->actingAs($order->therapistProfile->user)->get(route('mitra.saldo'))->assertOk()->assertSee('Rp85.000');
    }

    public function test_scheduler_menyelesaikan_order_setelah_durasi_dan_grace_secara_idempoten(): void
    {
        [, $order] = $this->order();
        $order->update(['started_at' => now()->subHours(4)]);

        $this->assertSame(1, Order::completeFinished());
        $this->assertSame(0, Order::completeFinished());
        $this->assertSame('completed', $order->fresh()->status);
        $this->assertDatabaseCount('earnings', 1);
    }

    public function test_scheduler_tidak_menyelesaikan_order_sebelum_grace_berakhir(): void
    {
        [, $order] = $this->order();
        $order->update(['started_at' => now()->subHours(2)]);

        $this->assertSame(0, Order::completeFinished());
        $this->assertSame('in_progress', $order->fresh()->status);
    }

    private function order(): array
    {
        $customer = User::factory()->create(['role' => 'user']);
        $therapist = User::factory()->create(['role' => 'therapist']);
        $profile = TherapistProfile::create(['user_id' => $therapist->id, 'verification_status' => 'anggota', 'serves_call' => true, 'city' => 'Yogyakarta']);
        $service = Service::create(['name' => 'Pijat', 'slug' => 'pijat-ledger', 'category' => 'pijat']);
        $order = Order::create(['code' => 'GT-LEDGER', 'user_id' => $customer->id, 'therapist_profile_id' => $profile->id, 'service_id' => $service->id, 'model' => 'panggilan', 'scheduled_at' => now()->addDay(), 'duration_min' => 60, 'price' => 100000, 'total' => 103000, 'commission' => 15000, 'payout' => 85000, 'status' => 'in_progress']);

        return [$customer, $order];
    }
}
