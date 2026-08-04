<?php

namespace Tests\Feature;

use App\Models\TherapistProfile;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_access_withdrawals(): void
    {
        $this->get(route('admin.withdrawals.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'therapist']))->get(route('admin.withdrawals.index'))->assertRedirect(route('home'));
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admin.withdrawals.index'))->assertOk();
    }

    public function test_admin_approval_requires_reference_and_only_requested_can_transition(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $withdrawal = $this->withdrawal();

        $this->actingAs($admin)->patch(route('admin.withdrawals.approve', $withdrawal))->assertSessionHasErrors('transfer_reference');
        $this->actingAs($admin)->patch(route('admin.withdrawals.approve', $withdrawal), ['transfer_reference' => 'TRX-123'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('withdrawals', ['id' => $withdrawal->id, 'status' => 'approved', 'transfer_reference' => 'TRX-123']);
        $this->actingAs($admin)->patch(route('admin.withdrawals.reject', $withdrawal), ['rejection_reason' => 'Gagal'])->assertSessionHasErrors('status');
    }

    public function test_admin_rejection_requires_reason_and_releases_reservation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $withdrawal = $this->withdrawal();

        $this->actingAs($admin)->patch(route('admin.withdrawals.reject', $withdrawal))->assertSessionHasErrors('rejection_reason');
        $this->actingAs($admin)->patch(route('admin.withdrawals.reject', $withdrawal), ['rejection_reason' => 'Rekening tidak valid'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('withdrawals', ['id' => $withdrawal->id, 'status' => 'rejected', 'rejection_reason' => 'Rekening tidak valid']);
    }

    public function test_list_filters_status_and_puts_requested_first(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $approved = $this->withdrawal(['status' => 'approved', 'amount' => 10000]);
        $requested = $this->withdrawal(['amount' => 20000]);

        $this->actingAs($admin)->get(route('admin.withdrawals.index'))->assertSeeInOrder(['Rp20.000', 'Rp10.000']);
        $this->actingAs($admin)->get(route('admin.withdrawals.index', ['status' => 'approved']))->assertSee('Rp10.000')->assertDontSee('Rp20.000');
    }

    private function withdrawal(array $attributes = []): Withdrawal
    {
        $user = User::factory()->create(['role' => 'therapist']);
        $profile = TherapistProfile::create(['user_id' => $user->id, 'verification_status' => 'anggota', 'city' => 'Yogyakarta']);

        return Withdrawal::create([...$attributes, 'therapist_profile_id' => $profile->id, 'amount' => $attributes['amount'] ?? 50000, 'bank_name' => 'BRI', 'bank_account_number' => '123', 'bank_account_name' => 'Siti']);
    }
}
