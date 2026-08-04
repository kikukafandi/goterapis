<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dapat_melihat_qr_gateway(): void
    {
        config([
            'services.whatsapp.url' => 'http://whatsapp.test',
            'services.whatsapp.token' => 'rahasia',
        ]);
        Http::fake(['http://whatsapp.test/status' => Http::response([
            'status' => 'qr',
            'qr' => 'data:image/png;base64,cXI=',
        ])]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.whatsapp'))
            ->assertOk()
            ->assertSee('Menunggu pemindaian')
            ->assertSee('data:image/png;base64,cXI=', false)
            ->assertDontSee('rahasia');
    }

    public function test_pengguna_biasa_tidak_dapat_membuka_dashboard_whatsapp(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get(route('admin.whatsapp'))
            ->assertRedirect(route('home'));
    }

    public function test_gateway_mati_tetap_menampilkan_halaman(): void
    {
        config(['services.whatsapp.url' => 'http://whatsapp.test']);
        Http::fake(['http://whatsapp.test/status' => Http::response([], 500)]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.whatsapp'))
            ->assertOk()
            ->assertSee('Gateway tidak tersedia');
    }
}
