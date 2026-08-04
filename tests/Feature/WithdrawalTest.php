<?php

namespace Tests\Feature;

use App\Models\Earning;
use App\Models\Order;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WithdrawalTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_requires_complete_bank_details_and_available_funds(): void
    {
        [$therapist, $profile] = $this->therapist();
        Earning::create(['therapist_profile_id' => $profile->id, 'order_id' => $this->orderId($profile), 'amount' => 50000, 'available_at' => now()->addHour()]);

        $this->actingAs($therapist)->post(route('mitra.withdrawals.store'), ['amount' => 10000])->assertSessionHasErrors('amount');
        $profile->update(['bank_name' => 'BRI', 'bank_account_number' => '123', 'bank_account_name' => 'Siti']);
        $this->actingAs($therapist)->post(route('mitra.withdrawals.store'), ['amount' => 10000])->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('withdrawals', 0);
    }

    public function test_request_snapshots_bank_and_reserves_funds_immediately(): void
    {
        [$therapist, $profile] = $this->therapist(['bank_name' => 'BRI', 'bank_account_number' => '123', 'bank_account_name' => 'Siti']);
        Earning::create(['therapist_profile_id' => $profile->id, 'order_id' => $this->orderId($profile), 'amount' => 50000, 'available_at' => now()]);

        $this->actingAs($therapist)->post(route('mitra.withdrawals.store'), ['amount' => 40000])->assertSessionHasNoErrors();
        $profile->update(['bank_name' => 'BCA', 'bank_account_number' => '999']);

        $this->assertDatabaseHas('withdrawals', ['amount' => 40000, 'bank_name' => 'BRI', 'bank_account_number' => '123', 'bank_account_name' => 'Siti', 'status' => 'requested']);
        $this->actingAs($therapist)->post(route('mitra.withdrawals.store'), ['amount' => 20000])->assertSessionHasErrors('amount');
        $this->actingAs($therapist)->get(route('mitra.saldo'))->assertSee('Rp10.000');
    }

    private function therapist(array $attributes = []): array
    {
        $user = User::factory()->create(['role' => 'therapist']);
        $profile = TherapistProfile::create([...$attributes, 'user_id' => $user->id, 'verification_status' => 'anggota', 'city' => 'Yogyakarta']);

        return [$user, $profile];
    }

    private function orderId(TherapistProfile $profile): int
    {
        $customer = User::factory()->create();
        $service = Service::create(['name' => 'Pijat', 'slug' => 'pijat-'.uniqid(), 'category' => 'pijat']);

        return Order::create(['code' => 'GT-'.uniqid(), 'user_id' => $customer->id, 'therapist_profile_id' => $profile->id, 'service_id' => $service->id, 'model' => 'tempat', 'scheduled_at' => now(), 'duration_min' => 60, 'price' => 50000, 'total' => 50000, 'payout' => 50000, 'status' => 'completed'])->id;
    }
}
