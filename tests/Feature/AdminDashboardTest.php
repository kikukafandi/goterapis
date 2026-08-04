<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\TherapistDocument;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_menampilkan_ringkasan_dan_antrean_operasional(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reporter = User::factory()->create(['role' => 'user']);
        $therapistUser = User::factory()->create(['role' => 'therapist']);
        $profile = TherapistProfile::create([
            'user_id' => $therapistUser->id,
            'verification_status' => 'anggota',
            'city' => 'Yogyakarta',
        ]);
        TherapistDocument::create([
            'therapist_profile_id' => $profile->id,
            'type' => 'ktp',
            'path' => 'dokumen/ktp.jpg',
            'status' => 'pending',
        ]);
        Report::create([
            'reporter_id' => $reporter->id,
            'reportable_type' => User::class,
            'reportable_id' => $therapistUser->id,
            'reason' => 'Perlu ditinjau',
            'status' => 'open',
        ]);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Ringkasan Operasional')
            ->assertSee('Perlu Ditindaklanjuti')
            ->assertSee('Dokumen menunggu tinjauan')
            ->assertSee('Laporan terbuka')
            ->assertSee($therapistUser->name)
            ->assertSee('Status pendaftaran terbaru');
    }

    public function test_dashboard_memiliki_empty_state_yang_informatif(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Tidak ada antrean yang membutuhkan perhatian saat ini.')
            ->assertSee('Belum ada terapis terdaftar')
            ->assertSee('Belum ada ringkasan berkala');
    }

    public function test_pengguna_biasa_tidak_dapat_membuka_dashboard_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('home'));
    }
}
