<?php

namespace Tests\Feature;

use App\Jobs\RefundLatePayment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Report;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminReportTest extends TestCase
{
    use RefreshDatabase;

    private function report(string $status = 'open'): Report
    {
        $reporter = User::factory()->create();
        $reported = User::factory()->create(['role' => 'therapist']);

        return Report::create(['reporter_id' => $reporter->id, 'reported_user_id' => $reported->id, 'reportable_type' => Order::class, 'reportable_id' => 999, 'reason' => 'pelecehan_seksual', 'detail' => 'Keterangan laporan yang lengkap.', 'status' => $status, 'evidence' => ['chat' => [['sender_id' => $reported->id, 'body' => 'Bukti percakapan']]]]);
    }

    public function test_admin_bisa_melihat_daftar_detail_dan_bukti(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $report = $this->report();
        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk()->assertSee($report->reporter->name)->assertSee($report->reportedUser->name);
        $this->actingAs($admin)->get(route('admin.reports.show', $report))->assertOk()->assertSee('Bukti percakapan')->assertSee('Keterangan laporan yang lengkap.');
    }

    public function test_admin_bisa_memperbarui_peninjauan_tanpa_mengubah_bukti(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $report = $this->report();
        $evidence = $report->evidence;

        $this->actingAs($admin)->patch(route('admin.reports.update', $report), ['status' => 'reviewing', 'admin_note' => 'Sedang diverifikasi.'])->assertRedirect();

        $report->refresh();
        $this->assertSame('reviewing', $report->status);
        $this->assertSame($admin->id, $report->reviewed_by);
        $this->assertNotNull($report->reviewed_at);
        $this->assertEquals($evidence, $report->evidence);
    }

    public function test_non_admin_tidak_bisa_mengelola_laporan(): void
    {
        $report = $this->report();
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.reports.index'))->assertRedirect(route('home'));
        $this->actingAs($user)->patch(route('admin.reports.update', $report), ['status' => 'resolved'])->assertRedirect(route('home'));
    }

    public function test_badge_dan_dashboard_menghitung_open_dan_reviewing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->report('open');
        $this->report('reviewing');
        $this->report('resolved');

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Laporan')->assertSee('2');
        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk()->assertSee('2');
    }

    public function test_admin_bisa_melepaskan_dana_dan_resolusi_idempoten(): void
    {
        [$report, $order] = $this->disputedReport();
        $admin = User::factory()->create(['role' => 'admin']);
        $payload = ['status' => 'resolved', 'resolution' => 'release', 'admin_note' => 'Dana dilepas.'];

        $this->actingAs($admin)->patch(route('admin.reports.update', $report), $payload)->assertSessionHasNoErrors();
        $this->actingAs($admin)->patch(route('admin.reports.update', $report), $payload)->assertSessionHasNoErrors();

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertDatabaseCount('earnings', 1);
    }

    public function test_admin_bisa_refund_dengan_job_existing(): void
    {
        Queue::fake();
        [$report, $order] = $this->disputedReport();
        $admin = User::factory()->create(['role' => 'admin']);
        Payment::create(['order_id' => $order->id, 'method' => 'midtrans', 'status' => 'paid', 'amount' => $order->total]);

        $this->actingAs($admin)->patch(route('admin.reports.update', $report), ['status' => 'resolved', 'resolution' => 'refund', 'admin_note' => 'Refund penuh.'])->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('earnings', ['order_id' => $order->id]);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'refund_amount' => $order->total]);
        Queue::assertPushed(RefundLatePayment::class, 1);
    }

    private function disputedReport(): array
    {
        $customer = User::factory()->create();
        $therapist = User::factory()->create(['role' => 'therapist']);
        $profile = TherapistProfile::create(['user_id' => $therapist->id, 'verification_status' => 'anggota', 'city' => 'Yogyakarta']);
        $service = Service::create(['name' => 'Pijat', 'slug' => fake()->unique()->slug(), 'category' => 'pijat']);
        $order = Order::create(['code' => fake()->unique()->bothify('GT-ADM####'), 'user_id' => $customer->id, 'therapist_profile_id' => $profile->id, 'service_id' => $service->id, 'model' => 'tempat', 'scheduled_at' => now(), 'duration_min' => 60, 'price' => 100000, 'total' => 100000, 'payout' => 85000, 'status' => 'completed', 'completed_at' => now()]);
        $order->changeStatus('completed', 'Selesai.', ['completed_at' => now()], ['completed']);
        $order->earning()->create(['therapist_profile_id' => $profile->id, 'amount' => 85000, 'available_at' => now()]);
        $order->update(['status' => 'disputed']);
        $report = Report::create(['reporter_id' => $customer->id, 'reported_user_id' => $therapist->id, 'reportable_type' => Order::class, 'reportable_id' => $order->id, 'reason' => 'pelecehan_seksual', 'detail' => 'Keterangan laporan yang lengkap.', 'status' => 'open']);

        return [$report, $order];
    }
}
