<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function pendingOrder(User $user): Order
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

        return Order::create([
            'code' => 'GT-'.strtoupper(uniqid()),
            'user_id' => $user->id,
            'therapist_profile_id' => $profile->id,
            'service_id' => $service->id,
            'model' => 'panggilan',
            'scheduled_at' => now()->addDay(),
            'duration_min' => 60,
            'price' => 100_000, 'transport_fee' => 15_000, 'service_fee' => 3_000,
            'total' => 118_000, 'commission' => 15_000, 'payout' => 100_000,
            'status' => 'pending_payment',
        ]);
    }

    public function test_pengguna_membayar_pesanan(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->pendingOrder($user);

        $this->actingAs($user)
            ->post(route('pesanan.pay', $order))
            ->assertRedirect(route('pesanan.show', $order));

        $order->refresh();
        $this->assertSame('paid', $order->status);

        $payment = Payment::where('order_id', $order->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame('paid', $payment->status);
        $this->assertSame(118_000, $payment->amount);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_tidak_bisa_bayar_pesanan_orang_lain(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $order = $this->pendingOrder($owner);
        $intruder = User::factory()->create(['role' => 'user']);

        $this->actingAs($intruder)
            ->post(route('pesanan.pay', $order))
            ->assertRedirect(route('pesanan.index'));

        $this->assertSame('pending_payment', $order->fresh()->status);
        $this->assertSame(0, Payment::count());
    }

    public function test_tidak_bisa_bayar_sebelum_terapis_menerima(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->pendingOrder($user);
        $order->update(['status' => 'pending_confirmation']);

        $this->actingAs($user)
            ->post(route('pesanan.pay', $order))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('pending_confirmation', $order->fresh()->status);
        $this->assertSame(0, Payment::count());
    }

    public function test_pesanan_batal_otomatis_bila_lewat_batas_bayar(): void
    {
        config(['goterapis.payment_window_hours' => 1]);
        $order = $this->pendingOrder(User::factory()->create(['role' => 'user']));
        $order->update(['accepted_at' => now()->subHours(2)]);

        $this->assertSame(1, Order::expireUnpaid());

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertNotNull($order->cancelled_at);
    }

    public function test_pesanan_masih_dalam_jendela_bayar_tidak_dibatalkan(): void
    {
        config(['goterapis.payment_window_hours' => 1]);
        $order = $this->pendingOrder(User::factory()->create(['role' => 'user']));
        $order->update(['accepted_at' => now()->subMinutes(10)]);

        $this->assertSame(0, Order::expireUnpaid());
        $this->assertSame('pending_payment', $order->fresh()->status);
    }

    public function test_batas_bayar_tidak_melewati_jadwal_layanan(): void
    {
        config(['goterapis.payment_window_hours' => 24]);
        $order = $this->pendingOrder(User::factory()->create(['role' => 'user']));
        $order->update(['accepted_at' => now()->subHour(), 'scheduled_at' => now()->subMinute()]);

        // Jadwal sudah lewat walau jendela 24 jam belum habis → tetap batal.
        $this->assertSame(1, Order::expireUnpaid());
        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_tidak_bisa_bayar_setelah_batas_waktu(): void
    {
        config(['goterapis.payment_window_hours' => 1]);
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->pendingOrder($user);
        $order->update(['accepted_at' => now()->subHours(2)]);

        $this->actingAs($user)
            ->post(route('pesanan.pay', $order))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, Payment::count());
    }

    public function test_tidak_bisa_bayar_dua_kali(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->pendingOrder($user);
        $order->update(['status' => 'paid']);

        $this->actingAs($user)
            ->post(route('pesanan.pay', $order))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, Payment::count());
    }
}
