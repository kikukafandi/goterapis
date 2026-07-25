<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Review;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private function completedOrder(User $user, string $status = 'completed'): Order
    {
        $therapistUser = User::factory()->create(['role' => 'therapist']);
        $profile = TherapistProfile::create([
            'user_id' => $therapistUser->id,
            'verification_status' => 'anggota',
            'serves_call' => true,
            'city' => 'Yogyakarta',
            'is_available' => true,
        ]);
        $service = Service::create(['name' => 'Pijat', 'slug' => 'pijat-'.uniqid(), 'category' => 'pijat']);

        return Order::create([
            'code' => 'GT-'.strtoupper(uniqid()),
            'user_id' => $user->id,
            'therapist_profile_id' => $profile->id,
            'service_id' => $service->id,
            'model' => 'panggilan',
            'scheduled_at' => now()->subHour(),
            'duration_min' => 60,
            'price' => 100_000, 'transport_fee' => 15_000, 'service_fee' => 3_000,
            'total' => 118_000, 'commission' => 15_000, 'payout' => 100_000,
            'status' => $status,
        ]);
    }

    public function test_pelanggan_memberi_ulasan_dan_rating_terapis_diperbarui(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->completedOrder($user);

        $this->actingAs($user)
            ->post(route('pesanan.review', $order), ['rating' => 5, 'body' => 'Mantap!'])
            ->assertRedirect();

        $review = Review::where('order_id', $order->id)->first();
        $this->assertNotNull($review);
        $this->assertSame(5, $review->rating_service);
        $this->assertSame('Mantap!', $review->body);

        $profile = $order->therapistProfile->fresh();
        $this->assertEqualsWithDelta(5.0, $profile->rating_avg, 0.001);
        $this->assertSame(1, $profile->reviews_count);
    }

    public function test_tidak_bisa_ulas_pesanan_belum_selesai(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->completedOrder($user, 'in_progress');

        $this->actingAs($user)
            ->post(route('pesanan.review', $order), ['rating' => 5])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, Review::count());
    }

    public function test_tidak_bisa_ulas_dua_kali(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->completedOrder($user);
        $this->actingAs($user)->post(route('pesanan.review', $order), ['rating' => 4]);

        $this->actingAs($user)
            ->post(route('pesanan.review', $order), ['rating' => 1])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(1, Review::count());
    }

    public function test_tidak_bisa_ulas_pesanan_orang_lain(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $order = $this->completedOrder($owner);
        $intruder = User::factory()->create(['role' => 'user']);

        $this->actingAs($intruder)
            ->post(route('pesanan.review', $order), ['rating' => 5])
            ->assertRedirect(route('pesanan.index'));

        $this->assertSame(0, Review::count());
    }
}
