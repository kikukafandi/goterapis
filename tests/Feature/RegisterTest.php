<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->post('/daftar', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'legal_accepted' => '1',
            'legal_version' => 'PALSU',
        ]);

        $response->assertRedirect(route('phone.verify'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'role' => 'user',
            'legal_version' => config('legal.version'),
        ]);
        $this->assertNotNull(User::where('email', 'budi@example.com')->value('legal_accepted_at'));
    }

    public function test_user_registration_requires_legal_consent(): void
    {
        $this->from('/daftar')->post('/daftar', [
            'name' => 'Budi Santoso',
            'email' => 'tanpapersetujuan@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect('/daftar')->assertSessionHasErrors('legal_accepted');

        $this->assertDatabaseMissing('users', ['email' => 'tanpapersetujuan@example.com']);
    }

    public function test_therapist_registration_requires_legal_consent(): void
    {
        $this->from('/daftar-terapis')->post('/daftar-terapis', [
            'name' => 'Terapis Tanpa Persetujuan',
            'email' => 'terapis-tanpapersetujuan@example.com',
            'phone' => '081234567800',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect('/daftar-terapis')->assertSessionHasErrors('legal_accepted');

        $this->assertDatabaseMissing('users', ['email' => 'terapis-tanpapersetujuan@example.com']);
    }

    public function test_therapist_registration_creates_profile_services_and_documents(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $pijat = Service::create(['name' => 'Pijat Tradisional', 'slug' => 'pijat-tradisional', 'category' => 'pijat']);
        $bekam = Service::create(['name' => 'Bekam Basah', 'slug' => 'bekam-basah', 'category' => 'bekam']);

        $response = $this->post('/daftar-terapis', [
            'name' => 'Siti Terapis',
            'email' => 'siti@example.com',
            'phone' => '081200000000',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'gender' => 'wanita',
            'experience_years' => 5,
            'bio' => 'Ahli pijat tradisional.',
            'province' => 'Jawa Barat',
            'city' => 'Bandung',
            'serves_call' => '1',
            'transport_fee' => 15000,
            'services' => [$pijat->id, $bekam->id],
            'price' => [$pijat->id => 80000, $bekam->id => 100000],
            'duration' => [$pijat->id => 60, $bekam->id => 90],
            'ktp' => UploadedFile::fake()->image('ktp.jpg'),
            'avatar' => UploadedFile::fake()->image('foto.jpg'),
            'sertifikat_pelatihan' => UploadedFile::fake()->create('sertifikat.pdf', 100, 'application/pdf'),
            'legal_accepted' => '1',
            'legal_version' => 'PALSU',
        ]);

        $response->assertRedirect(route('phone.verify'));
        $this->assertAuthenticated();

        $user = User::where('email', 'siti@example.com')->firstOrFail();
        $this->assertSame('therapist', $user->role);
        $this->assertSame(config('legal.version'), $user->legal_version);
        $this->assertNotNull($user->legal_accepted_at);
        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);

        $profile = $user->therapistProfile;
        $this->assertNotNull($profile);
        $this->assertSame('anggota', $profile->verification_status);
        $this->assertTrue($profile->serves_call);
        $this->assertSame(15000, $profile->transport_fee);

        // Dua layanan terpasang dengan harga di pivot
        $this->assertCount(2, $profile->services);
        $this->assertSame(80000, (int) $profile->services->firstWhere('id', $pijat->id)->pivot->price);

        // KTP, foto profil, sertifikat → 2 dokumen (avatar tersimpan di user, bukan dokumen)
        $this->assertCount(2, $profile->documents);
        $this->assertEqualsCanonicalizing(
            ['ktp', 'sertifikat_pelatihan'],
            $profile->documents->pluck('type')->all(),
        );
        foreach ($profile->documents as $document) {
            Storage::disk('local')->assertExists($document->path);
            Storage::disk('public')->assertMissing($document->path);
        }
    }

    public function test_therapist_can_view_verification_status(): void
    {
        $therapist = User::factory()->create(['role' => 'therapist']);
        $profile = $therapist->therapistProfile()->create([
            'verification_status' => 'anggota',
            'city' => 'Bandung',
        ]);
        $profile->documents()->create([
            'type' => 'ktp',
            'path' => 'therapist/ktp.jpg',
            'status' => 'rejected',
            'note' => 'Foto KTP kurang jelas.',
        ]);

        $this->actingAs($therapist)
            ->get(route('mitra.verifikasi'))
            ->assertOk()
            ->assertSee('Ada dokumen yang perlu diperbaiki')
            ->assertSee('Foto KTP kurang jelas.');
    }

    public function test_non_therapist_cannot_view_verification_status(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('mitra.verifikasi'))
            ->assertRedirect(route('home'));
    }

    public function test_therapist_registration_requires_a_service_model(): void
    {
        $service = Service::create(['name' => 'Kretek Tubuh', 'slug' => 'kretek-tubuh', 'category' => 'kretek']);

        $response = $this->from('/daftar-terapis')->post('/daftar-terapis', [
            'name' => 'Tanpa Model',
            'email' => 'nomodel@example.com',
            'phone' => '081211112222',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'gender' => 'pria',
            'experience_years' => 1,
            'province' => 'Jawa Barat',
            'city' => 'Bandung',
            'services' => [$service->id],
            'ktp' => UploadedFile::fake()->image('ktp.jpg'),
            'avatar' => UploadedFile::fake()->image('foto.jpg'),
            'legal_accepted' => '1',
            // serves_call & serves_place sengaja dikosongkan
        ]);

        $response->assertRedirect('/daftar-terapis');
        $response->assertSessionHasErrors('serves_call');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'nomodel@example.com']);
    }

    public function test_pelanggan_bisa_membuka_formulir_mitra_tanpa_dilempar_keluar(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get('/daftar-terapis')
            ->assertOk()
            ->assertSee('Akun yang dipakai')
            ->assertDontSee('Ulangi kata sandi');
    }

    public function test_pelanggan_naik_jadi_terapis_dengan_akun_yang_sama(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $service = Service::create(['name' => 'Pijat Punggung', 'slug' => 'pijat-punggung', 'category' => 'pijat']);
        $pelanggan = User::factory()->create([
            'role' => 'user',
            'gender' => 'pria',
            'phone' => '081299998888',
            'phone_verified_at' => now(),
        ]);

        $this->actingAs($pelanggan)->post('/daftar-terapis', [
            // Nama, email, dan sandi sengaja tak dikirim — dipakai dari akun yang sedang masuk.
            'phone' => '081299998888',
            'gender' => 'wanita', // diabaikan: akun sudah punya jenis kelamin
            'experience_years' => 3,
            'province' => 'Jawa Timur',
            'city' => 'Lamongan',
            'serves_call' => '1',
            'transport_fee' => 10000,
            'services' => [$service->id],
            'price' => [$service->id => 90000],
            'duration' => [$service->id => 60],
            'ktp' => UploadedFile::fake()->image('ktp.jpg'),
            'avatar' => UploadedFile::fake()->image('foto.jpg'),
            'legal_accepted' => '1',
        ])->assertRedirect(route('phone.verify'))->assertSessionHasNoErrors();

        $pelanggan->refresh();
        $this->assertSame('therapist', $pelanggan->role);
        $this->assertSame('pria', $pelanggan->gender);
        $this->assertSame('pria', $pelanggan->therapistProfile->gender);
        $this->assertSame('Lamongan', $pelanggan->therapistProfile->city);
        // Nomor tak berubah, jadi verifikasi lamanya tetap berlaku.
        $this->assertNotNull($pelanggan->phone_verified_at);
        $this->assertSame(1, User::count());
    }

    public function test_ganti_nomor_saat_naik_jadi_mitra_mencabut_verifikasi_lama(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $service = Service::create(['name' => 'Bekam Kering', 'slug' => 'bekam-kering', 'category' => 'bekam']);
        $pelanggan = User::factory()->create([
            'role' => 'user',
            'gender' => 'wanita',
            'phone' => '081200001111',
            'phone_verified_at' => now(),
        ]);

        $this->actingAs($pelanggan)->post('/daftar-terapis', [
            'phone' => '081277776666',
            'experience_years' => 2,
            'province' => 'Jawa Timur',
            'city' => 'Surabaya',
            'serves_place' => '1',
            'place_address' => 'Jl. Mawar 10',
            'services' => [$service->id],
            'price' => [$service->id => 70000],
            'duration' => [$service->id => 60],
            'ktp' => UploadedFile::fake()->image('ktp.jpg'),
            'avatar' => UploadedFile::fake()->image('foto.jpg'),
            'legal_accepted' => '1',
        ])->assertSessionHasNoErrors();

        $pelanggan->refresh();
        $this->assertSame('081277776666', $pelanggan->phone);
        $this->assertNull($pelanggan->phone_verified_at);
    }

    public function test_terapis_yang_sudah_terdaftar_diarahkan_ke_panel_mitra(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'therapist']))
            ->get('/daftar-terapis')
            ->assertRedirect(route('mitra.dashboard'));
    }

    public function test_admin_tidak_bisa_mendaftar_jadi_terapis(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/daftar-terapis')->assertRedirect(route('home'));

        $admin->refresh();
        $this->assertSame('admin', $admin->role);
    }
}
