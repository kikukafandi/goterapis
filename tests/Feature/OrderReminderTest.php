<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderReminderTest extends TestCase
{
    use RefreshDatabase;

    private User $patient;

    private User $therapist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = User::factory()->create(['role' => 'user']);
        $this->therapist = User::factory()->create(['role' => 'therapist']);
    }

    private function order(array $attributes): Order
    {
        $profile = TherapistProfile::firstOrCreate(
            ['user_id' => $this->therapist->id],
            ['verification_status' => 'anggota', 'city' => 'Yogyakarta', 'serves_call' => true, 'is_available' => true],
        );
        $service = Service::firstOrCreate(['slug' => 'pijat-pengingat'], ['name' => 'Pijat', 'category' => 'pijat']);

        return Order::create([
            'code' => 'GT-'.strtoupper(uniqid()),
            'user_id' => $this->patient->id,
            'therapist_profile_id' => $profile->id,
            'service_id' => $service->id,
            'model' => 'panggilan',
            'duration_min' => 60,
            'price' => 100_000, 'transport_fee' => 15_000, 'service_fee' => 3_000,
            'total' => 118_000, 'commission' => 15_000, 'payout' => 100_000,
            ...$attributes,
        ]);
    }

    public function test_terapis_diingatkan_saat_pesanan_belum_dijawab(): void
    {
        Notification::fake();
        $order = $this->order(['status' => 'pending_confirmation', 'scheduled_at' => now()->addDays(2)]);
        $order->forceFill(['created_at' => now()->subHour()])->save();

        $this->assertSame(1, Order::sendReminders());

        Notification::assertSentTo($this->therapist, OrderStatusChanged::class);
        Notification::assertNotSentTo($this->patient, OrderStatusChanged::class);
    }

    public function test_pesanan_yang_baru_masuk_belum_diingatkan(): void
    {
        Notification::fake();
        $this->order(['status' => 'pending_confirmation', 'scheduled_at' => now()->addDays(2)]);

        $this->assertSame(0, Order::sendReminders());
        Notification::assertNothingSent();
    }

    public function test_pengingat_h_1_dan_satu_jam_dikirim_ke_kedua_pihak(): void
    {
        Notification::fake();
        $this->order(['status' => 'paid', 'scheduled_at' => now()->addHours(23)->addMinutes(30)]);
        $this->order(['status' => 'paid', 'scheduled_at' => now()->addMinutes(45)]);

        $this->assertSame(2, Order::sendReminders());

        Notification::assertSentToTimes($this->patient, OrderStatusChanged::class, 2);
        Notification::assertSentToTimes($this->therapist, OrderStatusChanged::class, 2);
    }

    public function test_pesanan_di_luar_jendela_pengingat_dilewati(): void
    {
        Notification::fake();
        $this->order(['status' => 'paid', 'scheduled_at' => now()->addHours(10)]);
        $this->order(['status' => 'pending_payment', 'scheduled_at' => now()->addMinutes(30)]);

        $this->assertSame(0, Order::sendReminders());
        Notification::assertNothingSent();
    }

    public function test_pesanan_yang_tak_dijawab_dibatalkan_setelah_batas_waktu(): void
    {
        Notification::fake();
        $order = $this->order(['status' => 'pending_confirmation', 'scheduled_at' => now()->addDays(2)]);
        $order->forceFill(['created_at' => now()->subHours(3)])->save();

        $this->assertSame(1, Order::expireUnanswered());
        $this->assertSame(0, Order::expireUnanswered());

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertNotNull($order->cancelled_at);
        Notification::assertSentTo($this->patient, OrderStatusChanged::class);
    }

    public function test_pesanan_tanpa_jawaban_dibatalkan_saat_jadwal_terlewat(): void
    {
        Notification::fake();
        $this->order(['status' => 'pending_confirmation', 'scheduled_at' => now()->subMinute()]);

        $this->assertSame(1, Order::expireUnanswered());
    }

    public function test_pesanan_yang_masih_dalam_batas_waktu_tidak_dibatalkan(): void
    {
        Notification::fake();
        $this->order(['status' => 'pending_confirmation', 'scheduled_at' => now()->addDays(2)]);
        $this->order(['status' => 'pending_payment', 'scheduled_at' => now()->subDay()]);

        $this->assertSame(0, Order::expireUnanswered());
        Notification::assertNothingSent();
    }

    public function test_pengingat_yang_sama_tidak_terkirim_dua_kali(): void
    {
        Notification::fake();
        $this->order(['status' => 'paid', 'scheduled_at' => now()->addMinutes(45)]);

        $this->assertSame(1, Order::sendReminders());
        $this->assertSame(0, Order::sendReminders());

        Notification::assertSentToTimes($this->patient, OrderStatusChanged::class, 1);
    }
}
