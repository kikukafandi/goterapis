<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_akun_terblokir_tidak_dapat_masuk(): void
    {
        $user = User::factory()->create(['blocked_at' => now()]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_session_akun_yang_baru_diblokir_diputus(): void
    {
        $user = User::factory()->create(['blocked_at' => now()]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
