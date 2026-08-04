<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.url' => 'http://whatsapp.test',
            'services.whatsapp.token' => 'rahasia',
        ]);
    }

    public function test_nomor_lokal_dinormalisasi_dan_notifikasi_dikirim(): void
    {
        Http::fake(['http://whatsapp.test/messages' => Http::response([], 201)]);
        [$order, $therapist] = $this->order();

        $therapist->user->notify(new OrderStatusChanged($order, 'Ada pesanan baru.'));

        Http::assertSent(fn ($request) => $request->url() === 'http://whatsapp.test/messages'
            && $request->hasHeader('Authorization', 'Bearer rahasia')
            && $request['to'] === '6281234567890'
            && str_contains($request['message'], $order->code));
    }

    public function test_kegagalan_gateway_tidak_menggagalkan_notifikasi_utama(): void
    {
        Http::fake(['http://whatsapp.test/messages' => Http::response([], 500)]);
        [$order, $therapist] = $this->order();

        $therapist->user->notify(new OrderStatusChanged($order, 'Ada pesanan baru.'));

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $therapist->user_id]);
    }

    public function test_nomor_tidak_valid_tidak_dikirim_ke_gateway(): void
    {
        Http::fake();
        [$order, $therapist] = $this->order();
        $therapist->user->update(['phone' => null]);

        $therapist->user->notify(new OrderStatusChanged($order, 'Ada pesanan baru.'));

        Http::assertNothingSent();
    }

    /** @return array{Order, TherapistProfile} */
    private function order(): array
    {
        $customer = User::factory()->create(['role' => 'user']);
        $therapistUser = User::factory()->create(['role' => 'therapist', 'phone' => '0812-3456-7890']);
        $therapist = TherapistProfile::create([
            'user_id' => $therapistUser->id,
            'verification_status' => 'anggota',
            'serves_call' => true,
            'city' => 'Yogyakarta',
            'is_available' => true,
        ]);
        $service = Service::create(['name' => 'Pijat', 'slug' => 'pijat-wa', 'category' => 'pijat']);
        $order = Order::create([
            'code' => 'GT-WA123456',
            'user_id' => $customer->id,
            'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id,
            'model' => 'panggilan',
            'scheduled_at' => now()->addDay(),
            'duration_min' => 60,
            'price' => 100000,
            'transport_fee' => 0,
            'service_fee' => 0,
            'total' => 100000,
            'commission' => 15000,
            'payout' => 85000,
        ]);

        return [$order, $therapist];
    }
}
