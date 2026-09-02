<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Access;

use App\Enums\Permission;
use App\Models\AccessGrant;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VaultItem;
use App\Services\AccessResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AuthHelper;
use Tests\Helpers\TenantHelper;
use Tests\TestCase;

class AccessGrantTest extends TestCase
{
    use AuthHelper, RefreshDatabase, TenantHelper;

    private function createVaultItem(Tenant $tenant, User $owner): VaultItem
    {
        $this->setupTenantContext($tenant);

        return VaultItem::create([
            'tenant_id' => $tenant->id,
            'team_id' => null,
            'user_id' => $owner->id,
            'name' => 'Test Item',
            'type' => 'password',
            'username' => 'user',
            'password' => 'pass',
        ]);
    }

    private function createGrant(Model $resource, string $subjectType, int $subjectId, Permission $permission, User $grantedBy, Tenant $tenant, array $extra = []): AccessGrant
    {
        $this->setupTenantContext($tenant);

        return AccessGrant::create(array_merge([
            'tenant_id' => $tenant->id,
            'grantable_type' => $resource::class,
            'grantable_id' => $resource->id,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'permission' => $permission,
            'granted_by' => $grantedBy->id,
            'views_count' => 0,
        ], $extra));
    }

    public function test_owner_has_full_access(): void
    {
        $owner = $this->createUser(['email' => 'owner@example.com']);
        $tenant = $this->createTenant();
        $this->attachUserToTenant($owner, $tenant, 'admin');
        $item = $this->createVaultItem($tenant, $owner);

        $resolver = app(AccessResolver::class);
        $this->setupTenantContext($tenant);

        $this->assertTrue($resolver->can($owner, Permission::View, $item));
        $this->assertTrue($resolver->can($owner, Permission::Edit, $item));
        $this->assertTrue($resolver->can($owner, Permission::Manage, $item));
        $this->assertEquals(Permission::Manage, $resolver->getPermission($owner, $item));
    }

    public function test_direct_grant_works(): void
    {
        $owner = $this->createUser(['email' => 'owner@example.com']);
        $viewer = $this->createUser(['email' => 'viewer@example.com']);
        $tenant = $this->createTenant();
        $this->attachUserToTenant($owner, $tenant, 'admin');
        $this->attachUserToTenant($viewer, $tenant, 'member');
        $item = $this->createVaultItem($tenant, $owner);

        $this->createGrant($item, User::class, $viewer->id, Permission::View, $owner, $tenant);

        $resolver = app(AccessResolver::class);
        $this->setupTenantContext($tenant);

        $this->assertTrue($resolver->can($viewer, Permission::View, $item));
        $this->assertFalse($resolver->can($viewer, Permission::Edit, $item));
    }

    public function test_team_grant_works(): void
    {
        $owner = $this->createUser(['email' => 'owner@example.com']);
        $member = $this->createUser(['email' => 'member@example.com']);
        $tenant = $this->createTenant();
        $this->attachUserToTenant($owner, $tenant, 'admin');
        $this->attachUserToTenant($member, $tenant, 'member');

        $this->setupTenantContext($tenant);
        $team = Team::create(['tenant_id' => $tenant->id, 'name' => 'Eng', 'created_by' => $owner->id]);
        $team->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $item = $this->createVaultItem($tenant, $owner);
        $this->createGrant($item, Team::class, $team->id, Permission::View, $owner, $tenant);

        $resolver = app(AccessResolver::class);
        $this->setupTenantContext($tenant);

        $this->assertTrue($resolver->can($member, Permission::View, $item));
    }

    public function test_tenant_grant_works(): void
    {
        $owner = $this->createUser(['email' => 'owner@example.com']);
        $member = $this->createUser(['email' => 'member@example.com']);
        $tenant = $this->createTenant();
        $this->attachUserToTenant($owner, $tenant, 'admin');
        $this->attachUserToTenant($member, $tenant, 'member');
        $item = $this->createVaultItem($tenant, $owner);

        $this->createGrant($item, Tenant::class, $tenant->id, Permission::View, $owner, $tenant);

        $resolver = app(AccessResolver::class);
        $this->setupTenantContext($tenant);

        $this->assertTrue($resolver->can($member, Permission::View, $item));
    }

