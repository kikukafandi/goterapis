<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhoneVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.whatsapp.url' => 'http://whatsapp.test', 'services.whatsapp.token' => 'rahasia']);
        Http::fake(['http://whatsapp.test/messages' => Http::response([], 201)]);
    }

    public function test_pendaftaran_mengirim_kode_dan_mengarahkan_ke_halaman_verifikasi(): void
    {
        $this->post(route('register'), [
            'name' => 'Pelanggan Baru',
            'email' => 'baru@example.com',
            'phone' => '081234567890',
            'password' => 'sandi-rahasia-2026',
            'password_confirmation' => 'sandi-rahasia-2026',
            'legal_accepted' => '1',
        ])->assertRedirect(route('phone.verify'));

        $this->assertDatabaseHas('users', ['email' => 'baru@example.com', 'phone_verified_at' => null]);
        Http::assertSent(fn ($request) => $request['to'] === '6281234567890'
            && str_contains($request['message'], 'Kode verifikasi GoTerapis'));
    }

    public function test_kode_benar_menandai_nomor_terverifikasi(): void
    {
        $user = $this->belumTerverifikasi();
        $code = $this->kirimKode($user, '081234567890');

        $this->actingAs($user)->post(route('phone.confirm'), ['code' => $code])
            ->assertRedirect(route('tutorial'));

        $this->assertNotNull($user->refresh()->phone_verified_at);
    }

    public function test_kode_salah_ditolak(): void
    {
        $user = $this->belumTerverifikasi();
        $this->kirimKode($user, '081234567890');

        $this->actingAs($user)->post(route('phone.confirm'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->refresh()->phone_verified_at);
    }

    public function test_nomor_salah_ketik_bisa_diperbaiki_sebelum_kode_dikirim(): void
    {
        $user = $this->belumTerverifikasi();
        $code = $this->kirimKode($user, '081299998888');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'phone' => '081299998888']);
        Http::assertSent(fn ($request) => $request['to'] === '6281299998888');

        $this->actingAs($user)->post(route('phone.confirm'), ['code' => $code])->assertSessionHasNoErrors();
    }

    public function test_nomor_yang_sudah_dipakai_akun_lain_ditolak(): void
    {
        User::factory()->create(['phone' => '081277776666']);
        $user = $this->belumTerverifikasi();

        $this->actingAs($user)->post(route('phone.send'), ['phone' => '081277776666'])
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'phone' => '081234567890']);
    }

    public function test_pemesanan_ditutup_sampai_nomor_terverifikasi(): void
    {
        $user = $this->belumTerverifikasi();
        $therapist = $this->terapis();

        $this->actingAs($user)->get(route('pesan.create', $therapist))->assertRedirect(route('phone.verify'));
        $this->actingAs($user)->post(route('pesanan.store'), [])->assertRedirect(route('phone.verify'));

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_halaman_verifikasi_dilewati_bila_nomor_sudah_terverifikasi(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('phone.verify'))
            ->assertRedirect(route('tutorial'));
    }

    private function belumTerverifikasi(): User
    {
        return User::factory()->create(['phone' => '081234567890', 'phone_verified_at' => null]);
    }

    /** Kirim kode ke nomor tertentu lalu baca kodenya dari pesan WhatsApp palsu. */
    private function kirimKode(User $user, string $phone): string
    {
        $this->actingAs($user)->post(route('phone.send'), ['phone' => $phone])->assertSessionHasNoErrors();

        $code = null;
        Http::assertSent(function ($request) use (&$code) {
            preg_match('/(\d{6})/', (string) $request['message'], $matches);
            $code = $matches[1] ?? $code;

            return true;
        });

        return (string) $code;
    }

    private function terapis(): TherapistProfile
    {
        $user = User::factory()->create(['role' => 'therapist']);
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
        $service = Service::create(['name' => 'Pijat', 'slug' => 'pijat-verif', 'category' => 'pijat', 'is_active' => true]);
        $profile->services()->attach($service->id, ['price' => 100000, 'duration_min' => 60]);

        return $profile;
    }
}
