<?php

namespace Tests\Feature;

use App\Models\DeactivationRequest;
use App\Models\Order;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use App\Models\UserBan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountModerationTest extends TestCase
{
    use RefreshDatabase;

    private function therapist(): array
    {
        $user = User::factory()->create(['role' => 'therapist']);
        $profile = TherapistProfile::create(['user_id' => $user->id, 'verification_status' => 'identitas', 'is_available' => true, 'is_featured' => true]);

        return [$user, $profile];
    }

    public function test_therapist_can_submit_only_one_pending_request(): void
    {
        [$user] = $this->therapist();
        $this->actingAs($user)->post(route('deactivation-requests.store'), ['reason' => 'Berhenti sementara'])->assertRedirect();
        $this->assertAuthenticated()->assertDatabaseHas('deactivation_requests', ['user_id' => $user->id, 'status' => 'pending']);
        $this->actingAs($user)->post(route('deactivation-requests.store'))->assertSessionHasErrors('reason');
        $this->assertSame(1, DeactivationRequest::count());
    }

    public function test_admin_can_reject_or_approve_without_deleting_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$user, $profile] = $this->therapist();
        $rejected = DeactivationRequest::create(['user_id' => $user->id]);
        $this->actingAs($admin)->patch(route('admin.deactivations.update', $rejected), ['status' => 'rejected', 'admin_note' => 'Silakan lanjutkan akun'])->assertRedirect();
        $approved = DeactivationRequest::create(['user_id' => $user->id]);
        $this->actingAs($admin)->patch(route('admin.deactivations.update', $approved), ['status' => 'approved', 'admin_note' => 'Disetujui'])->assertRedirect();

        $this->assertNotNull($user->fresh()->deactivated_at);
        $this->assertFalse($profile->fresh()->is_available);
        $this->assertFalse($profile->fresh()->is_featured);
        $this->assertSame(2, DeactivationRequest::count());
        $this->assertDatabaseHas('deactivation_requests', ['id' => $rejected->id, 'status' => 'rejected', 'reviewed_by' => $admin->id]);
    }

    public function test_approval_is_refused_while_order_is_active(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        [$user, $profile] = $this->therapist();
        $service = Service::create(['name' => 'Pijat', 'slug' => 'pijat', 'category' => 'pijat']);
        $request = DeactivationRequest::create(['user_id' => $user->id]);
        Order::create(['code' => 'GT-BLOCK', 'user_id' => $customer->id, 'therapist_profile_id' => $profile->id, 'service_id' => $service->id, 'model' => 'tempat', 'scheduled_at' => now()->addDay(), 'duration_min' => 60, 'price' => 100000, 'total' => 100000, 'payout' => 85000, 'status' => 'paid']);

        $this->actingAs($admin)->patch(route('admin.deactivations.update', $request), ['status' => 'approved', 'admin_note' => 'Setuju'])->assertSessionHasErrors('status');
        $this->assertNull($user->fresh()->deactivated_at);
        $this->assertSame('pending', $request->fresh()->status);
    }

    public function test_non_admin_cannot_moderate_users(): void
    {
        $this->actingAs(User::factory()->create())->post(route('admin.users.ban', User::factory()->create()), ['duration' => '1', 'reason' => 'Alasan'])->assertRedirect(route('home'));
    }

    public function test_admin_can_ban_and_unban_user_with_audit_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$user, $profile] = $this->therapist();
        $this->actingAs($admin)->post(route('admin.users.ban', $user), ['duration' => 'permanent', 'reason' => 'Pelanggaran'])->assertRedirect();
        $this->assertTrue($user->fresh()->isBlocked());
        $this->assertFalse($profile->fresh()->is_available);
        $this->assertDatabaseHas('user_bans', ['user_id' => $user->id, 'actor_id' => $admin->id, 'reason' => 'Pelanggaran', 'expires_at' => null]);
        $this->actingAs($admin)->patch(route('admin.users.unban', $user))->assertRedirect();
        $this->assertFalse($user->fresh()->isBlocked());
        $this->assertFalse($profile->fresh()->is_available);
        $this->assertNotNull(UserBan::first()->unbanned_at);
    }

    public function test_expired_temporary_ban_allows_login_but_active_ban_does_not(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $expired = User::factory()->create();
        UserBan::create(['user_id' => $expired->id, 'actor_id' => $admin->id, 'reason' => 'Selesai', 'expires_at' => now()->subMinute()]);
        $this->post(route('login'), ['email' => $expired->email, 'password' => 'password'])->assertRedirect();
        $this->post(route('logout'));
        $active = User::factory()->create();
        UserBan::create(['user_id' => $active->id, 'actor_id' => $admin->id, 'reason' => 'Aktif', 'expires_at' => now()->addDay()]);
        $this->post(route('login'), ['email' => $active->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->actingAs($active)->get(route('home'))->assertRedirect(route('login'));
    }

    public function test_admin_cannot_ban_self_or_another_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $other = User::factory()->create(['role' => 'admin']);
        foreach ([$admin, $other] as $target) {
            $this->actingAs($admin)->post(route('admin.users.ban', $target), ['duration' => '7', 'reason' => 'Tidak boleh'])->assertSessionHasErrors('user');
        }
        $this->assertSame(0, UserBan::count());
    }
}
