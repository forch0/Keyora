<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Access;

use App\Events\AccessGranted;
use App\Events\AccessRevoked;
use App\Models\AccessGrant;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VaultItem;
use App\Notifications\AccessGrantedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\Helpers\AuthHelper;
use Tests\Helpers\TenantHelper;
use Tests\TestCase;

class ShareAccessTest extends TestCase
{
    use AuthHelper, RefreshDatabase, TenantHelper;

    private function setupItemAndOwner(Tenant $tenant): array
    {
        $owner = $this->createUser(['email' => 'owner@example.com']);
        $this->attachUserToTenant($owner, $tenant, 'admin');
        $this->setupTenantContext($tenant);

        $item = VaultItem::create([
            'tenant_id' => $tenant->id,
            'team_id' => null,
            'user_id' => $owner->id,
            'name' => 'Shared Item',
            'type' => 'password',
            'username' => 'user',
            'password' => 'pass',
        ]);

        $token = $owner->createToken('test')->plainTextToken;

        return [$owner, $item, $token];
    }

    public function test_user_can_share_with_individual(): void
    {
        $tenant = $this->createTenant();
        [$owner, $item, $token] = $this->setupItemAndOwner($tenant);
        $recipient = $this->createUser(['email' => 'recipient@example.com']);
        $this->attachUserToTenant($recipient, $tenant, 'member');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->postJson('/api/v1/vault/items/'.$item->id.'/access', [
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
            'permission' => 'view',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['permission' => 'view']);
        $this->assertDatabaseHas('access_grants', [
            'grantable_type' => VaultItem::class,
            'grantable_id' => $item->id,
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
        ]);
    }

    public function test_user_can_share_with_team(): void
    {
        $tenant = $this->createTenant();
        [$owner, $item, $token] = $this->setupItemAndOwner($tenant);
        $this->setupTenantContext($tenant);
        $team = Team::create(['tenant_id' => $tenant->id, 'name' => 'Eng', 'created_by' => $owner->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->postJson('/api/v1/vault/items/'.$item->id.'/access', [
            'subject_type' => Team::class,
            'subject_id' => $team->id,
            'permission' => 'edit',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['permission' => 'edit']);
    }

    public function test_user_can_share_with_company(): void
    {
        $tenant = $this->createTenant();
        [$owner, $item, $token] = $this->setupItemAndOwner($tenant);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->postJson('/api/v1/vault/items/'.$item->id.'/access', [
            'subject_type' => Tenant::class,
            'subject_id' => $tenant->id,
            'permission' => 'view',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['permission' => 'view']);
    }

    public function test_user_can_bulk_share_with_teams(): void
    {
        $tenant = $this->createTenant();
        [$owner, $item, $token] = $this->setupItemAndOwner($tenant);
        $this->setupTenantContext($tenant);
        $team1 = Team::create(['tenant_id' => $tenant->id, 'name' => 'Eng', 'created_by' => $owner->id]);
        $team2 = Team::create(['tenant_id' => $tenant->id, 'name' => 'Sales', 'created_by' => $owner->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->postJson('/api/v1/vault/items/'.$item->id.'/access/bulk', [
            'team_ids' => [$team1->id, $team2->id],
            'permission' => 'view',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('access_grants', [
            'grantable_id' => $item->id,
            'subject_type' => Team::class,
            'subject_id' => $team1->id,
        ]);
        $this->assertDatabaseHas('access_grants', [
            'grantable_id' => $item->id,
            'subject_type' => Team::class,
            'subject_id' => $team2->id,
        ]);
    }

    public function test_user_can_change_permission(): void
    {
        $tenant = $this->createTenant();
        [$owner, $item, $token] = $this->setupItemAndOwner($tenant);
        $recipient = $this->createUser(['email' => 'recipient@example.com']);
        $this->attachUserToTenant($recipient, $tenant, 'member');

        $this->setupTenantContext($tenant);
        $grant = AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => VaultItem::class,
            'grantable_id' => $item->id,
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
            'permission' => 'view',
            'granted_by' => $owner->id,
            'views_count' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->putJson('/api/v1/vault/items/'.$item->id.'/access/'.$grant->id, [
            'permission' => 'edit',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['permission' => 'edit']);
        $this->assertDatabaseHas('access_grants', [
            'id' => $grant->id,
            'permission' => 'edit',
        ]);
    }

    public function test_user_can_revoke_access(): void
    {
        $tenant = $this->createTenant();
        [$owner, $item, $token] = $this->setupItemAndOwner($tenant);
        $recipient = $this->createUser(['email' => 'recipient@example.com']);
        $this->attachUserToTenant($recipient, $tenant, 'member');

        $this->setupTenantContext($tenant);
        $grant = AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => VaultItem::class,
            'grantable_id' => $item->id,
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
            'permission' => 'view',
            'granted_by' => $owner->id,
            'views_count' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->deleteJson('/api/v1/vault/items/'.$item->id.'/access/'.$grant->id);

        $response->assertStatus(204);
        $this->assertDatabaseHas('access_grants', [
            'id' => $grant->id,
        ]);
        $grant->refresh();
        $this->assertNotNull($grant->revoked_at);
    }

    public function test_user_without_share_permission_cannot_grant(): void
    {
        $tenant = $this->createTenant();
        [$owner, $item] = $this->setupItemAndOwner($tenant);
        $viewer = $this->createUser(['email' => 'viewer@example.com']);
        $this->attachUserToTenant($viewer, $tenant, 'member');
        $viewerToken = $viewer->createToken('test')->plainTextToken;
        $recipient = $this->createUser(['email' => 'recipient@example.com']);
        $this->attachUserToTenant($recipient, $tenant, 'member');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$viewerToken,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->postJson('/api/v1/vault/items/'.$item->id.'/access', [
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
            'permission' => 'view',
        ]);

        $response->assertStatus(403);
    }

    public function test_cannot_grant_to_non_tenant_member(): void
    {
        $tenant = $this->createTenant();
        [$owner, $item, $token] = $this->setupItemAndOwner($tenant);
        $outsider = $this->createUser(['email' => 'outsider@example.com']);
        // Outsider is NOT attached to the tenant

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->postJson('/api/v1/vault/items/'.$item->id.'/access', [
            'subject_type' => User::class,
            'subject_id' => $outsider->id,
            'permission' => 'view',
        ]);

        $response->assertStatus(422);
    }

    public function test_duplicate_grant_updates_existing(): void
    {
        $tenant = $this->createTenant();
        [$owner, $item, $token] = $this->setupItemAndOwner($tenant);
        $recipient = $this->createUser(['email' => 'recipient@example.com']);
        $this->attachUserToTenant($recipient, $tenant, 'member');

        // First grant
        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->postJson('/api/v1/vault/items/'.$item->id.'/access', [
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
            'permission' => 'view',
        ])->assertStatus(201);

        // Second grant — should update, not create new
        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->postJson('/api/v1/vault/items/'.$item->id.'/access', [
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
            'permission' => 'edit',
        ])->assertStatus(201);

        // Should have only 1 grant, with 'edit' permission
        $this->assertEquals(1, AccessGrant::withoutTenant()->where('grantable_id', $item->id)->count());
        $this->assertDatabaseHas('access_grants', [
            'grantable_id' => $item->id,
            'subject_id' => $recipient->id,
            'permission' => 'edit',
        ]);
    }

    public function test_grantee_receives_notification(): void
    {
        Notification::fake();

        $tenant = $this->createTenant();
        [$owner, $item, $token] = $this->setupItemAndOwner($tenant);
        $recipient = $this->createUser(['email' => 'recipient@example.com']);
        $this->attachUserToTenant($recipient, $tenant, 'member');

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->postJson('/api/v1/vault/items/'.$item->id.'/access', [
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
            'permission' => 'view',
        ])->assertStatus(201);

        Notification::assertSentTo($recipient, AccessGrantedNotification::class);
    }

    public function test_revoked_grant_appears_in_history(): void
    {
        $tenant = $this->createTenant();
        [$owner, $item, $token] = $this->setupItemAndOwner($tenant);
        $recipient = $this->createUser(['email' => 'recipient@example.com']);
        $this->attachUserToTenant($recipient, $tenant, 'member');

        $this->setupTenantContext($tenant);
        $grant = AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => VaultItem::class,
            'grantable_id' => $item->id,
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
            'permission' => 'view',
            'granted_by' => $owner->id,
            'views_count' => 0,
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->deleteJson('/api/v1/vault/items/'.$item->id.'/access/'.$grant->id)
            ->assertStatus(204);

        // Grant still exists in DB (soft delete via revoked_at)
        $this->assertDatabaseHas('access_grants', ['id' => $grant->id]);
        $grant->refresh();
        $this->assertNotNull($grant->revoked_at);
        $this->assertNotNull($grant->revoked_by);
    }

    public function test_events_dispatched_on_grant(): void
    {
        Event::fake([AccessGranted::class]);

        $tenant = $this->createTenant();
        [$owner, $item, $token] = $this->setupItemAndOwner($tenant);
        $recipient = $this->createUser(['email' => 'recipient@example.com']);
        $this->attachUserToTenant($recipient, $tenant, 'member');

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->postJson('/api/v1/vault/items/'.$item->id.'/access', [
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
            'permission' => 'view',
        ])->assertStatus(201);

        Event::assertDispatched(AccessGranted::class);
    }

    public function test_events_dispatched_on_revoke(): void
    {
        Event::fake([AccessRevoked::class]);

        $tenant = $this->createTenant();
        [$owner, $item, $token] = $this->setupItemAndOwner($tenant);
        $recipient = $this->createUser(['email' => 'recipient@example.com']);
        $this->attachUserToTenant($recipient, $tenant, 'member');

        $this->setupTenantContext($tenant);
        $grant = AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => VaultItem::class,
            'grantable_id' => $item->id,
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
            'permission' => 'view',
            'granted_by' => $owner->id,
            'views_count' => 0,
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->deleteJson('/api/v1/vault/items/'.$item->id.'/access/'.$grant->id)
            ->assertStatus(204);

        Event::assertDispatched(AccessRevoked::class);
    }
}
