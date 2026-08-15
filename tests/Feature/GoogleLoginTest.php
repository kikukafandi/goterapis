<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    /** Palsukan balikan Google supaya tes tidak menyentuh jaringan. */
    private function fakeGoogleUser(string $id, string $email, string $name = 'Budi Santoso'): void
    {
        $user = (new SocialiteUser)->map(['id' => $id, 'name' => $name, 'email' => $email]);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($user);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_pengguna_baru_terdaftar_lewat_google(): void
    {
        $this->fakeGoogleUser('google-123', 'budi@example.com');

        $response = $this->get(route('google.callback'));

        $response->assertRedirect(route('phone.verify'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'name' => 'Budi Santoso',
            'google_id' => 'google-123',
            'role' => 'user',
            'password' => null,
        ]);
    }

    public function test_email_yang_sudah_punya_sandi_ditolak(): void
    {
        $existing = User::factory()->create(['email' => 'budi@example.com']);

        $this->fakeGoogleUser('google-123', 'budi@example.com');

        $response = $this->get(route('google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertSame(1, User::where('email', 'budi@example.com')->count());
        $this->assertNull($existing->fresh()->google_id);
    }

    public function test_pengguna_google_lama_bisa_masuk_lagi(): void
    {
        $existing = User::factory()->create([
            'email' => 'budi@example.com',
            'google_id' => 'google-123',
            'password' => null,
        ]);

        $this->fakeGoogleUser('google-123', 'budi@example.com');

        $response = $this->get(route('google.callback'));

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($existing);
        $this->assertSame(1, User::count());
    }

    public function test_pembatalan_di_google_tidak_membuat_akun(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andThrow(new \RuntimeException('akses ditolak'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_tombol_google_mengalihkan_ke_google(): void
    {
        config(['services.google' => [
            'client_id' => 'id-palsu',
            'client_secret' => 'rahasia-palsu',
            'redirect' => 'http://localhost/auth/google/callback',
        ]]);

        $response = $this->get(route('google.redirect'));

        $response->assertRedirectContains('accounts.google.com');
    }

    public function test_halaman_masuk_dan_daftar_menawarkan_google_beserta_keterangan_legal(): void
    {
        foreach (['/masuk', '/daftar'] as $path) {
            $response = $this->get($path);

            $response->assertOk();
            $response->assertSee(route('google.redirect'), false);
            $response->assertSee('Syarat &amp; Ketentuan', false);
        }
    }
}