    public function test_expired_grant_denied(): void
    {
        $owner = $this->createUser(['email' => 'owner@example.com']);
        $viewer = $this->createUser(['email' => 'viewer@example.com']);
        $tenant = $this->createTenant();
        $this->attachUserToTenant($owner, $tenant, 'admin');
        $this->attachUserToTenant($viewer, $tenant, 'member');
        $item = $this->createVaultItem($tenant, $owner);

        $this->createGrant($item, User::class, $viewer->id, Permission::View, $owner, $tenant, [
            'expires_at' => now()->subHour(),
        ]);

        $resolver = app(AccessResolver::class);
        $this->setupTenantContext($tenant);

        $this->assertFalse($resolver->can($viewer, Permission::View, $item));
    }

    public function test_revoked_grant_denied(): void
    {
        $owner = $this->createUser(['email' => 'owner@example.com']);
        $viewer = $this->createUser(['email' => 'viewer@example.com']);
        $tenant = $this->createTenant();
        $this->attachUserToTenant($owner, $tenant, 'admin');
        $this->attachUserToTenant($viewer, $tenant, 'member');
        $item = $this->createVaultItem($tenant, $owner);

        $this->createGrant($item, User::class, $viewer->id, Permission::View, $owner, $tenant, [
            'revoked_at' => now(),
            'revoked_by' => $owner->id,
        ]);

        $resolver = app(AccessResolver::class);
        $this->setupTenantContext($tenant);

        $this->assertFalse($resolver->can($viewer, Permission::View, $item));
    }

    public function test_view_limit_enforced(): void
    {
        $owner = $this->createUser(['email' => 'owner@example.com']);
        $viewer = $this->createUser(['email' => 'viewer@example.com']);
        $tenant = $this->createTenant();
        $this->attachUserToTenant($owner, $tenant, 'admin');
        $this->attachUserToTenant($viewer, $tenant, 'member');
        $item = $this->createVaultItem($tenant, $owner);

        $this->createGrant($item, User::class, $viewer->id, Permission::View, $owner, $tenant, [
            'max_views' => 3,
            'views_count' => 3,
        ]);

        $resolver = app(AccessResolver::class);
        $this->setupTenantContext($tenant);

        $this->assertFalse($resolver->can($viewer, Permission::View, $item));
    }

    public function test_start_time_respected(): void
    {
        $owner = $this->createUser(['email' => 'owner@example.com']);
        $viewer = $this->createUser(['email' => 'viewer@example.com']);
        $tenant = $this->createTenant();
        $this->attachUserToTenant($owner, $tenant, 'admin');
        $this->attachUserToTenant($viewer, $tenant, 'member');
        $item = $this->createVaultItem($tenant, $owner);

        $this->createGrant($item, User::class, $viewer->id, Permission::View, $owner, $tenant, [
            'starts_at' => now()->addDay(),
        ]);

        $resolver = app(AccessResolver::class);
        $this->setupTenantContext($tenant);

        $this->assertFalse($resolver->can($viewer, Permission::View, $item));
    }

    public function test_start_on_first_view(): void
    {
        $owner = $this->createUser(['email' => 'owner@example.com']);
        $viewer = $this->createUser(['email' => 'viewer@example.com']);
        $tenant = $this->createTenant();
        $this->attachUserToTenant($owner, $tenant, 'admin');
        $this->attachUserToTenant($viewer, $tenant, 'member');
        $item = $this->createVaultItem($tenant, $owner);

        // Grant with start_on_first_view=true, no first_viewed_at yet → not started
        $this->createGrant($item, User::class, $viewer->id, Permission::View, $owner, $tenant, [
            'start_on_first_view' => true,
            'first_viewed_at' => null,
        ]);

        $resolver = app(AccessResolver::class);
        $this->setupTenantContext($tenant);

        $this->assertFalse($resolver->can($viewer, Permission::View, $item));
    }

    public function test_permission_hierarchy(): void
    {
        $this->assertTrue(Permission::Manage->satisfies(Permission::View));
        $this->assertTrue(Permission::Manage->satisfies(Permission::Edit));
        $this->assertTrue(Permission::Manage->satisfies(Permission::Share));
        $this->assertTrue(Permission::Manage->satisfies(Permission::Download));
        $this->assertTrue(Permission::Share->satisfies(Permission::View));
        $this->assertTrue(Permission::Edit->satisfies(Permission::View));
        $this->assertTrue(Permission::Download->satisfies(Permission::View));
        $this->assertFalse(Permission::View->satisfies(Permission::Edit));
        $this->assertFalse(Permission::View->satisfies(Permission::Manage));
    }

