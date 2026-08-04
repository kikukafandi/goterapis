<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    /** Terapis panggilan + satu layanan (harga 100k, transport 15k). */
    private function bookableTherapist(): TherapistProfile
    {
        $therapistUser = User::factory()->create(['role' => 'therapist']);
        $profile = TherapistProfile::create([
            'user_id' => $therapistUser->id,
            'verification_status' => 'anggota',
            'serves_call' => true,
            'serves_place' => false,
            'city' => 'Yogyakarta',
            'transport_fee' => 15_000,
            'is_available' => true,
        ]);
        $service = Service::create(['name' => 'Pijat Kebugaran', 'slug' => 'pijat-kebugaran', 'category' => 'pijat']);
        $profile->services()->attach($service->id, ['price' => 100_000, 'duration_min' => 60]);

        return $profile;
    }

    public function test_membuat_pesanan_dengan_rincian_biaya_benar(): void
    {
        config(['goterapis.commission_percent' => 15, 'goterapis.service_fee' => 3_000]);
        $user = User::factory()->create(['role' => 'user']);
        $therapist = $this->bookableTherapist();
        $service = $therapist->services->first();

        $response = $this->actingAs($user)->post(route('pesanan.store'), [
            'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id,
            'model' => 'panggilan',
            'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'address' => 'Jl. Malioboro No. 1',
        ]);

        $order = Order::first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('pesanan.show', $order));

        $this->assertSame($user->id, $order->user_id);
        $this->assertSame('pending_confirmation', $order->status);
        $this->assertSame(100_000, $order->price);
        $this->assertSame(15_000, $order->transport_fee);
        $this->assertSame(3_000, $order->service_fee);
        $this->assertSame(118_000, $order->total);      // 100k + 15k + 3k
        $this->assertSame(15_000, $order->commission);  // 15% dari harga
        $this->assertSame(100_000, $order->payout);     // 100k + 15k - 15k
        $this->assertSame(60, $order->duration_min);
        $this->assertSame(6, strlen($order->start_pin));
    }

    public function test_ketersediaan_tidak_menampilkan_slot_yang_sudah_dipesan(): void
    {
        $firstUser = User::factory()->create(['role' => 'user']);
        $secondUser = User::factory()->create(['role' => 'user']);
        $therapist = $this->bookableTherapist();
        $service = $therapist->services->first();
        $start = now()->addDay()->setTime(13, 0);
        Order::create([
            'code' => 'GT-BOOKED', 'user_id' => $firstUser->id, 'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id, 'model' => 'panggilan', 'scheduled_at' => $start, 'duration_min' => 60,
            'price' => 100_000, 'transport_fee' => 15_000, 'service_fee' => 3_000,
            'total' => 118_000, 'commission' => 15_000, 'payout' => 100_000,
        ]);

        $response = $this->actingAs($secondUser)->getJson(route('pesan.availability', [
            'therapist' => $therapist,
            'date' => $start->format('Y-m-d'),
            'service_id' => $service->id,
        ]))->assertOk();

        $values = collect($response->json('slots'))->pluck('value');
        $this->assertFalse($values->contains($start->format('Y-m-d\TH:i')));
        $this->assertFalse($values->contains($start->copy()->subMinutes(30)->format('Y-m-d\TH:i')));
        $this->assertTrue($values->contains($start->copy()->addHour()->format('Y-m-d\TH:i')));
    }

    public function test_menolak_waktu_di_luar_jadwal_terapis(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $therapist = $this->bookableTherapist();
        $start = now()->addDay()->setTime(13, 0);
        $therapist->update(['schedule_configured' => true]);
        $therapist->schedules()->create([
            'day_of_week' => $start->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '12:00',
        ]);

        $this->actingAs($user)->post(route('pesanan.store'), [
            'therapist_profile_id' => $therapist->id,
            'service_id' => $therapist->services->first()->id,
            'model' => 'panggilan',
            'scheduled_at' => $start->format('Y-m-d\TH:i'),
            'address' => 'Jl. Test',
        ])->assertSessionHasErrors('scheduled_at');

        $this->assertSame(0, Order::count());
    }

    public function test_menolak_jadwal_yang_bertumpang_tindih(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $therapist = $this->bookableTherapist();
        $service = $therapist->services->first();
        $start = now()->addDay()->startOfHour();
        Order::create([
            'code' => 'GT-OVERLAP', 'user_id' => $user->id, 'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id, 'model' => 'panggilan', 'scheduled_at' => $start, 'duration_min' => 60,
            'price' => 100_000, 'transport_fee' => 15_000, 'service_fee' => 3_000,
            'total' => 118_000, 'commission' => 15_000, 'payout' => 100_000,
        ]);

        $this->actingAs($user)->post(route('pesanan.store'), [
            'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id,
            'model' => 'panggilan',
            'scheduled_at' => $start->copy()->addMinutes(30)->format('Y-m-d\TH:i'),
            'address' => 'Jl. Test',
        ])->assertSessionHasErrors('scheduled_at');

        $this->assertSame(1, Order::count());
    }

    public function test_mengizinkan_jadwal_tepat_setelah_pesanan_sebelumnya(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $therapist = $this->bookableTherapist();
        $service = $therapist->services->first();
        $start = now()->addDay()->startOfHour();
        Order::create([
            'code' => 'GT-ADJACENT', 'user_id' => $user->id, 'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id, 'model' => 'panggilan', 'scheduled_at' => $start, 'duration_min' => 60,
            'price' => 100_000, 'transport_fee' => 15_000, 'service_fee' => 3_000,
            'total' => 118_000, 'commission' => 15_000, 'payout' => 100_000,
        ]);

        $this->actingAs($user)->post(route('pesanan.store'), [
            'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id,
            'model' => 'panggilan',
            'scheduled_at' => $start->copy()->addHour()->format('Y-m-d\TH:i'),
            'address' => 'Jl. Test',
        ])->assertRedirect();

        $this->assertSame(2, Order::count());
    }

    public function test_mengizinkan_jadwal_yang_sama_dengan_pesanan_batal(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $therapist = $this->bookableTherapist();
        $service = $therapist->services->first();
        $start = now()->addDay()->startOfHour();
        Order::create([
            'code' => 'GT-CANCELLED', 'user_id' => $user->id, 'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id, 'model' => 'panggilan', 'scheduled_at' => $start, 'duration_min' => 60,
            'price' => 100_000, 'transport_fee' => 15_000, 'service_fee' => 3_000,
            'total' => 118_000, 'commission' => 15_000, 'payout' => 100_000, 'status' => 'cancelled',
        ]);

        $this->actingAs($user)->post(route('pesanan.store'), [
            'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id,
            'model' => 'panggilan',
            'scheduled_at' => $start->format('Y-m-d\TH:i'),
            'address' => 'Jl. Test',
        ])->assertRedirect();

        $this->assertSame(2, Order::count());
    }

    public function test_menolak_layanan_yang_tidak_ditawarkan_terapis(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $therapist = $this->bookableTherapist();
        $other = Service::create(['name' => 'Bekam', 'slug' => 'bekam', 'category' => 'bekam']);

        $this->actingAs($user)->post(route('pesanan.store'), [
            'therapist_profile_id' => $therapist->id,
            'service_id' => $other->id,
            'model' => 'panggilan',
            'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'address' => 'Jl. Test',
        ])->assertSessionHasErrors('service_id');

        $this->assertSame(0, Order::count());
    }

    public function test_menolak_model_panggilan_tanpa_alamat(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $therapist = $this->bookableTherapist();

        $this->actingAs($user)->post(route('pesanan.store'), [
            'therapist_profile_id' => $therapist->id,
            'service_id' => $therapist->services->first()->id,
            'model' => 'panggilan',
            'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ])->assertSessionHasErrors('address');

        $this->assertSame(0, Order::count());
    }

    public function test_mengarahkan_tamu_ke_halaman_masuk(): void
    {
        $therapist = $this->bookableTherapist();

        $this->get(route('pesan.create', $therapist))->assertRedirect(route('login'));
    }

    public function test_melarang_terapis_memesan(): void
    {
        $therapist = $this->bookableTherapist();
        $therapistUser = User::factory()->create(['role' => 'therapist']);

        $this->actingAs($therapistUser)->get(route('pesan.create', $therapist))->assertRedirect(route('home'));
    }

    public function test_pemilik_bisa_melihat_detail_dengan_status(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $therapist = $this->bookableTherapist();
        $order = Order::create([
            'code' => 'GT-VIEW1234',
            'user_id' => $user->id,
            'therapist_profile_id' => $therapist->id,
            'service_id' => $therapist->services->first()->id,
            'model' => 'panggilan',
            'scheduled_at' => now()->addDay(),
            'duration_min' => 60,
            'price' => 100_000, 'transport_fee' => 15_000, 'service_fee' => 3_000,
            'total' => 118_000, 'commission' => 15_000, 'payout' => 100_000,
            'status' => 'pending_payment',
        ]);

        $this->actingAs($user)->get(route('pesanan.show', $order))
            ->assertOk()
            ->assertSee('GT-VIEW1234')
            ->assertSee('Menunggu pembayaran');
    }

    public function test_pelanggan_mengonfirmasi_layanan_selesai(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $therapist = $this->bookableTherapist();
        $order = Order::create([
            'code' => 'GT-DONE1234',
            'user_id' => $user->id,
            'therapist_profile_id' => $therapist->id,
            'service_id' => $therapist->services->first()->id,
            'model' => 'panggilan',
            'scheduled_at' => now()->addDay(),
            'duration_min' => 60,
            'price' => 100_000, 'transport_fee' => 15_000, 'service_fee' => 3_000,
            'total' => 118_000, 'commission' => 15_000, 'payout' => 100_000,
            'status' => 'in_progress',
        ]);

        $this->actingAs($user)->patch(route('pesanan.complete', $order))->assertRedirect();

        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertNotNull($order->completed_at);
    }

    public function test_pelanggan_membatalkan_pesanan_dibayar_dan_dana_dikembalikan(): void
    {
        config(['goterapis.cancel_free_hours' => 2]);
        $user = User::factory()->create(['role' => 'user']);
        $therapist = $this->bookableTherapist();
        $order = Order::create([
            'code' => 'GT-CANCEL12',
            'user_id' => $user->id,
            'therapist_profile_id' => $therapist->id,
            'service_id' => $therapist->services->first()->id,
            'model' => 'panggilan',
            'scheduled_at' => now()->addDays(2),
            'duration_min' => 60,
            'price' => 100_000, 'transport_fee' => 15_000, 'service_fee' => 3_000,
            'total' => 118_000, 'commission' => 15_000, 'payout' => 100_000,
            'status' => 'paid',
        ]);
        $order->payment()->create(['gateway' => 'simulasi', 'amount' => 118_000, 'status' => 'paid', 'paid_at' => now()]);

        $this->actingAs($user)->patch(route('pesanan.cancel', $order))->assertRedirect();

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertNotNull($order->cancelled_at);
        $this->assertSame('refunded', $order->payment->status);

        $this->actingAs($user)->patch(route('pesanan.cancel', $order))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('refunded', $order->payment->fresh()->status);
    }

    public function test_refund_midtrans_diproses_sebelum_pesanan_dibatalkan(): void
    {
        config([
            'goterapis.gateway' => 'midtrans',
            'goterapis.cancel_free_hours' => 2,
            'services.midtrans.server_key' => 'SB-Mid-server-test',
            'services.midtrans.is_production' => false,
        ]);
        Http::fake(['api.sandbox.midtrans.com/*' => Http::response(['status_code' => '200'])]);
        $user = User::factory()->create(['role' => 'user']);
        $therapist = $this->bookableTherapist();
        $order = Order::create([
            'code' => 'GT-REFUND12', 'user_id' => $user->id, 'therapist_profile_id' => $therapist->id,
            'service_id' => $therapist->services->first()->id, 'model' => 'panggilan',
            'scheduled_at' => now()->addDays(2), 'duration_min' => 60,
            'price' => 100_000, 'transport_fee' => 15_000, 'service_fee' => 3_000,
            'total' => 118_000, 'commission' => 15_000, 'payout' => 100_000, 'status' => 'paid',
        ]);
        $order->payment()->create([
            'gateway' => 'midtrans', 'gateway_ref' => $order->code, 'amount' => 118_000,
            'status' => 'paid', 'paid_at' => now(),
        ]);

        $this->actingAs($user)->patch(route('pesanan.cancel', $order))->assertRedirect();

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.sandbox.midtrans.com/v2/GT-REFUND12/refund'
            && $request['refund_key'] === 'refund-GT-REFUND12'
            && $request['amount'] === 115_000
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('SB-Mid-server-test:')));
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('refunded', $order->payment->fresh()->status);
    }

    public function test_pesanan_tetap_dibayar_bila_refund_midtrans_gagal(): void
    {
        config([
            'goterapis.gateway' => 'midtrans',
            'services.midtrans.server_key' => 'SB-Mid-server-test',
        ]);
        Http::fake(['api.sandbox.midtrans.com/*' => Http::response(['status_code' => '500'], 500)]);
        $user = User::factory()->create(['role' => 'user']);
        $therapist = $this->bookableTherapist();
        $order = Order::create([
            'code' => 'GT-FAILREF1', 'user_id' => $user->id, 'therapist_profile_id' => $therapist->id,
            'service_id' => $therapist->services->first()->id, 'model' => 'panggilan',
            'scheduled_at' => now()->addDays(2), 'duration_min' => 60,
            'price' => 100_000, 'transport_fee' => 15_000, 'service_fee' => 3_000,
            'total' => 118_000, 'commission' => 15_000, 'payout' => 100_000, 'status' => 'paid',
        ]);
        $order->payment()->create([
            'gateway' => 'midtrans', 'gateway_ref' => $order->code, 'amount' => 118_000,
            'status' => 'paid', 'paid_at' => now(),
        ]);

        $this->actingAs($user)->patch(route('pesanan.cancel', $order))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame('paid', $order->payment->fresh()->status);
    }

    public function test_tidak_bisa_membatalkan_pesanan_berjalan(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $therapist = $this->bookableTherapist();
        $order = Order::create([
            'code' => 'GT-RUN12345',
            'user_id' => $user->id,
            'therapist_profile_id' => $therapist->id,
            'service_id' => $therapist->services->first()->id,
            'model' => 'panggilan',
            'scheduled_at' => now()->addDay(),
            'duration_min' => 60,
            'price' => 100_000, 'transport_fee' => 15_000, 'service_fee' => 3_000,
            'total' => 118_000, 'commission' => 15_000, 'payout' => 100_000,
            'status' => 'in_progress',
        ]);

        $this->actingAs($user)->patch(route('pesanan.cancel', $order))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('in_progress', $order->fresh()->status);
    }

    public function test_menyembunyikan_pesanan_milik_orang_lain(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $therapist = $this->bookableTherapist();
        $order = Order::create([
            'code' => 'GT-TEST1234',
            'user_id' => $owner->id,
            'therapist_profile_id' => $therapist->id,
            'service_id' => $therapist->services->first()->id,
            'model' => 'panggilan',
            'scheduled_at' => now()->addDay(),
            'duration_min' => 60,
            'price' => 100_000, 'transport_fee' => 15_000, 'service_fee' => 3_000,
            'total' => 118_000, 'commission' => 15_000, 'payout' => 100_000,
        ]);

        $intruder = User::factory()->create(['role' => 'user']);
        $this->actingAs($intruder)->get(route('pesanan.show', $order))->assertRedirect(route('pesanan.index'));
    }
}
