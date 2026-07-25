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
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'role' => 'user',
        ]);
    }

    public function test_therapist_registration_creates_profile_services_and_documents(): void
    {
        Storage::fake('public');

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
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated();

        $user = User::where('email', 'siti@example.com')->firstOrFail();
        $this->assertSame('therapist', $user->role);
        $this->assertNotNull($user->avatar_path);

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
            // serves_call & serves_place sengaja dikosongkan
        ]);

        $response->assertRedirect('/daftar-terapis');
        $response->assertSessionHasErrors('serves_call');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'nomodel@example.com']);
    }
}
