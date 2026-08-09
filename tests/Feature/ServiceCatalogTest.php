<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_katalog_layanan_direkonsiliasi_tanpa_menghapus_layanan_lama(): void
    {
        $profile = TherapistProfile::create([
            'user_id' => User::factory()->create(['role' => 'therapist'])->id,
            'gender' => 'pria',
            'city' => 'Yogyakarta',
        ]);
        $legacy = Service::create(['name' => 'Bekam Kering', 'slug' => 'bekam-kering', 'category' => 'bekam']);
        $refleksi = Service::create(['name' => 'Refleksi Kaki', 'slug' => 'refleksi-kaki', 'category' => 'refleksi']);
        $profile->services()->attach([$legacy->id, $refleksi->id], ['price' => 100000, 'duration_min' => 60]);

        $this->seed(ServiceSeeder::class);
        $this->seed(ServiceSeeder::class);

        $this->assertFalse($legacy->fresh()->is_active);
        $this->assertFalse($refleksi->fresh()->is_active);
        $this->assertTrue(Service::where('slug', 'bekam')->where('is_active', true)->exists());
        $this->assertTrue(Service::where('slug', 'totok')->where('is_active', true)->exists());
        $this->assertSame(1, Service::where('slug', 'spot-massage')->count());
        $this->assertSame('wanita', Service::where('slug', 'spa-massage')->value('allowed_gender'));
        $this->assertDatabaseMissing('therapist_service', ['service_id' => $legacy->id]);
        $this->assertDatabaseMissing('therapist_service', ['service_id' => $refleksi->id]);
    }

    public function test_spa_massage_hanya_tersedia_untuk_terapis_wanita(): void
    {
        $this->seed(ServiceSeeder::class);
        $spa = Service::where('slug', 'spa-massage')->firstOrFail();
        $spot = Service::where('slug', 'spot-massage')->firstOrFail();

        $this->assertFalse($spa->isAvailableTo('pria'));
        $this->assertTrue($spa->isAvailableTo('wanita'));
        $this->assertTrue($spot->isAvailableTo('pria'));
        $this->assertTrue($spot->isAvailableTo('wanita'));
    }

    public function test_layanan_nonaktif_tidak_dapat_dipesan(): void
    {
        $this->assertServiceCannotBeOrdered([
            'name' => 'Layanan Lama', 'slug' => 'layanan-lama', 'category' => 'pijat', 'is_active' => false,
        ]);
    }

    public function test_layanan_tidak_sesuai_gender_tidak_dapat_dipesan(): void
    {
        $this->assertServiceCannotBeOrdered([
            'name' => 'Spa Massage', 'slug' => 'spa-massage-test', 'category' => 'pijat', 'allowed_gender' => 'wanita',
        ]);
    }

    private function assertServiceCannotBeOrdered(array $serviceData): void
    {
        $customer = User::factory()->create(['role' => 'user']);
        $therapist = TherapistProfile::create([
            'user_id' => User::factory()->create(['role' => 'therapist'])->id,
            'gender' => 'pria',
            'city' => 'Yogyakarta',
            'verification_status' => 'identitas',
            'serves_call' => true,
            'is_available' => true,
        ]);
        $service = Service::create($serviceData);
        $therapist->services()->attach($service->id, ['price' => 100000, 'duration_min' => 60]);

        $this->actingAs($customer)->post(route('pesanan.store'), [
            'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id,
            'model' => 'panggilan',
            'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'address' => 'Jl. Malioboro',
        ])->assertSessionHasErrors('service_id');

        $this->assertSame(0, Order::count());
    }
}
