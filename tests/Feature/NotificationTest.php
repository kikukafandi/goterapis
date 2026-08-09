<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_notifikasi_hanya_menampilkan_notifikasi_pengguna_dengan_paginasi(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        foreach (range(1, 13) as $number) {
            $this->notification($user, "Kabar {$number}", now()->addSeconds($number));
        }
        $this->notification($other, 'Kabar pengguna lain');

        $this->actingAs($user)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Kabar 13')
            ->assertDontSee('Kabar pengguna lain')
            ->assertSee(route('notifications.index', ['page' => 2]));
    }

    public function test_notifikasi_mempertahankan_tampilan_pasien_dan_memberi_tampilan_mitra(): void
    {
        $patient = User::factory()->create();
        $therapist = User::factory()->create(['role' => 'therapist']);

        $this->actingAs($patient)->get(route('notifications.index'))
            ->assertOk()->assertSee('Pembaruan pesananmu akan muncul di sini.');
        $this->actingAs($therapist)->get(route('notifications.index'))
            ->assertOk()->assertSee('Pembaruan pesanan dan akun mitramu akan muncul di sini.');
    }

    public function test_url_status_pesanan_terapis_mengikuti_relasi_peserta(): void
    {
        $buyer = User::factory()->create(['role' => 'therapist']);
        $seller = User::factory()->create(['role' => 'therapist']);
        $profile = TherapistProfile::create(['user_id' => $seller->id, 'verification_status' => 'anggota', 'city' => 'Yogyakarta']);
        $service = Service::create(['name' => 'Pijat', 'slug' => 'pijat-notifikasi', 'category' => 'pijat']);
        $order = Order::create([
            'code' => 'GT-NOTIFY', 'user_id' => $buyer->id, 'therapist_profile_id' => $profile->id,
            'service_id' => $service->id, 'model' => 'tempat', 'scheduled_at' => now()->addDay(),
            'duration_min' => 60, 'price' => 100000, 'transport_fee' => 0, 'service_fee' => 3000,
            'total' => 103000, 'commission' => 15000, 'payout' => 85000,
        ]);
        $notification = new OrderStatusChanged($order, 'Berubah');

        $this->assertSame(route('pesanan.show', $order), $notification->toArray($buyer)['url']);
        $this->assertSame(route('mitra.pesanan.show', $order), $notification->toArray($seller)['url']);
    }

    public function test_pengguna_dapat_menandai_satu_notifikasi_miliknya_dibaca(): void
    {
        $user = User::factory()->create();
        $notification = $this->notification($user, 'Pesanan berubah');

        $this->actingAs($user)->patch(route('notifications.read', $notification))->assertRedirect('/pesanan');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_url_notifikasi_domain_lama_diarahkan_ke_path_lokal(): void
    {
        $user = User::factory()->create();
        $notification = $this->notification($user, 'Pesanan berubah');
        $notification->update(['data' => ['message' => 'Pesanan berubah', 'url' => 'https://domain-lama.test/pesanan/123?tab=aktif']]);

        $this->actingAs($user)->patch(route('notifications.read', $notification))
            ->assertRedirect('/pesanan/123?tab=aktif');
    }

    public function test_pengguna_tidak_dapat_menandai_notifikasi_pengguna_lain(): void
    {
        $notification = $this->notification(User::factory()->create(), 'Rahasia');

        $this->actingAs(User::factory()->create())->patch(route('notifications.read', $notification))->assertNotFound();
    }

    public function test_tandai_semua_hanya_mengubah_notifikasi_pengguna_sendiri(): void
    {
        $user = User::factory()->create();
        $own = $this->notification($user, 'Milik sendiri');
        $other = $this->notification(User::factory()->create(), 'Milik orang lain');

        $this->actingAs($user)->patch(route('notifications.read-all'))->assertRedirect();

        $this->assertNotNull($own->fresh()->read_at);
        $this->assertNull($other->fresh()->read_at);
    }

    private function notification(User $user, string $message, mixed $createdAt = null): DatabaseNotification
    {
        return $user->notifications()->create([
            'id' => fake()->uuid(),
            'type' => OrderStatusChanged::class,
            'data' => ['message' => $message, 'url' => '/pesanan'],
            'created_at' => $createdAt ?? now(),
            'updated_at' => $createdAt ?? now(),
        ]);
    }
}
