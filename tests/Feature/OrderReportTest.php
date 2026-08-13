<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Report;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderReportTest extends TestCase
{
    use RefreshDatabase;

    private function order(): Order
    {
        $customer = User::factory()->create(['role' => 'user']);
        $therapist = User::factory()->create(['role' => 'therapist']);
        $profile = TherapistProfile::create(['user_id' => $therapist->id, 'verification_status' => 'anggota', 'city' => 'Yogyakarta']);
        $service = Service::create(['name' => 'Pijat', 'slug' => fake()->unique()->slug(), 'category' => 'pijat']);

        return Order::create(['code' => fake()->unique()->bothify('GT-REP####'), 'user_id' => $customer->id, 'therapist_profile_id' => $profile->id, 'service_id' => $service->id, 'model' => 'panggilan', 'scheduled_at' => now()->addDay(), 'duration_min' => 60, 'address' => 'Alamat', 'lat' => -7.1, 'lng' => 110.1, 'start_pin' => '123456', 'price' => 100000, 'total' => 100000, 'commission' => 15000, 'payout' => 85000, 'status' => 'in_progress', 'started_at' => now()]);
    }

    public function test_peserta_non_admin_bisa_melapor_counterpart_dengan_bukti_aman(): void
    {
        $order = $this->order();
        $order->messages()->create(['sender_id' => $order->therapistProfile->user_id, 'body' => 'Pesan bukti']);

        $this->actingAs($order->user)->post(route('pesanan.reports.store', $order), ['reason' => 'pelecehan_seksual', 'detail' => str_repeat('Keterangan kejadian ', 2), 'source' => 'chat'])->assertRedirect();

        $report = $order->morphMany(Report::class, 'reportable')->firstOrFail();
        $this->assertSame($order->therapistProfile->user_id, $report->reported_user_id);
        $this->assertSame('Pesan bukti', $report->evidence['chat'][0]['body']);
        $this->assertArrayNotHasKey('start_pin', $report->evidence['order']);
        $this->assertArrayNotHasKey('lat', $report->evidence['order']);
        $this->assertArrayNotHasKey('lng', $report->evidence['order']);
    }

    public function test_laporan_idempoten_dan_bukti_pertama_tidak_tertimpa(): void
    {
        $order = $this->order();
        $payload = ['reason' => 'perilaku_tidak_pantas', 'detail' => str_repeat('Detail pertama ', 2), 'source' => 'order'];
        $this->actingAs($order->therapistProfile->user)->post(route('pesanan.reports.store', $order), $payload);
        $this->actingAs($order->therapistProfile->user)->post(route('pesanan.reports.store', $order), [...$payload, 'detail' => str_repeat('Detail kedua ', 2)]);

        $this->assertDatabaseCount('reports', 1);
        $this->assertDatabaseHas('reports', ['detail' => str_repeat('Detail pertama ', 2), 'reported_user_id' => $order->user_id]);
    }

    public function test_tamu_admin_dan_bukan_peserta_dilarang(): void
    {
        $order = $this->order();
        $payload = ['reason' => 'pelecehan_seksual', 'detail' => str_repeat('Keterangan ', 3), 'source' => 'order'];
        $this->post(route('pesanan.reports.store', $order), $payload)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->post(route('pesanan.reports.store', $order), $payload)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post(route('pesanan.reports.store', $order), $payload)->assertForbidden();
    }

    public function test_validasi_detail_sumber_dan_alasan(): void
    {
        $order = $this->order();
        $this->actingAs($order->user)->post(route('pesanan.reports.store', $order), ['reason' => 'lain', 'detail' => 'pendek', 'source' => 'lain'])->assertSessionHasErrors(['reason', 'detail', 'source']);
    }

    public function test_laporan_memindahkan_order_ke_sengketa_dan_menahan_earning(): void
    {
        $order = $this->order();
        $order->changeStatus('completed', 'Selesai.', ['completed_at' => now()], ['in_progress']);

        $this->actingAs($order->user)->post(route('pesanan.reports.store', $order), ['reason' => 'pelecehan_seksual', 'detail' => str_repeat('Keterangan ', 3), 'source' => 'order'])->assertSessionHasNoErrors();

        $this->assertSame('disputed', $order->fresh()->status);
        $order->therapistProfile->update(['verification_status' => 'identitas', 'bank_name' => 'BRI', 'bank_account_number' => '123', 'bank_account_name' => 'Siti']);
        $this->actingAs($order->therapistProfile->user)->post(route('mitra.withdrawals.store'), ['amount' => 10000, 'code' => '000000'])->assertSessionHasErrors('amount');
    }

    public function test_laporan_ditolak_di_luar_window_dan_saat_dana_masuk_payout(): void
    {
        $order = $this->order();
        $order->changeStatus('completed', 'Selesai.', ['completed_at' => now()->subHours(25)], ['in_progress']);
        $payload = ['reason' => 'pelecehan_seksual', 'detail' => str_repeat('Keterangan ', 3), 'source' => 'order'];

        $this->actingAs($order->user)->post(route('pesanan.reports.store', $order), $payload)->assertSessionHasErrors('detail');

        $order->update(['completed_at' => now()]);
        $order->earning->update(['available_at' => now()]);
        $order->therapistProfile->withdrawals()->create(['amount' => 85000, 'bank_name' => 'BRI', 'bank_account_number' => '123', 'bank_account_name' => 'Siti']);
        $this->actingAs($order->user)->post(route('pesanan.reports.store', $order), $payload)->assertSessionHasErrors('detail');
        $this->assertSame('completed', $order->fresh()->status);
    }
}
