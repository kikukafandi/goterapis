<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TherapistProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_terapis_dapat_memperbarui_profil_dan_layanan(): void
    {
        [$user, $profile, $service] = $this->therapist();

        $this->actingAs($user)->put(route('mitra.profil.update'), [
            'name' => 'Terapis Baru',
            'email' => $user->email,
            'phone' => '081299998888',
            'gender' => 'wanita',
            'experience_years' => 8,
            'bio' => 'Profil profesional terbaru.',
            'province' => 'DI Yogyakarta',
            'city' => 'Sleman',
            'district' => 'Depok',
            'serves_call' => '1',
            'transport_fee' => 20000,
            'services' => [$service->id],
            'price' => [$service->id => 175000],
            'duration' => [$service->id => 90],
            'schedules' => $this->schedules(),
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Terapis Baru', 'phone' => '081299998888']);
        $this->assertDatabaseHas('therapist_profiles', ['id' => $profile->id, 'city' => 'Sleman', 'serves_call' => true]);
        $this->assertDatabaseHas('therapist_service', ['therapist_profile_id' => $profile->id, 'service_id' => $service->id, 'price' => 175000, 'duration_min' => 90]);
    }

    public function test_terapis_dapat_mengatur_hari_dan_jam_layanan(): void
    {
        [$user, $profile, $service] = $this->therapist();
        $schedules = $this->schedules();
        $schedules[0]['active'] = '0';
        $schedules[1] = ['day' => 1, 'active' => '1', 'start' => '09:00', 'end' => '17:00'];

        $this->actingAs($user)->put(route('mitra.profil.update'), $this->profileData($user, $service, $schedules))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('therapist_profiles', ['id' => $profile->id, 'schedule_configured' => true]);
        $this->assertDatabaseMissing('schedules', ['therapist_profile_id' => $profile->id, 'day_of_week' => 0]);
        $this->assertDatabaseHas('schedules', [
            'therapist_profile_id' => $profile->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);
    }

    public function test_jam_selesai_harus_setelah_jam_mulai(): void
    {
        [$user, $profile, $service] = $this->therapist();
        $profile->schedules()->create(['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '16:00']);
        $schedules = $this->schedules();
        $schedules[1] = ['day' => 1, 'active' => '1', 'start' => '17:00', 'end' => '16:00'];

        $this->actingAs($user)->put(route('mitra.profil.update'), $this->profileData($user, $service, $schedules))
            ->assertSessionHasErrors('schedules.1.end');

        $this->assertDatabaseHas('schedules', ['therapist_profile_id' => $profile->id, 'day_of_week' => 1, 'start_time' => '08:00']);
    }

    public function test_form_edit_profil_memiliki_pratinjau_foto(): void
    {
        [$user] = $this->therapist();

        $this->actingAs($user)
            ->get(route('mitra.profil.edit'))
            ->assertOk()
            ->assertSee('id="avatar-preview"', false)
            ->assertSee('URL.createObjectURL(this.files[0])', false)
            ->assertSee('navigator.geolocation.getCurrentPosition', false)
            ->assertSee('>Gunakan lokasi perangkat</button>', false);
    }

    public function test_pengguna_biasa_tidak_dapat_membuka_edit_profil_terapis(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get(route('mitra.profil.edit'))
            ->assertRedirect(route('home'));
    }

    public function test_kontak_terapis_tidak_tampil_di_profil_publik(): void
    {
        [$user, $profile] = $this->therapist();

        $this->get(route('terapis.show', $profile))
            ->assertOk()
            ->assertDontSee($user->email)
            ->assertDontSee($user->phone);
    }

    public function test_terapis_belum_disetujui_tidak_tampil_di_pencarian_dan_profil_publik(): void
    {
        [$user, $profile] = $this->therapist();
        $profile->update(['verification_status' => 'anggota']);

        $this->get(route('cari'))->assertOk()->assertDontSee($user->name);
        $this->get(route('terapis.show', $profile))->assertNotFound();
    }

    private function profileData(User $user, Service $service, array $schedules): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'gender' => 'pria',
            'experience_years' => 5,
            'province' => 'DI Yogyakarta',
            'city' => 'Yogyakarta',
            'serves_call' => '1',
            'transport_fee' => 15000,
            'services' => [$service->id],
            'price' => [$service->id => 100000],
            'duration' => [$service->id => 60],
            'schedules' => $schedules,
        ];
    }

    private function schedules(): array
    {
        return collect(range(0, 6))->map(fn (int $day) => [
            'day' => $day,
            'active' => '1',
            'start' => '08:00',
            'end' => '20:00',
        ])->all();
    }

    /** @return array{User, TherapistProfile, Service} */
    private function therapist(): array
    {
        $user = User::factory()->create([
            'role' => 'therapist',
            'phone' => '081234567890',
            // Gerbang OTP ganti nomor diuji terpisah di OtpTest.
            'phone_verified_at' => null,
        ]);
        $profile = TherapistProfile::create([
            'user_id' => $user->id,
            'gender' => 'pria',
            'experience_years' => 5,
            'province' => 'DI Yogyakarta',
            'city' => 'Yogyakarta',
            'verification_status' => 'identitas',
            'serves_call' => true,
            'is_available' => true,
        ]);
        $service = Service::create([
            'name' => 'Pijat Kebugaran',
            'slug' => 'pijat-profil',
            'category' => 'pijat',
            'is_active' => true,
        ]);
        $profile->services()->attach($service->id, ['price' => 100000, 'duration_min' => 60]);

        return [$user, $profile, $service];
    }
}
