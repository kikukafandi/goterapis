<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TherapistOrderTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0:User,1:TherapistProfile,2:Order} pemilik terapis, profil, satu pesanan masuk */
    private function therapistWithOrder(string $status = 'pending_confirmation'): array
    {
        $therapistUser = User::factory()->create(['role' => 'therapist']);
        $profile = TherapistProfile::create([
            'user_id' => $therapistUser->id,
            'verification_status' => 'anggota',
            'serves_call' => true,
            'city' => 'Yogyakarta',
            'is_available' => true,
        ]);
        $service = Service::create(['name' => 'Pijat', 'slug' => 'pijat-'.uniqid(), 'category' => 'pijat']);
        $customer = User::factory()->create(['role' => 'user']);

        $order = Order::create([
            'code' => 'GT-'.strtoupper(uniqid()),
            'user_id' => $customer->id,
            'therapist_profile_id' => $profile->id,
            'service_id' => $service->id,
            'model' => 'panggilan',
            'scheduled_at' => now()->addDay(),
            'duration_min' => 60,
            'price' => 100_000, 'transport_fee' => 15_000, 'service_fee' => 3_000,
            'total' => 118_000, 'commission' => 15_000, 'payout' => 100_000,
            'status' => $status,
            'start_pin' => '123456',
        ]);

        return [$therapistUser, $profile, $order];
    }

    public function test_terapis_menerima_pesanan(): void
    {
        [$therapistUser, , $order] = $this->therapistWithOrder();

        $this->actingAs($therapistUser)
            ->patch(route('mitra.pesanan.accept', $order))
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('pending_payment', $order->status);
        $this->assertNotNull($order->accepted_at);
    }

    public function test_terapis_menolak_pesanan_dengan_alasan(): void
    {
        [$therapistUser, , $order] = $this->therapistWithOrder();

        $this->actingAs($therapistUser)
            ->patch(route('mitra.pesanan.reject', $order), ['cancel_reason' => 'Jadwal bentrok'])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('rejected', $order->status);
        $this->assertNotNull($order->cancelled_at);
        $this->assertSame('Jadwal bentrok', $order->cancel_reason);
    }

    public function test_terapis_tidak_bisa_menindak_pesanan_terapis_lain(): void
    {
        [, , $order] = $this->therapistWithOrder();
        $lain = User::factory()->create(['role' => 'therapist']);

        $this->actingAs($lain)
            ->patch(route('mitra.pesanan.accept', $order))
            ->assertRedirect(route('mitra.pesanan'));

        $this->assertSame('pending_confirmation', $order->fresh()->status);
    }

    public function test_pesanan_yang_sudah_diterima_tidak_bisa_ditindak_lagi(): void
    {
        [$therapistUser, , $order] = $this->therapistWithOrder('pending_payment');

        $this->actingAs($therapistUser)
            ->patch(route('mitra.pesanan.reject', $order))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('pending_payment', $order->fresh()->status);
    }

    public function test_terapis_memulai_layanan_dengan_pin_benar(): void
    {
        [$therapistUser, , $order] = $this->therapistWithOrder('paid');

        $this->actingAs($therapistUser)
            ->patch(route('mitra.pesanan.start', $order), ['pin' => '123456'])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('in_progress', $order->status);
        $this->assertNotNull($order->started_at);
    }

    public function test_pin_salah_tidak_memulai_layanan(): void
    {
        [$therapistUser, , $order] = $this->therapistWithOrder('paid');

        $this->actingAs($therapistUser)
            ->patch(route('mitra.pesanan.start', $order), ['pin' => '000000'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('paid', $order->fresh()->status);
    }

    public function test_tidak_bisa_mulai_sebelum_dibayar(): void
    {
        [$therapistUser, , $order] = $this->therapistWithOrder('pending_payment');

        $this->actingAs($therapistUser)
            ->patch(route('mitra.pesanan.start', $order), ['pin' => '123456'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('pending_payment', $order->fresh()->status);
    }

    public function test_terapis_di_titik_pelanggan_bisa_mulai(): void
    {
        config(['goterapis.start_radius_m' => 150]);
        [$therapistUser, , $order] = $this->therapistWithOrder('paid');
        $order->update(['lat' => -7.7975000, 'lng' => 110.3705000]);

        // ~55 m dari titik pelanggan → dalam radius
        $this->actingAs($therapistUser)
            ->patch(route('mitra.pesanan.start', $order), ['pin' => '123456', 'lat' => -7.7980000, 'lng' => 110.3705000])
            ->assertRedirect();

        $this->assertSame('in_progress', $order->fresh()->status);
    }

    public function test_terapis_jauh_dari_titik_ditolak(): void
    {
        config(['goterapis.start_radius_m' => 150]);
        [$therapistUser, , $order] = $this->therapistWithOrder('paid');
        $order->update(['lat' => -7.7975000, 'lng' => 110.3705000]);

        // ~1,1 km → di luar radius
        $this->actingAs($therapistUser)
            ->patch(route('mitra.pesanan.start', $order), ['pin' => '123456', 'lat' => -7.8075000, 'lng' => 110.3705000])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('paid', $order->fresh()->status);
    }

    public function test_akurasi_rendah_melonggarkan_cek_jarak(): void
    {
        config(['goterapis.start_radius_m' => 150]);
        [$therapistUser, , $order] = $this->therapistWithOrder('paid');
        // Titik pelanggan dengan akurasi buruk (mis. laptop/WiFi), ~3 km ketidakpastian.
        $order->update(['lat' => -7.7975000, 'lng' => 110.3705000, 'loc_accuracy' => 3000]);

        // Terapis ~1,1 km dari titik tersimpan, tapi akurasinya juga buruk → tak boleh salah-tolak.
        $this->actingAs($therapistUser)
            ->patch(route('mitra.pesanan.start', $order), [
                'pin' => '123456', 'lat' => -7.8075000, 'lng' => 110.3705000, 'acc' => 3000,
            ])
            ->assertRedirect();

        $this->assertSame('in_progress', $order->fresh()->status);
    }

    public function test_panggilan_tanpa_lokasi_terapis_ditolak(): void
    {
        [$therapistUser, , $order] = $this->therapistWithOrder('paid');
        $order->update(['lat' => -7.7975000, 'lng' => 110.3705000]);

        $this->actingAs($therapistUser)
            ->patch(route('mitra.pesanan.start', $order), ['pin' => '123456'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('paid', $order->fresh()->status);
    }

    public function test_pengguna_biasa_tidak_bisa_membuka_panel_terapis(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get(route('mitra.pesanan'))->assertRedirect(route('home'));
    }

    public function test_halaman_akun_butuh_login(): void
    {
        $this->get(route('akun'))->assertRedirect(route('login'));

        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)->get(route('akun'))->assertOk();
    }
}
