<?php

namespace Tests\Feature;

use App\Events\TherapistLocationUpdated;
use App\Http\Controllers\TherapistLocationController;
use App\Models\Order;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TherapistLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_terapis_pemilik_dapat_mengirim_lokasi_otw_tanpa_membroadcast_koordinat(): void
    {
        Event::fake([TherapistLocationUpdated::class]);
        [$customer, $therapist, $order] = $this->order();

        $this->actingAs($therapist)->putJson(route('mitra.pesanan.location', $order), [
            'lat' => -7.7960, 'lng' => 110.3695, 'accuracy' => 18,
        ])->assertOk()->assertJsonStructure(['updated_at']);

        $cached = Cache::get(TherapistLocationController::cacheKey($order));
        $this->assertSame(-7.796, $cached['lat']);
        Event::assertDispatched(TherapistLocationUpdated::class, function (TherapistLocationUpdated $event): bool {
            $this->assertSame(['distance_m', 'accuracy', 'updated_at'], array_keys($event->broadcastWith()));

            return true;
        });
        $this->actingAs($customer)->get(route('pesanan.show', $order))->assertOk()->assertSee('Perjalanan terapis');
    }

    public function test_lokasi_ditolak_untuk_bukan_pemilik_status_salah_dan_data_tidak_wajar(): void
    {
        [, $therapist, $order] = $this->order();
        $other = User::factory()->create(['role' => 'therapist']);

        $this->actingAs($other)->putJson(route('mitra.pesanan.location', $order), $this->location())->assertNotFound();
        $order->update(['status' => 'paid']);
        $this->actingAs($therapist)->putJson(route('mitra.pesanan.location', $order), $this->location())->assertNotFound();
        $order->update(['status' => 'therapist_en_route']);
        $this->actingAs($therapist)->putJson(route('mitra.pesanan.location', $order), ['lat' => 91, 'lng' => 181, 'accuracy' => 1001])->assertStatus(422);
    }

    public function test_endpoint_dibatasi_dua_belas_kali_per_menit_per_terapis_dan_pesanan(): void
    {
        [, $therapist, $order] = $this->order();

        foreach (range(1, 12) as $attempt) {
            $this->actingAs($therapist)->putJson(route('mitra.pesanan.location', $order), $this->location())->assertOk();
        }

        $this->actingAs($therapist)->putJson(route('mitra.pesanan.location', $order), $this->location())->assertTooManyRequests();
    }

    public function test_lokasi_cache_dihapus_saat_terapis_tiba(): void
    {
        [, $therapist, $order] = $this->order();
        Cache::put(TherapistLocationController::cacheKey($order), $this->location(), now()->addMinutes(2));

        $this->actingAs($therapist)->patch(route('mitra.pesanan.arrive', $order))->assertRedirect();

        $this->assertFalse(Cache::has(TherapistLocationController::cacheKey($order)));
    }

    private function location(): array
    {
        return ['lat' => -7.7960, 'lng' => 110.3695, 'accuracy' => 18];
    }

    private function order(): array
    {
        $customer = User::factory()->create(['role' => 'user']);
        $therapist = User::factory()->create(['role' => 'therapist']);
        $profile = TherapistProfile::create(['user_id' => $therapist->id, 'verification_status' => 'anggota', 'serves_call' => true, 'city' => 'Yogyakarta', 'is_available' => true]);
        $service = Service::create(['name' => 'Pijat', 'slug' => fake()->unique()->slug(), 'category' => 'pijat']);
        $order = Order::create(['code' => fake()->unique()->bothify('GT-########'), 'user_id' => $customer->id, 'therapist_profile_id' => $profile->id, 'service_id' => $service->id, 'model' => 'panggilan', 'scheduled_at' => now()->addDay(), 'duration_min' => 60, 'address' => 'Malioboro', 'lat' => -7.7956, 'lng' => 110.3695, 'loc_accuracy' => 10, 'price' => 100000, 'transport_fee' => 0, 'service_fee' => 3000, 'total' => 103000, 'commission' => 15000, 'payout' => 85000, 'status' => 'therapist_en_route', 'start_pin' => '123456']);

        return [$customer, $therapist, $order];
    }
}