    public function test_download_and_edit_are_parallel(): void
    {
        $this->assertFalse(Permission::Download->satisfies(Permission::Edit));
        $this->assertFalse(Permission::Edit->satisfies(Permission::Download));
    }

    public function test_highest_permission_wins(): void
    {
        $owner = $this->createUser(['email' => 'owner@example.com']);
        $viewer = $this->createUser(['email' => 'viewer@example.com']);
        $tenant = $this->createTenant();
        $this->attachUserToTenant($owner, $tenant, 'admin');
        $this->attachUserToTenant($viewer, $tenant, 'member');
        $item = $this->createVaultItem($tenant, $owner);

        // Direct grant: view
        $this->createGrant($item, User::class, $viewer->id, Permission::View, $owner, $tenant);
        // Tenant-wide grant: edit
        $this->createGrant($item, Tenant::class, $tenant->id, Permission::Edit, $owner, $tenant);

        $resolver = app(AccessResolver::class);
        $this->setupTenantContext($tenant);

        $permission = $resolver->getPermission($viewer, $item);
        $this->assertNotNull($permission);
        $this->assertEquals(Permission::Edit, $permission);
        $this->assertTrue($resolver->can($viewer, Permission::Edit, $item));
    }

    public function test_who_has_access_returns_all_grants(): void
    {
        $owner = $this->createUser(['email' => 'owner@example.com']);
        $viewer = $this->createUser(['email' => 'viewer@example.com']);
        $tenant = $this->createTenant();
        $this->attachUserToTenant($owner, $tenant, 'admin');
        $this->attachUserToTenant($viewer, $tenant, 'member');
        $item = $this->createVaultItem($tenant, $owner);

        $this->createGrant($item, User::class, $viewer->id, Permission::View, $owner, $tenant);
        $this->createGrant($item, Tenant::class, $tenant->id, Permission::View, $owner, $tenant);

        $resolver = app(AccessResolver::class);
        $this->setupTenantContext($tenant);

        $grants = $resolver->whoHasAccess($item);
        $this->assertCount(2, $grants);
    }

    public function test_revoked_grants_excluded_from_list(): void
    {
        $owner = $this->createUser(['email' => 'owner@example.com']);
        $viewer = $this->createUser(['email' => 'viewer@example.com']);
        $tenant = $this->createTenant();
        $this->attachUserToTenant($owner, $tenant, 'admin');
        $this->attachUserToTenant($viewer, $tenant, 'member');
        $item = $this->createVaultItem($tenant, $owner);

        // Active grant
        $this->createGrant($item, User::class, $viewer->id, Permission::View, $owner, $tenant);
        // Revoked grant
        $this->createGrant($item, Tenant::class, $tenant->id, Permission::View, $owner, $tenant, [
            'revoked_at' => now(),
            'revoked_by' => $owner->id,
        ]);

        $resolver = app(AccessResolver::class);
        $this->setupTenantContext($tenant);

        $grants = $resolver->whoHasAccess($item);
        $this->assertCount(1, $grants);
        $this->assertEquals(User::class, $grants->first()->subject_type);
    }

    public function test_tenant_scope_isolates_grants(): void
    {
        $ownerA = $this->createUser(['email' => 'a@example.com']);
        $ownerB = $this->createUser(['email' => 'b@example.com']);
        $tenantA = $this->createTenant(['name' => 'A', 'slug' => 'a']);
        $tenantB = $this->createTenant(['name' => 'B', 'slug' => 'b']);
        $this->attachUserToTenant($ownerA, $tenantA, 'admin');
        $this->attachUserToTenant($ownerB, $tenantB, 'admin');

        $itemA = $this->createVaultItem($tenantA, $ownerA);
        $itemB = $this->createVaultItem($tenantB, $ownerB);

        // Grant in tenant A
        $this->createGrant($itemA, Tenant::class, $tenantA->id, Permission::View, $ownerA, $tenantA);
        // Grant in tenant B
        $this->createGrant($itemB, Tenant::class, $tenantB->id, Permission::View, $ownerB, $tenantB);

        $resolver = app(AccessResolver::class);
        $this->setupTenantContext($tenantA);

        // Tenant A resolver should only see tenant A's grants
        $grantsA = $resolver->whoHasAccess($itemA);
        $this->assertCount(1, $grantsA);

        // Tenant A resolver should not see tenant B's grants for itemB
        $grantsB = $resolver->whoHasAccess($itemB);
        $this->assertCount(0, $grantsB);
    }
}
