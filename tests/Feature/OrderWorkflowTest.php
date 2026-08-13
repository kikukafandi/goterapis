<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_terapis_pemilik_dapat_menandai_layanan_panggilan_sudah_otw(): void
    {
        Notification::fake();
        [$customer, $therapist, $order] = $this->order();

        $this->actingAs($therapist)->patch(route('mitra.pesanan.en-route', $order))->assertRedirect();

        $this->assertSame('therapist_en_route', $order->fresh()->status);
        Notification::assertSentTo($customer, OrderStatusChanged::class);
        Notification::assertNotSentTo($therapist, OrderStatusChanged::class);
    }

    public function test_terapis_pemilik_dapat_menandai_sudah_tiba_dan_pelanggan_diberi_notifikasi(): void
    {
        Notification::fake();
        [$customer, $therapist, $order] = $this->order();
        $order->update(['status' => 'therapist_en_route']);

        $this->actingAs($therapist)->patch(route('mitra.pesanan.arrive', $order))->assertRedirect();

        $this->assertSame('therapist_arrived', $order->fresh()->status);
        Notification::assertSentTo($customer, OrderStatusChanged::class);
        Notification::assertNotSentTo($therapist, OrderStatusChanged::class);
    }

    public function test_terapis_lain_tidak_dapat_menandai_sudah_tiba(): void
    {
        [, , $order] = $this->order();
        $order->update(['status' => 'therapist_en_route']);
        $otherTherapist = User::factory()->create(['role' => 'therapist']);

        $this->actingAs($otherTherapist)->patch(route('mitra.pesanan.arrive', $order))
            ->assertRedirect(route('mitra.pesanan'));

        $this->assertSame('therapist_en_route', $order->fresh()->status);
    }

    public function test_status_sama_tidak_mengirim_notifikasi(): void
    {
        Notification::fake();
        [, , $order] = $this->order();

        $this->assertFalse($order->changeStatus('paid', 'Pembayaran berhasil.'));
        Notification::assertNothingSent();
    }

    public function test_tutorial_menandai_pengguna_sudah_melihat(): void
    {
        $user = User::factory()->create(['role' => 'user', 'tutorial_seen_at' => null]);

        $this->actingAs($user)->get(route('tutorial'))->assertOk()->assertSee('Pesan');

        $this->assertNotNull($user->fresh()->tutorial_seen_at);
    }

    private function order(): array
    {
        $customer = User::factory()->create(['role' => 'user']);
        $therapist = User::factory()->create(['role' => 'therapist']);
        $profile = TherapistProfile::create(['user_id' => $therapist->id, 'verification_status' => 'identitas', 'serves_call' => true, 'city' => 'Yogyakarta', 'is_available' => true]);
        $service = Service::create(['name' => 'Pijat', 'slug' => 'pijat-workflow', 'category' => 'pijat']);
        $order = Order::create(['code' => 'GT-WORKFLOW', 'user_id' => $customer->id, 'therapist_profile_id' => $profile->id, 'service_id' => $service->id, 'model' => 'panggilan', 'scheduled_at' => now()->addDay(), 'duration_min' => 60, 'price' => 100000, 'transport_fee' => 0, 'service_fee' => 3000, 'total' => 103000, 'commission' => 15000, 'payout' => 85000, 'status' => 'paid', 'start_pin' => '123456']);

        return [$customer, $therapist, $order];
    }
}
