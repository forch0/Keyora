<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Employee;

use App\Models\AccessGrant;
use App\Models\ActivityLog;
use App\Models\SecureLink;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use App\Models\VaultItem;
use App\Notifications\EmployeeOffboardedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\Helpers\AuthHelper;
use Tests\Helpers\TenantHelper;
use Tests\TestCase;

class EmployeeLifecycleTest extends TestCase
{
    use AuthHelper, RefreshDatabase, TenantHelper;

    private function setupTenantWithAdmin(): array
    {
        [$admin, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant(['name' => 'Life Co', 'slug' => 'life-co']);
        $this->attachUserToTenant($admin, $tenant, 'admin');
        $this->setupTenantContext($tenant);

        return [$tenant, $admin, $token];
    }

    public function test_invitation_with_team_assignment(): void
    {
        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();
        $team = Team::create([
            'tenant_id' => $tenant->id, 'name' => 'Engineering',
            'created_by' => $admin->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/invite", [
                'email' => 'new@example.com',
                'role' => 'member',
                'team_ids' => [$team->id],
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tenant_invitations', [
            'email' => 'new@example.com',
            'team_ids' => json_encode([$team->id]),
        ]);
    }

    public function test_invitation_with_initial_access(): void
    {
        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();
        $item = VaultItem::create([
            'tenant_id' => $tenant->id, 'team_id' => null, 'user_id' => $admin->id,
            'name' => 'Shared', 'type' => 'password', 'username' => 'u', 'password' => 'p',
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/invite", [
                'email' => 'new@example.com',
                'role' => 'member',
                'initial_access' => [
                    [
                        'resource_type' => VaultItem::class,
                        'resource_id' => $item->id,
                        'permission' => 'view',
                    ],
                ],
            ]);

        $response->assertStatus(201);
        $invitation = TenantInvitation::where('email', 'new@example.com')->first();
        $this->assertNotNull($invitation->initial_access);
    }

    public function test_onboarding_completion(): void
    {
        [$tenant, $user, $token] = $this->setupTenantWithAdmin();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/onboarding-complete");

        $response->assertStatus(204);

        $pivot = $user->tenants()->where('tenants.id', $tenant->id)->first()->pivot;
        $this->assertNotNull($pivot->onboarding_completed_at);
    }

    public function test_admin_can_assign_teams(): void
    {
        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();
        $team = Team::create([
            'tenant_id' => $tenant->id, 'name' => 'Design',
            'created_by' => $admin->id,
        ]);
        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}/teams", [
                'team_ids' => [$team->id],
            ]);

        $response->assertStatus(204);
        $this->assertTrue($member->teams()->where('teams.id', $team->id)->exists());
    }

    public function test_admin_can_remove_from_team(): void
    {
        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();
        $team = Team::create([
            'tenant_id' => $tenant->id, 'name' => 'QA',
            'created_by' => $admin->id,
        ]);
        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');
        $member->teams()->attach($team->id, ['role' => 'member', 'joined_at' => now()]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->deleteJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}/teams/{$team->id}");

        $response->assertStatus(204);
        $this->assertFalse($member->teams()->where('teams.id', $team->id)->exists());
    }

    public function test_removing_from_team_revokes_team_access(): void
    {
        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();
        $team = Team::create([
            'tenant_id' => $tenant->id, 'name' => 'Dev',
            'created_by' => $admin->id,
        ]);
        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');
        $member->teams()->attach($team->id, ['role' => 'member', 'joined_at' => now()]);

        // Create a team vault item and grant access to the member
        $item = VaultItem::create([
            'tenant_id' => $tenant->id, 'team_id' => $team->id, 'user_id' => $admin->id,
            'name' => 'Team Secret', 'type' => 'password', 'username' => 'u', 'password' => 'p',
        ]);
        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => VaultItem::class,
            'grantable_id' => $item->id,
            'subject_type' => User::class,
            'subject_id' => $member->id,
            'permission' => 'view',
            'granted_by' => $admin->id,
            'granted_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->deleteJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}/teams/{$team->id}");

        $response->assertStatus(204);
        $grant = AccessGrant::where('subject_id', $member->id)
            ->where('grantable_id', $item->id)
            ->first();
        $this->assertNotNull($grant);
        $this->assertNotNull($grant->revoked_at);
    }

    public function test_admin_can_change_role(): void
    {
        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();
        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');

        $response = $this->withHeaders($this->authHeaders($token))
            ->putJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}/role", [
                'role' => 'admin',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['role' => 'admin']);
    }

    public function test_admin_can_offboard_employee(): void
    {
        Notification::fake();

        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();
        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}/offboard", [
                'reason' => 'Leaving the company',
            ]);

        $response->assertStatus(204);
        $pivot = $member->tenants()->where('tenants.id', $tenant->id)->first()->pivot;
        $this->assertSame('left', $pivot->status);
        $this->assertNotNull($pivot->left_at);
    }

    public function test_offboarding_revokes_all_access(): void
    {
        Notification::fake();

        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();
        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');

        $item = VaultItem::create([
            'tenant_id' => $tenant->id, 'team_id' => null, 'user_id' => $admin->id,
            'name' => 'Shared', 'type' => 'password', 'username' => 'u', 'password' => 'p',
        ]);
        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => VaultItem::class,
            'grantable_id' => $item->id,
            'subject_type' => User::class,
            'subject_id' => $member->id,
            'permission' => 'view',
            'granted_by' => $admin->id,
            'granted_at' => now(),
        ]);

        $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}/offboard");

        $grant = AccessGrant::where('subject_id', $member->id)->first();
        $this->assertNotNull($grant->revoked_at);
    }

    public function test_offboarding_removes_from_teams(): void
    {
        Notification::fake();

        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();
        $team = Team::create([
            'tenant_id' => $tenant->id, 'name' => 'Eng',
            'created_by' => $admin->id,
        ]);
        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');
        $member->teams()->attach($team->id, ['role' => 'member', 'joined_at' => now()]);

        $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}/offboard");

        $this->assertFalse($member->teams()->where('teams.id', $team->id)->exists());
    }

    public function test_offboarding_revokes_tokens(): void
    {
        Notification::fake();

        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();
        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');
        $memberToken = $member->createToken('member-token');
        DB::table('personal_access_tokens')
            ->where('id', $memberToken->accessToken->id)
            ->update(['tenant_id' => $tenant->id]);

        $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}/offboard");

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $memberToken->accessToken->id,
        ]);
    }

    public function test_offboarding_revokes_secure_links(): void
    {
        Notification::fake();

        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();
        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');

        $item = VaultItem::create([
            'tenant_id' => $tenant->id, 'team_id' => null, 'user_id' => $member->id,
            'name' => 'Link', 'type' => 'password', 'username' => 'u', 'password' => 'p',
        ]);
        $link = SecureLink::create([
            'tenant_id' => $tenant->id,
            'uuid' => Str::uuid()->toString(),
            'resource_type' => VaultItem::class,
            'resource_id' => $item->id,
            'created_by' => $member->id,
            'permission' => 'view',
            'download_enabled' => true,
            'views_count' => 0,
            'is_one_time' => false,
        ]);

        $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}/offboard");

        $link->refresh();
        $this->assertNotNull($link->revoked_at);
        $this->assertSame('offboarding', $link->revoke_reason);
    }

    public function test_offboarded_user_cannot_access_tenant(): void
    {
        Notification::fake();

        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();
        $member = $this->createUser(['email' => 'member@example.com', 'password' => bcrypt('password')]);
        $this->attachUserToTenant($member, $tenant, 'member');
        $memberTokenResult = $member->createToken('member-token');
        DB::table('personal_access_tokens')
            ->where('id', $memberTokenResult->accessToken->id)
            ->update(['tenant_id' => $tenant->id]);

        $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}/offboard");

        // The member's membership status should be 'left'
        $member->refresh();
        $pivot = $member->tenants()->where('tenants.id', $tenant->id)->first()->pivot;
        $this->assertSame('left', $pivot->status);
        $this->assertNotNull($pivot->left_at);

        // The member should no longer pass isMemberOf (used by gates and middleware)
        $this->assertFalse($member->isMemberOf($tenant));

        // The member's token for this tenant should be deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $memberTokenResult->accessToken->id,
        ]);

        // The member should not be listed in the tenant's active members
        $activeMembers = $tenant->users()->wherePivotNull('left_at')->get();
        $this->assertFalse($activeMembers->contains('id', $member->id));
    }

    public function test_activity_history_preserved(): void
    {
        Notification::fake();

        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();
        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');

        // Create some activity for the member
        ActivityLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $member->id,
            'action' => 'vault_item.viewed',
        ]);

        $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}/offboard");

        // Activity logs should still exist
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $member->id,
            'action' => 'vault_item.viewed',
        ]);
    }

    public function test_offboarded_user_notified(): void
    {
        Notification::fake();

        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();
        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');

        $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}/offboard", [
                'reason' => 'Company restructure',
            ]);

        Notification::assertSentTo($member, EmployeeOffboardedNotification::class);
    }

    public function test_non_admin_cannot_offboard(): void
    {
        [$tenant, $admin, $adminToken] = $this->setupTenantWithAdmin();
        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');
        $memberToken = $member->createToken('member-token')->plainTextToken;

        $target = $this->createUser(['email' => 'target@example.com']);
        $this->attachUserToTenant($target, $tenant, 'member');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$memberToken,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->postJson("/api/v1/tenants/{$tenant->id}/members/{$target->id}/offboard");

        $response->assertStatus(403);
    }
}
