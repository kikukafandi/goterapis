<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengguna_dapat_mengatur_ulang_kata_sandi_lewat_tautan_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token, $user) {
            $token = $notification->token;
            $this->assertSame('Atur ulang kata sandi GoTerapis', $notification->toMail($user)->subject);

            return true;
        });

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'sandi-baru-2026',
            'password_confirmation' => 'sandi-baru-2026',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('sandi-baru-2026', $user->refresh()->password));

        $this->post(route('login'), ['email' => $user->email, 'password' => 'sandi-baru-2026'])
            ->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_email_tak_terdaftar_tidak_membocorkan_keberadaan_akun(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'bukan-pengguna@example.com'])
            ->assertSessionHas('status', trans('passwords.sent'))
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_token_kedaluwarsa_ditolak(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.update'), [
            'token' => 'token-palsu',
            'email' => $user->email,
            'password' => 'sandi-baru-2026',
            'password_confirmation' => 'sandi-baru-2026',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_percobaan_masuk_dibatasi_setelah_lima_kali_gagal(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $ignored) {
            $this->post(route('login'), ['email' => $user->email, 'password' => 'salah'])
                ->assertSessionHasErrors('email');
        }

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(429);

        $this->assertGuest();
    }
}
