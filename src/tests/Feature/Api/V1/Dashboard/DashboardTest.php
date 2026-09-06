<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Dashboard;

use App\Models\AccessGrant;
use App\Models\AccessRequest;
use App\Models\ActivityLog;
use App\Models\PersonalVaultItem;
use App\Models\SecurityAlert;
use App\Models\Team;
use App\Models\User;
use App\Models\VaultItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AuthHelper;
use Tests\Helpers\TenantHelper;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use AuthHelper, RefreshDatabase, TenantHelper;

    private function setupTenantWithAdmin(): array
    {
        [$admin, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant(['name' => 'Dash Co', 'slug' => 'dash-co', 'plan' => 'team']);
        $this->attachUserToTenant($admin, $tenant, 'admin');
        $this->setupTenantContext($tenant);

        return [$tenant, $admin, $token];
    }

    // --- Personal Dashboard ---

    public function test_personal_dashboard_returns_summary(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        PersonalVaultItem::create([
            'user_id' => $user->id, 'name' => 'Pass1', 'type' => 'password',
            'username' => 'u', 'password' => 'p',
        ]);
        PersonalVaultItem::create([
            'user_id' => $user->id, 'name' => 'Key1', 'type' => 'api_key',
            'username' => 'u', 'password' => 'p',
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/dashboard/personal');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [
                'vault_summary' => ['total_items', 'by_type', 'favorites_count', 'archived_count'],
                'recently_viewed',
                'recently_added',
                'shared_with_me',
                'expiring_access',
                'pending_requests',
                'security_alerts_unread',
            ]])
            ->assertJsonPath('data.vault_summary.total_items', 2);
    }

    public function test_personal_dashboard_includes_favorites_count(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        PersonalVaultItem::create([
            'user_id' => $user->id, 'name' => 'Fav', 'type' => 'password',
            'username' => 'u', 'password' => 'p', 'favorite' => true,
        ]);
        PersonalVaultItem::create([
            'user_id' => $user->id, 'name' => 'NoFav', 'type' => 'password',
            'username' => 'u', 'password' => 'p', 'favorite' => false,
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/dashboard/personal');

        $response->assertStatus(200)
            ->assertJsonPath('data.vault_summary.favorites_count', 1);
    }

    public function test_personal_dashboard_includes_recently_viewed(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        for ($i = 0; $i < 5; $i++) {
            PersonalVaultItem::create([
                'user_id' => $user->id, 'name' => "Item{$i}", 'type' => 'password',
                'username' => 'u', 'password' => 'p',
                'last_accessed_at' => now()->subMinutes($i),
            ]);
        }

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/dashboard/personal');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data.recently_viewed'));
    }

    public function test_personal_dashboard_includes_shared_with_me(): void
    {
        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();
        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');
        $memberToken = $member->createToken('test')->plainTextToken;

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

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$memberToken,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->getJson('/api/v1/dashboard/personal');

        $response->assertStatus(200)
            ->assertJsonPath('data.shared_with_me.total', 1);
    }

    public function test_personal_dashboard_includes_expiring_access(): void
    {
        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();
        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');
        $memberToken = $member->createToken('test')->plainTextToken;

        $item = VaultItem::create([
            'tenant_id' => $tenant->id, 'team_id' => null, 'user_id' => $admin->id,
            'name' => 'Expiring', 'type' => 'password', 'username' => 'u', 'password' => 'p',
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
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$memberToken,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->getJson('/api/v1/dashboard/personal');

        $response->assertStatus(200)
            ->assertJsonPath('data.expiring_access.count', 1);
    }

    public function test_personal_dashboard_includes_pending_requests(): void
    {
        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();
        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');
        $memberToken = $member->createToken('test')->plainTextToken;

        $item = VaultItem::create([
            'tenant_id' => $tenant->id, 'team_id' => null, 'user_id' => $admin->id,
            'name' => 'Requested', 'type' => 'password', 'username' => 'u', 'password' => 'p',
        ]);
        AccessRequest::create([
            'tenant_id' => $tenant->id,
            'requester_id' => $member->id,
            'resource_type' => VaultItem::class,
            'resource_id' => $item->id,
            'resource_owner_id' => $admin->id,
            'requested_permission' => 'view',
            'reason' => 'Need access',
            'status' => 'pending',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$memberToken,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->getJson('/api/v1/dashboard/personal');

        $response->assertStatus(200)
            ->assertJsonPath('data.pending_requests.sent', 1);
    }

    public function test_personal_dashboard_includes_security_alerts(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        SecurityAlert::create([
            'user_id' => $user->id,
            'type' => SecurityAlert::TYPE_NEW_DEVICE_LOGIN,
            'severity' => SecurityAlert::SEVERITY_INFO,
            'title' => 'New device',
            'message' => 'New login',
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/dashboard/personal');

        $response->assertStatus(200)
            ->assertJsonPath('data.security_alerts_unread', 1);
    }

    // --- Company Dashboard ---

    public function test_company_dashboard_returns_overview(): void
    {
        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();

        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');

        VaultItem::create([
            'tenant_id' => $tenant->id, 'team_id' => null, 'user_id' => $admin->id,
            'name' => 'V1', 'type' => 'password', 'username' => 'u', 'password' => 'p',
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/dashboard/company');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [
                'overview' => ['total_members', 'active_members', 'suspended_members', 'total_teams', 'total_vault_items', 'total_files', 'total_notes'],
                'members',
                'teams',
                'access_requests',
                'temporary_access',
                'security_activity',
                'expiring_access',
                'recent_activity',
            ]])
            ->assertJsonPath('data.overview.total_members', 2)
            ->assertJsonPath('data.overview.active_members', 2)
            ->assertJsonPath('data.overview.total_vault_items', 1);
    }

    public function test_company_dashboard_includes_recent_members(): void
    {
        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();

        $member = $this->createUser(['email' => 'member@example.com', 'name' => 'New Member']);
        $this->attachUserToTenant($member, $tenant, 'member');

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/dashboard/company');

        $response->assertStatus(200);
        $members = $response->json('data.members');
        $this->assertGreaterThanOrEqual(2, count($members));
    }

    public function test_company_dashboard_includes_teams(): void
    {
        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();

        Team::create([
            'tenant_id' => $tenant->id, 'name' => 'Engineering',
            'created_by' => $admin->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/dashboard/company');

        $response->assertStatus(200);
        $teams = $response->json('data.teams');
        $this->assertGreaterThanOrEqual(1, count($teams));
    }

    public function test_company_dashboard_includes_access_requests(): void
    {
        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();
        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');

        $item = VaultItem::create([
            'tenant_id' => $tenant->id, 'team_id' => null, 'user_id' => $admin->id,
            'name' => 'Item', 'type' => 'password', 'username' => 'u', 'password' => 'p',
        ]);
        AccessRequest::create([
            'tenant_id' => $tenant->id,
            'requester_id' => $member->id,
            'resource_type' => VaultItem::class,
            'resource_id' => $item->id,
            'resource_owner_id' => $admin->id,
            'requested_permission' => 'view',
            'reason' => 'Need',
            'status' => 'pending',
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/dashboard/company');

        $response->assertStatus(200)
            ->assertJsonPath('data.access_requests.pending', 1);
    }

    public function test_company_dashboard_includes_activity_feed(): void
    {
        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();

        for ($i = 0; $i < 12; $i++) {
            ActivityLog::create([
                'tenant_id' => $tenant->id,
                'user_id' => $admin->id,
                'action' => 'vault_item.viewed',
            ]);
        }

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/dashboard/company');

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('data.recent_activity'));
    }

    public function test_non_admin_cannot_access_company_dashboard(): void
    {
        [$tenant, $admin, $adminToken] = $this->setupTenantWithAdmin();
        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');
        $memberToken = $member->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$memberToken,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->getJson('/api/v1/dashboard/company');

        $response->assertStatus(403);
    }

    // --- Usage Dashboard ---

    public function test_usage_dashboard_returns_limits(): void
    {
        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/dashboard/usage');

        $response->assertStatus(200)
            ->assertJsonPath('data.plan', 'team')
            ->assertJsonStructure(['data' => [
                'plan',
                'limits' => ['max_members', 'max_storage_mb', 'max_vault_items'],
            ]])
            ->assertJsonPath('data.limits.max_members', 25);
    }

    public function test_usage_dashboard_returns_usage(): void
    {
        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();

        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');

        VaultItem::create([
            'tenant_id' => $tenant->id, 'team_id' => null, 'user_id' => $admin->id,
            'name' => 'V1', 'type' => 'password', 'username' => 'u', 'password' => 'p',
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/dashboard/usage');

        $response->assertStatus(200)
            ->assertJsonPath('data.usage.members', 2)
            ->assertJsonPath('data.usage.vault_items', 1);
    }

    public function test_usage_dashboard_returns_percentages(): void
    {
        [$tenant, $admin, $token] = $this->setupTenantWithAdmin();

        // team plan: max_members = 25
        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/dashboard/usage');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [
                'percentages' => ['members', 'storage', 'vault_items'],
            ]]);

        // 1 admin out of 25 max = 4%
        $response->assertJsonPath('data.percentages.members', 4);
    }

    public function test_non_admin_cannot_access_usage_dashboard(): void
    {
        [$tenant, $admin, $adminToken] = $this->setupTenantWithAdmin();
        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');
        $memberToken = $member->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$memberToken,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->getJson('/api/v1/dashboard/usage');

        $response->assertStatus(403);
    }
}
