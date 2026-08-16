<?php

namespace Tests\Feature;

use App\Models\Earning;
use App\Models\Order;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.whatsapp.url' => 'http://whatsapp.test', 'services.whatsapp.token' => 'rahasia']);
        Http::fake(['http://whatsapp.test/messages' => Http::response([], 201)]);
    }

    public function test_penarikan_tanpa_kode_ditolak(): void
    {
        [$therapist] = $this->therapistBersaldo();

        $this->actingAs($therapist)->post(route('mitra.withdrawals.store'), ['amount' => 40000])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('withdrawals', 0);
    }

    public function test_penarikan_dengan_kode_salah_ditolak(): void
    {
        [$therapist] = $this->therapistBersaldo();
        $this->kodeOtp($therapist, 'penarikan');

        $this->actingAs($therapist)->post(route('mitra.withdrawals.store'), ['amount' => 40000, 'code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('withdrawals', 0);
    }

    public function test_kode_benar_meloloskan_penarikan_dan_menandai_nomor_terverifikasi(): void
    {
        [$therapist] = $this->therapistBersaldo();
        $code = $this->kodeOtp($therapist, 'penarikan');

        Http::assertSent(fn ($request) => $request['to'] === '6281234567890'
            && str_contains($request['message'], $code));

        $this->actingAs($therapist)->post(route('mitra.withdrawals.store'), ['amount' => 40000, 'code' => $code])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('withdrawals', ['amount' => 40000, 'status' => 'requested']);
        $this->assertNotNull($therapist->refresh()->phone_verified_at);
    }

    public function test_kode_hangus_setelah_dipakai(): void
    {
        [$therapist] = $this->therapistBersaldo();
        $code = $this->kodeOtp($therapist, 'penarikan');

        $this->actingAs($therapist)->post(route('mitra.withdrawals.store'), ['amount' => 10000, 'code' => $code])
            ->assertSessionHasNoErrors();
        $this->actingAs($therapist)->post(route('mitra.withdrawals.store'), ['amount' => 10000, 'code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('withdrawals', 1);
    }

    public function test_kode_ganti_nomor_tidak_bisa_dipakai_untuk_penarikan(): void
    {
        [$therapist] = $this->therapistBersaldo();
        $code = $this->kodeOtp($therapist, 'nomor');

        $this->actingAs($therapist)->post(route('mitra.withdrawals.store'), ['amount' => 40000, 'code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('withdrawals', 0);
    }

    public function test_ganti_nomor_terverifikasi_butuh_kode_dari_nomor_lama(): void
    {
        [$therapist, $profile, $service] = $this->therapistBersaldo();
        $therapist->forceFill(['phone_verified_at' => now()])->save();
        $data = $this->dataProfil($therapist, $service, '081200001111');

        $this->actingAs($therapist)->put(route('mitra.profil.update'), $data)
            ->assertSessionHasErrors('code');
        $this->assertDatabaseHas('users', ['id' => $therapist->id, 'phone' => '081234567890']);

        $code = $this->kodeOtp($therapist, 'nomor');
        Http::assertSent(fn ($request) => $request['to'] === '6281234567890');

        $this->actingAs($therapist)->put(route('mitra.profil.update'), [...$data, 'code' => $code])
            ->assertSessionHasNoErrors();

        // Nomor baru belum terbukti dimiliki, jadi statusnya kembali belum terverifikasi.
        $this->assertDatabaseHas('users', ['id' => $therapist->id, 'phone' => '081200001111', 'phone_verified_at' => null]);
        $this->assertSame($profile->id, $therapist->refresh()->therapistProfile->id);
    }

    public function test_nomor_belum_terverifikasi_boleh_diganti_tanpa_kode(): void
    {
        [$therapist, , $service] = $this->therapistBersaldo();

        $this->actingAs($therapist)->put(route('mitra.profil.update'), $this->dataProfil($therapist, $service, '081200002222'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $therapist->id, 'phone' => '081200002222']);
    }

    public function test_kode_tetap_berlaku_meski_gateway_gagal_membalas(): void
    {
        [$therapist] = $this->therapistBersaldo();

        // Gateway kerap membalas gagal/putus padahal pesannya sudah sampai ke WhatsApp.
        // Host lain dipakai karena stub dari setUp() tidak bisa ditimpa, hanya ditumpuk.
        config(['services.whatsapp.url' => 'http://wa-gagal.test']);
        Http::fake(['http://wa-gagal.test/messages' => Http::response(['message' => 'timeout'], 500)]);

        $this->actingAs($therapist)->post(route('mitra.otp.send'), ['purpose' => 'penarikan'])
            ->assertSessionHasErrors('code');

        $code = null;
        Http::assertSent(function ($request) use (&$code) {
            preg_match('/(\d{6})/', (string) $request['message'], $matches);
            $code = $matches[1] ?? $code;

            return true;
        });

        // Kode yang terlanjur diterima pengguna harus tetap bisa dipakai.
        $this->actingAs($therapist)->post(route('mitra.withdrawals.store'), ['amount' => 40000, 'code' => $code])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('withdrawals', ['amount' => 40000, 'status' => 'requested']);
    }

    public function test_penolakan_gateway_dijelaskan_apa_adanya(): void
    {
        [$therapist] = $this->therapistBersaldo();

        config(['services.whatsapp.url' => 'http://wa-tolak.test']);
        Http::fake(['http://wa-tolak.test/messages' => Http::response(['message' => 'Nomor tidak terdaftar di WhatsApp.'], 422)]);

        $this->actingAs($therapist)->post(route('mitra.otp.send'), ['purpose' => 'penarikan'])
            ->assertSessionHasErrors(['code' => 'Nomor tidak terdaftar di WhatsApp.']);
    }

    /** @return array{User, TherapistProfile, Service} */
    private function therapistBersaldo(): array
    {
        $user = User::factory()->create(['role' => 'therapist', 'phone' => '081234567890', 'phone_verified_at' => null]);
        $profile = TherapistProfile::create([
            'user_id' => $user->id,
            'gender' => 'pria',
            'experience_years' => 5,
            'province' => 'DI Yogyakarta',
            'city' => 'Yogyakarta',
            'verification_status' => 'identitas',
            'serves_call' => true,
            'is_available' => true,
            'bank_name' => 'BRI',
            'bank_account_number' => '123',
            'bank_account_name' => 'Siti',
        ]);
        $service = Service::create(['name' => 'Pijat', 'slug' => 'pijat-otp', 'category' => 'pijat', 'is_active' => true]);
        $profile->services()->attach($service->id, ['price' => 100000, 'duration_min' => 60]);

        $customer = User::factory()->create();
        $order = Order::create([
            'code' => 'GT-OTP-'.uniqid(), 'user_id' => $customer->id, 'therapist_profile_id' => $profile->id,
            'service_id' => $service->id, 'model' => 'tempat', 'scheduled_at' => now(), 'duration_min' => 60,
            'price' => 50000, 'total' => 50000, 'payout' => 50000, 'status' => 'completed',
        ]);
        Earning::create(['therapist_profile_id' => $profile->id, 'order_id' => $order->id, 'amount' => 50000, 'available_at' => now()]);

        return [$user, $profile, $service];
    }

    /** @return array<string, mixed> */
    private function dataProfil(User $user, Service $service, string $phone): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $phone,
            'gender' => 'pria',
            'experience_years' => 5,
            'province' => 'DI Yogyakarta',
            'city' => 'Yogyakarta',
            'serves_call' => '1',
            'transport_fee' => 15000,
            'bank_name' => 'BRI',
            'bank_account_number' => '123',
            'bank_account_name' => 'Siti',
            'services' => [$service->id],
            'price' => [$service->id => 100000],
            'duration' => [$service->id => 60],
            'schedules' => collect(range(0, 6))->map(fn (int $day) => [
                'day' => $day, 'active' => '1', 'start' => '08:00', 'end' => '20:00',
            ])->all(),
        ];
    }
}
