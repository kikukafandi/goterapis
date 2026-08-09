<?php

namespace Tests\Feature;

use App\Models\Earning;
use App\Models\Order;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TherapistDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_hanya_dapat_diakses_terapis_dan_menampilkan_data_miliknya(): void
    {
        [$therapist, $profile, $service] = $this->therapist();
        $customer = User::factory()->create();
        $pending = $this->order($customer, $profile, $service, 'pending_confirmation', now()->addDay());
        $today = $this->order($customer, $profile, $service, 'paid', now()->setTime(14, 0));
        $completed = $this->order($customer, $profile, $service, 'completed', now()->subHour(), now());
        Earning::create(['therapist_profile_id' => $profile->id, 'order_id' => $completed->id, 'amount' => 85_000, 'available_at' => now()]);

        $this->actingAs($therapist)->get(route('mitra.dashboard'))
            ->assertOk()
            ->assertSee('Beranda Mitra')
            ->assertSee($pending->user->name)
            ->assertSee($today->service->name)
            ->assertSee('Rp85.000')
            ->assertSee('Jadwal mingguan')
            ->assertSeeInOrder(['Beranda', 'Pesanan', 'Pesan', 'Saldo', 'Profil'])
            ->assertDontSee('Marketplace layanan terapi panggilan ke rumah.');

        $this->actingAs($customer)->get(route('mitra.dashboard'))->assertRedirect(route('home'));
    }

    public function test_terapis_dapat_mengubah_ketersediaan_profilnya_sendiri(): void
    {
        [$therapist, $profile] = $this->therapist();
        $otherProfile = $this->therapist()[1];

        $this->actingAs($therapist)->patch(route('mitra.availability'), ['is_available' => false])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($profile->fresh()->is_available);
        $this->assertTrue($otherProfile->fresh()->is_available);
    }

    public function test_terapis_belum_disetujui_tidak_dapat_mengaktifkan_ketersediaan(): void
    {
        [$therapist, $profile] = $this->therapist();
        $profile->update(['verification_status' => 'anggota', 'is_available' => false]);

        $this->actingAs($therapist)->patch(route('mitra.availability'), ['is_available' => true])->assertForbidden();

        $this->assertFalse($profile->fresh()->is_available);
    }

    public function test_toggle_ketersediaan_menolak_pengguna_biasa_dan_nilai_tidak_valid(): void
    {
        [$therapist, $profile] = $this->therapist();

        $this->actingAs(User::factory()->create())->patch(route('mitra.availability'), ['is_available' => false])
            ->assertRedirect(route('home'));
        $this->actingAs($therapist)->patch(route('mitra.availability'), ['is_available' => 'kadang'])
            ->assertSessionHasErrors('is_available');

        $this->assertTrue($profile->fresh()->is_available);
    }

    private function therapist(): array
    {
        $user = User::factory()->create(['role' => 'therapist']);
        $profile = TherapistProfile::create(['user_id' => $user->id, 'verification_status' => 'identitas', 'is_available' => true]);
        $profile->schedules()->create(['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '17:00']);
        $service = Service::create(['name' => 'Pijat Mitra', 'slug' => 'pijat-mitra-'.$profile->id, 'category' => 'pijat']);

        return [$user, $profile, $service];
    }

    private function order(User $customer, TherapistProfile $profile, Service $service, string $status, mixed $scheduledAt, mixed $completedAt = null): Order
    {
        return Order::create([
            'code' => 'GT-'.str()->upper(str()->random(8)),
            'user_id' => $customer->id,
            'therapist_profile_id' => $profile->id,
            'service_id' => $service->id,
            'model' => 'panggilan',
            'scheduled_at' => $scheduledAt,
            'duration_min' => 60,
            'price' => 100_000,
            'total' => 100_000,
            'payout' => 85_000,
            'status' => $status,
            'completed_at' => $completedAt,
        ]);
    }
}
