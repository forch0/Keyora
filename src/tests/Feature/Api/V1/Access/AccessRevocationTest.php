<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Access;

use App\Actions\OffboardEmployeeAction;
use App\Models\AccessGrant;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VaultItem;
use App\Notifications\AccessRevokedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Helpers\AuthHelper;
use Tests\Helpers\TenantHelper;
use Tests\TestCase;

class AccessRevocationTest extends TestCase
{
    use AuthHelper, RefreshDatabase, TenantHelper;

    private function setupTenantAndUsers(): array
    {
        $tenant = $this->createTenant();
        $admin = $this->createUser(['email' => 'admin@example.com']);
        $this->attachUserToTenant($admin, $tenant, 'admin');
        $this->setupTenantContext($tenant);
        $adminToken = $admin->createToken('test')->plainTextToken;

        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');
        $memberToken = $member->createToken('test')->plainTextToken;

        return [$tenant, $admin, $adminToken, $member, $memberToken];
    }

    private function authHeaders(string $token, int $tenantId): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenantId,
        ];
    }

    private function createVaultItem(Tenant $tenant, User $owner): VaultItem
    {
        return VaultItem::create([
            'tenant_id' => $tenant->id,
            'team_id' => null,
            'user_id' => $owner->id,
            'name' => 'Shared Item',
            'type' => 'password',
            'username' => 'user',
            'password' => 'pass',
        ]);
    }

    private function createGrant(Tenant $tenant, VaultItem $item, User $subject, User $grantedBy, string $permission = 'view'): AccessGrant
    {
        return AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => VaultItem::class,
            'grantable_id' => $item->id,
            'subject_type' => User::class,
            'subject_id' => $subject->id,
            'permission' => $permission,
            'views_count' => 0,
            'granted_by' => $grantedBy->id,
            'granted_at' => now(),
        ]);
    }

    public function test_revoke_individual_access(): void
    {
        [$tenant, $admin, $adminToken, $member] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $admin);
        $grant = $this->createGrant($tenant, $item, $member, $admin);

        $response = $this->withHeaders($this->authHeaders($adminToken, $tenant->id))
            ->deleteJson("/api/v1/vault/items/{$item->id}/access/{$grant->id}");

        $response->assertStatus(204);
        $grant->refresh();
        $this->assertNotNull($grant->revoked_at);
    }

    public function test_revoke_team_access_for_resource(): void
    {
        [$tenant, $admin, $adminToken, $member] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $admin);
        $team = Team::create(['tenant_id' => $tenant->id, 'name' => 'Eng', 'created_by' => $admin->id]);

        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => VaultItem::class,
            'grantable_id' => $item->id,
            'subject_type' => 'App\\Models\\Team',
            'subject_id' => $team->id,
            'permission' => 'view',
            'views_count' => 0,
            'granted_by' => $admin->id,
            'granted_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($adminToken, $tenant->id))
            ->postJson("/api/v1/vault/items/{$item->id}/access/revoke-team/{$team->id}");

        $response->assertStatus(200);
        $this->assertSame(1, $response->json('data.revoked_count'));
        $this->assertDatabaseHas('access_grants', [
            'grantable_id' => $item->id,
            'subject_type' => 'App\\Models\\Team',
            'subject_id' => $team->id,
            'revoke_reason' => 'manual',
        ]);
        $this->assertNotNull(AccessGrant::where('subject_id', $team->id)->first()->revoked_at);
    }

    public function test_revoke_all_for_resource(): void
    {
        [$tenant, $admin, $adminToken, $member] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $admin);

        $this->createGrant($tenant, $item, $member, $admin);
        $member2 = $this->createUser(['email' => 'member2@example.com']);
        $this->attachUserToTenant($member2, $tenant, 'member');
        $this->createGrant($tenant, $item, $member2, $admin);

        $response = $this->withHeaders($this->authHeaders($adminToken, $tenant->id))
            ->postJson("/api/v1/vault/items/{$item->id}/access/revoke-all");

        $response->assertStatus(200);
        $this->assertSame(2, $response->json('data.revoked_count'));
        $this->assertSame(0, AccessGrant::where('grantable_id', $item->id)->whereNull('revoked_at')->count());
    }

    public function test_emergency_revoke_removes_all_user_grants(): void
    {
        [$tenant, $admin, $adminToken, $member] = $this->setupTenantAndUsers();
        $item1 = $this->createVaultItem($tenant, $admin);
        $item2 = VaultItem::create([
            'tenant_id' => $tenant->id, 'team_id' => null, 'user_id' => $admin->id,
            'name' => 'Item 2', 'type' => 'password', 'username' => 'u', 'password' => 'p',
        ]);

        $this->createGrant($tenant, $item1, $member, $admin);
        $this->createGrant($tenant, $item2, $member, $admin);

        $response = $this->withHeaders($this->authHeaders($adminToken, $tenant->id))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}/revoke-all");

        $response->assertStatus(200);
        $this->assertSame(2, $response->json('data.revoked_count'));
        $this->assertSame(0, AccessGrant::where('subject_id', $member->id)->whereNull('revoked_at')->count());
    }

    public function test_emergency_revoke_removes_team_grants(): void
    {
        [$tenant, $admin, $adminToken, $member] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $admin);
        $team = Team::create(['tenant_id' => $tenant->id, 'name' => 'Eng', 'created_by' => $admin->id]);
        $member->teams()->attach($team->id);

        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => VaultItem::class,
            'grantable_id' => $item->id,
            'subject_type' => 'App\\Models\\Team',
            'subject_id' => $team->id,
            'permission' => 'view',
            'views_count' => 0,
            'granted_by' => $admin->id,
            'granted_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($adminToken, $tenant->id))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}/revoke-all");

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $response->json('data.revoked_count'));
        $this->assertNotNull(AccessGrant::where('subject_id', $team->id)->first()->revoked_at);
    }

    public function test_emergency_revoke_returns_count(): void
    {
        [$tenant, $admin, $adminToken, $member] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $admin);

        $this->createGrant($tenant, $item, $member, $admin);

        $response = $this->withHeaders($this->authHeaders($adminToken, $tenant->id))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}/revoke-all");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['user_id', 'revoked_count', 'reason']]);
    }

    public function test_offboarding_auto_revokes(): void
    {
        [$tenant, $admin, $adminToken, $member] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $admin);
        $this->createGrant($tenant, $item, $member, $admin);

        $action = app(OffboardEmployeeAction::class);
        $count = $action($admin, $member, $tenant);

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertSame(0, AccessGrant::where('subject_id', $member->id)->whereNull('revoked_at')->count());
        $this->assertFalse($tenant->users()->where('users.id', $member->id)->exists());
    }

    public function test_revoked_grants_have_reason(): void
    {
        [$tenant, $admin, $adminToken, $member] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $admin);
        $this->createGrant($tenant, $item, $member, $admin);

        $this->withHeaders($this->authHeaders($adminToken, $tenant->id))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}/revoke-all", [
                'reason' => 'security_incident',
            ]);

        $grant = AccessGrant::where('subject_id', $member->id)->first();
        $this->assertSame('security_incident', $grant->revoke_reason);
    }

    public function test_revoked_grants_not_hard_deleted(): void
    {
        [$tenant, $admin, $adminToken, $member] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $admin);
        $grant = $this->createGrant($tenant, $item, $member, $admin);

        $this->withHeaders($this->authHeaders($adminToken, $tenant->id))
            ->deleteJson("/api/v1/vault/items/{$item->id}/access/{$grant->id}");

        $this->assertDatabaseHas('access_grants', ['id' => $grant->id]);
    }

    public function test_user_notified_on_revocation(): void
    {
        Notification::fake();

        [$tenant, $admin, $adminToken, $member] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $admin);
        $this->createGrant($tenant, $item, $member, $admin);

        $this->withHeaders($this->authHeaders($adminToken, $tenant->id))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}/revoke-all");

        Notification::assertSentTo($member, AccessRevokedNotification::class);
    }

    public function test_non_admin_cannot_emergency_revoke(): void
    {
        [$tenant, $admin, $adminToken, $member, $memberToken] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $admin);
        $this->createGrant($tenant, $item, $member, $admin);

        $response = $this->withHeaders($this->authHeaders($memberToken, $tenant->id))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/{$admin->id}/revoke-all");

        $response->assertStatus(403);
    }

    public function test_revoke_all_requires_manage_permission(): void
    {
        [$tenant, $admin, $adminToken, $member, $memberToken] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $admin);

        // Give member view access (not manage)
        $this->createGrant($tenant, $item, $member, $admin, 'view');

        $response = $this->withHeaders($this->authHeaders($memberToken, $tenant->id))
            ->postJson("/api/v1/vault/items/{$item->id}/access/revoke-all");

        $response->assertStatus(403);
    }
}
