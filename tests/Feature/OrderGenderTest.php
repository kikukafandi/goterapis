<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderGenderTest extends TestCase
{
    use RefreshDatabase;

    private function bookableTherapist(string $gender): TherapistProfile
    {
        $profile = TherapistProfile::create([
            'user_id' => User::factory()->create(['role' => 'therapist', 'gender' => $gender])->id,
            'gender' => $gender,
            'verification_status' => 'identitas',
            'serves_call' => true,
            'city' => 'Yogyakarta',
            'is_available' => true,
        ]);
        $service = Service::create(['name' => 'Pijat Kebugaran', 'slug' => fake()->unique()->slug(), 'category' => 'pijat']);
        $profile->services()->attach($service->id, ['price' => 100_000, 'duration_min' => 60]);

        return $profile;
    }

    private function payload(TherapistProfile $therapist): array
    {
        return [
            'therapist_profile_id' => $therapist->id,
            'service_id' => $therapist->services->first()->id,
            'model' => 'panggilan',
            'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'address' => 'Jl. Test',
        ];
    }

    public function test_pelanggan_tidak_bisa_memesan_terapis_lawan_jenis(): void
    {
        $customer = User::factory()->create(['role' => 'user', 'gender' => 'wanita']);
        $therapist = $this->bookableTherapist('pria');

        $this->actingAs($customer)->get(route('pesan.create', $therapist))->assertForbidden();
        $this->actingAs($customer)->post(route('pesanan.store'), $this->payload($therapist))->assertForbidden();

        $this->assertSame(0, Order::count());
    }

    public function test_pelanggan_bisa_memesan_terapis_sesama_jenis(): void
    {
        $customer = User::factory()->create(['role' => 'user', 'gender' => 'wanita']);
        $therapist = $this->bookableTherapist('wanita');

        $this->actingAs($customer)->get(route('pesan.create', $therapist))->assertOk();
        $this->actingAs($customer)->post(route('pesanan.store'), $this->payload($therapist))->assertRedirect();

        $this->assertSame(1, Order::count());
    }

    public function test_terapis_tanpa_jenis_kelamin_tidak_bisa_dipesan(): void
    {
        $customer = User::factory()->create(['role' => 'user', 'gender' => 'pria']);
        $therapist = $this->bookableTherapist('pria');
        $therapist->update(['gender' => null]);

        $this->actingAs($customer)->post(route('pesanan.store'), $this->payload($therapist))->assertForbidden();
        $this->assertSame(0, Order::count());
    }

    public function test_pemesan_tanpa_jenis_kelamin_diarahkan_mengisinya_dulu(): void
    {
        $customer = User::factory()->create(['role' => 'user', 'gender' => null]);
        $therapist = $this->bookableTherapist('pria');

        $this->actingAs($customer)->get(route('pesan.create', $therapist))->assertRedirect(route('phone.verify'));
        $this->actingAs($customer)->post(route('pesanan.store'), $this->payload($therapist))->assertRedirect(route('phone.verify'));
        $this->assertSame(0, Order::count());

        $this->actingAs($customer)->post(route('gender.store'), ['gender' => 'wanita'])->assertRedirect();
        $this->assertSame('wanita', $customer->fresh()->gender);
    }

    public function test_jenis_kelamin_tidak_bisa_diubah_setelah_terisi(): void
    {
        $customer = User::factory()->create(['role' => 'user', 'gender' => 'pria']);

        $this->actingAs($customer)->post(route('gender.store'), ['gender' => 'wanita']);

        $this->assertSame('pria', $customer->fresh()->gender);
    }

    public function test_pencarian_hanya_menampilkan_terapis_sesama_jenis(): void
    {
        $customer = User::factory()->create(['role' => 'user', 'gender' => 'wanita']);
        $wanita = $this->bookableTherapist('wanita');
        $pria = $this->bookableTherapist('pria');

        // Filter gender di URL sekalipun tidak bisa menembus penguncian.
        $this->actingAs($customer)->get(route('cari', ['gender' => 'pria']))
            ->assertOk()
            ->assertSee($wanita->user->name)
            ->assertDontSee($pria->user->name);
    }
}
