<?php

namespace Tests\Feature;

use App\Models\TherapistDocument;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TherapistSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_radius_memfilter_dan_mengurutkan_jarak(): void
    {
        $this->therapist('Dekat', 'pria', 'Bandung', -6.2, 106.8168);
        $this->therapist('Sedang', 'wanita', 'Bandung', -6.245, 106.8168);
        $this->therapist('Jauh', 'pria', 'Bandung', -7.2, 106.8168);
        $this->get(route('cari', ['lat' => -6.2, 'lng' => 106.8168, 'radius' => 10, 'sort' => 'terdekat']))
            ->assertOk()->assertSeeInOrder(['Dekat', 'Sedang'])->assertDontSee('Jauh')->assertSee('0,0 km');
    }

    public function test_fallback_kota_dan_filter_gender(): void
    {
        $this->therapist('Pria Bandung', 'pria', 'Bandung', -6.2, 106.8);
        $this->therapist('Wanita Bogor', 'wanita', 'Bogor', -6.6, 106.8);
        $this->get(route('cari', ['kota' => 'Bogor', 'gender' => 'wanita']))->assertOk()->assertSee('Wanita Bogor')->assertDontSee('Pria Bandung');
    }

    public function test_parameter_liar_ditolak(): void
    {
        foreach ([['kategori' => 'liar'], ['model' => 'liar'], ['sort' => 'liar'], ['gender' => 'liar'], ['lat' => 91, 'lng' => 0, 'radius' => 25], ['lat' => 0, 'lng' => 181, 'radius' => 25], ['lat' => 0, 'lng' => 0, 'radius' => 101]] as $query) {
            $this->get(route('cari', $query))->assertSessionHasErrors();
        }
    }

    public function test_search_bar_navbar_disembunyikan_di_halaman_cari(): void
    {
        $response = $this->get(route('cari'))->assertOk();

        $this->assertSame(1, substr_count($response->getContent(), 'placeholder="Layanan atau nama terapis"'));
    }

    private function therapist(string $name, string $gender, string $city, float $lat, float $lng): TherapistProfile
    {
        $user = User::factory()->create(['name' => $name, 'role' => 'therapist']);
        $profile = TherapistProfile::create(['user_id' => $user->id, 'gender' => $gender, 'city' => $city, 'service_lat' => $lat, 'service_lng' => $lng, 'verification_status' => 'identitas', 'is_available' => true]);
        TherapistDocument::create(['therapist_profile_id' => $profile->id, 'type' => 'ktp', 'path' => "dokumen/{$profile->id}.jpg", 'status' => 'approved']);

        return $profile;
    }
}
