<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AccessRequests;

use App\Models\AccessGrant;
use App\Models\AccessRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VaultItem;
use App\Notifications\AccessRequestApprovedNotification;
use App\Notifications\AccessRequestReceived;
use App\Notifications\AccessRequestRejectedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Helpers\AuthHelper;
use Tests\Helpers\TenantHelper;
use Tests\TestCase;

class AccessRequestTest extends TestCase
{
    use AuthHelper, RefreshDatabase, TenantHelper;

    private function setupTenantAndUsers(): array
    {
        $tenant = $this->createTenant();
        $owner = $this->createUser(['email' => 'owner@example.com']);
        $this->attachUserToTenant($owner, $tenant, 'admin');
        $this->setupTenantContext($tenant);
        $ownerToken = $owner->createToken('test')->plainTextToken;

        $requester = $this->createUser(['email' => 'requester@example.com']);
        $this->attachUserToTenant($requester, $tenant, 'member');
        $requesterToken = $requester->createToken('test')->plainTextToken;

        return [$tenant, $owner, $ownerToken, $requester, $requesterToken];
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

    public function test_user_can_request_access(): void
    {
        [$tenant, $owner, $ownerToken, $requester, $requesterToken] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        $response = $this->withHeaders($this->authHeaders($requesterToken, $tenant->id))
            ->postJson('/api/v1/access-requests', [
                'resource_type' => VaultItem::class,
                'resource_id' => $item->id,
                'requested_permission' => 'view',
                'requested_duration' => '1h',
                'reason' => 'I need to view this for a project.',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['status' => 'pending']);
        $this->assertDatabaseHas('access_requests', [
            'requester_id' => $requester->id,
            'resource_owner_id' => $owner->id,
            'resource_type' => VaultItem::class,
            'resource_id' => $item->id,
            'status' => 'pending',
        ]);
    }

    public function test_cannot_request_access_already_have(): void
    {
        [$tenant, $owner, $ownerToken, $requester, $requesterToken] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        // Grant access to requester
        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => VaultItem::class,
            'grantable_id' => $item->id,
            'subject_type' => User::class,
            'subject_id' => $requester->id,
            'permission' => 'view',
            'views_count' => 0,
            'granted_by' => $owner->id,
            'granted_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($requesterToken, $tenant->id))
            ->postJson('/api/v1/access-requests', [
                'resource_type' => VaultItem::class,
                'resource_id' => $item->id,
                'requested_permission' => 'view',
                'reason' => 'I need access.',
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_create_duplicate_pending(): void
    {
        [$tenant, $owner, $ownerToken, $requester, $requesterToken] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        AccessRequest::create([
            'tenant_id' => $tenant->id,
            'requester_id' => $requester->id,
            'resource_type' => VaultItem::class,
            'resource_id' => $item->id,
            'resource_owner_id' => $owner->id,
            'requested_permission' => 'view',
            'reason' => 'First request.',
            'status' => 'pending',
        ]);

        $response = $this->withHeaders($this->authHeaders($requesterToken, $tenant->id))
            ->postJson('/api/v1/access-requests', [
                'resource_type' => VaultItem::class,
                'resource_id' => $item->id,
                'requested_permission' => 'view',
                'reason' => 'Second request.',
            ]);

        $response->assertStatus(422);
    }

    public function test_owner_receives_notification(): void
    {
        Notification::fake();

        [$tenant, $owner, $ownerToken, $requester, $requesterToken] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        $this->withHeaders($this->authHeaders($requesterToken, $tenant->id))
            ->postJson('/api/v1/access-requests', [
                'resource_type' => VaultItem::class,
                'resource_id' => $item->id,
                'requested_permission' => 'view',
                'reason' => 'I need access.',
            ]);

        Notification::assertSentTo($owner, AccessRequestReceived::class);
    }

    public function test_owner_can_approve(): void
    {
        [$tenant, $owner, $ownerToken, $requester, $requesterToken] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        $request = AccessRequest::create([
            'tenant_id' => $tenant->id,
            'requester_id' => $requester->id,
            'resource_type' => VaultItem::class,
            'resource_id' => $item->id,
            'resource_owner_id' => $owner->id,
            'requested_permission' => 'view',
            'reason' => 'Need access.',
            'status' => 'pending',
        ]);

        $response = $this->withHeaders($this->authHeaders($ownerToken, $tenant->id))
            ->putJson("/api/v1/access-requests/{$request->id}/approve");

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'approved']);
        $this->assertDatabaseHas('access_grants', [
            'grantable_type' => VaultItem::class,
            'grantable_id' => $item->id,
            'subject_type' => User::class,
            'subject_id' => $requester->id,
        ]);
    }

    public function test_owner_can_modify_permission_on_approve(): void
    {
        [$tenant, $owner, $ownerToken, $requester, $requesterToken] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        $request = AccessRequest::create([
            'tenant_id' => $tenant->id,
            'requester_id' => $requester->id,
            'resource_type' => VaultItem::class,
            'resource_id' => $item->id,
            'resource_owner_id' => $owner->id,
            'requested_permission' => 'view',
            'reason' => 'Need access.',
            'status' => 'pending',
        ]);

        $response = $this->withHeaders($this->authHeaders($ownerToken, $tenant->id))
            ->putJson("/api/v1/access-requests/{$request->id}/approve", [
                'granted_permission' => 'edit',
            ]);

        $response->assertStatus(200);
        $this->assertSame('edit', $response->json('data.granted_permission'));
    }

    public function test_owner_can_modify_duration_on_approve(): void
    {
        [$tenant, $owner, $ownerToken, $requester, $requesterToken] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        $request = AccessRequest::create([
            'tenant_id' => $tenant->id,
            'requester_id' => $requester->id,
            'resource_type' => VaultItem::class,
            'resource_id' => $item->id,
            'resource_owner_id' => $owner->id,
            'requested_permission' => 'view',
            'requested_duration' => '1h',
            'reason' => 'Need access.',
            'status' => 'pending',
        ]);

        $response = $this->withHeaders($this->authHeaders($ownerToken, $tenant->id))
            ->putJson("/api/v1/access-requests/{$request->id}/approve", [
                'granted_duration' => '24h',
            ]);

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data.granted_expires_at'));
    }

    public function test_owner_can_reject(): void
    {
        [$tenant, $owner, $ownerToken, $requester, $requesterToken] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        $request = AccessRequest::create([
            'tenant_id' => $tenant->id,
            'requester_id' => $requester->id,
            'resource_type' => VaultItem::class,
            'resource_id' => $item->id,
            'resource_owner_id' => $owner->id,
            'requested_permission' => 'view',
            'reason' => 'Need access.',
            'status' => 'pending',
        ]);

        $response = $this->withHeaders($this->authHeaders($ownerToken, $tenant->id))
            ->putJson("/api/v1/access-requests/{$request->id}/reject", [
                'review_note' => 'Access denied.',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'rejected', 'review_note' => 'Access denied.']);
    }

    public function test_requester_notified_on_approval(): void
    {
        Notification::fake();

        [$tenant, $owner, $ownerToken, $requester, $requesterToken] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        $request = AccessRequest::create([
            'tenant_id' => $tenant->id,
            'requester_id' => $requester->id,
            'resource_type' => VaultItem::class,
            'resource_id' => $item->id,
            'resource_owner_id' => $owner->id,
            'requested_permission' => 'view',
            'reason' => 'Need access.',
            'status' => 'pending',
        ]);

        $this->withHeaders($this->authHeaders($ownerToken, $tenant->id))
            ->putJson("/api/v1/access-requests/{$request->id}/approve");

        Notification::assertSentTo($requester, AccessRequestApprovedNotification::class);
    }

    public function test_requester_notified_on_rejection(): void
    {
        Notification::fake();

        [$tenant, $owner, $ownerToken, $requester, $requesterToken] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        $request = AccessRequest::create([
            'tenant_id' => $tenant->id,
            'requester_id' => $requester->id,
            'resource_type' => VaultItem::class,
            'resource_id' => $item->id,
            'resource_owner_id' => $owner->id,
            'requested_permission' => 'view',
            'reason' => 'Need access.',
            'status' => 'pending',
        ]);

        $this->withHeaders($this->authHeaders($ownerToken, $tenant->id))
            ->putJson("/api/v1/access-requests/{$request->id}/reject");

        Notification::assertSentTo($requester, AccessRequestRejectedNotification::class);
    }

    public function test_requester_can_cancel_pending(): void
    {
        [$tenant, $owner, $ownerToken, $requester, $requesterToken] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        $request = AccessRequest::create([
            'tenant_id' => $tenant->id,
            'requester_id' => $requester->id,
            'resource_type' => VaultItem::class,
            'resource_id' => $item->id,
            'resource_owner_id' => $owner->id,
            'requested_permission' => 'view',
            'reason' => 'Need access.',
            'status' => 'pending',
        ]);

        $response = $this->withHeaders($this->authHeaders($requesterToken, $tenant->id))
            ->deleteJson("/api/v1/access-requests/{$request->id}");

        $response->assertStatus(204);
        $this->assertSame('cancelled', AccessRequest::find($request->id)->status);
    }

    public function test_cannot_cancel_approved(): void
    {
        [$tenant, $owner, $ownerToken, $requester, $requesterToken] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        $request = AccessRequest::create([
            'tenant_id' => $tenant->id,
            'requester_id' => $requester->id,
            'resource_type' => VaultItem::class,
            'resource_id' => $item->id,
            'resource_owner_id' => $owner->id,
            'requested_permission' => 'view',
            'reason' => 'Need access.',
            'status' => 'approved',
        ]);

        $response = $this->withHeaders($this->authHeaders($requesterToken, $tenant->id))
            ->deleteJson("/api/v1/access-requests/{$request->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_view_sent_requests(): void
    {
        [$tenant, $owner, $ownerToken, $requester, $requesterToken] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        AccessRequest::create([
            'tenant_id' => $tenant->id,
            'requester_id' => $requester->id,
            'resource_type' => VaultItem::class,
            'resource_id' => $item->id,
            'resource_owner_id' => $owner->id,
            'requested_permission' => 'view',
            'reason' => 'Need access.',
            'status' => 'pending',
        ]);

        $response = $this->withHeaders($this->authHeaders($requesterToken, $tenant->id))
            ->getJson('/api/v1/access-requests?direction=sent');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_user_can_view_received_requests(): void
    {
        [$tenant, $owner, $ownerToken, $requester, $requesterToken] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        AccessRequest::create([
            'tenant_id' => $tenant->id,
            'requester_id' => $requester->id,
            'resource_type' => VaultItem::class,
            'resource_id' => $item->id,
            'resource_owner_id' => $owner->id,
            'requested_permission' => 'view',
            'reason' => 'Need access.',
            'status' => 'pending',
        ]);

        $response = $this->withHeaders($this->authHeaders($ownerToken, $tenant->id))
            ->getJson('/api/v1/access-requests?direction=received');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_request_history_retained(): void
    {
        [$tenant, $owner, $ownerToken, $requester, $requesterToken] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        foreach (['pending', 'approved', 'rejected', 'cancelled'] as $status) {
            AccessRequest::create([
                'tenant_id' => $tenant->id,
                'requester_id' => $requester->id,
                'resource_type' => VaultItem::class,
                'resource_id' => $item->id,
                'resource_owner_id' => $owner->id,
                'requested_permission' => 'view',
                'reason' => 'Need access.',
                'status' => $status,
            ]);
        }

        $response = $this->withHeaders($this->authHeaders($requesterToken, $tenant->id))
            ->getJson('/api/v1/access-requests/history');

        $response->assertStatus(200);
        $this->assertCount(4, $response->json('data'));
    }

    public function test_non_owner_cannot_approve(): void
    {
        [$tenant, $owner, $ownerToken, $requester, $requesterToken] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        $request = AccessRequest::create([
            'tenant_id' => $tenant->id,
            'requester_id' => $requester->id,
            'resource_type' => VaultItem::class,
            'resource_id' => $item->id,
            'resource_owner_id' => $owner->id,
            'requested_permission' => 'view',
            'reason' => 'Need access.',
            'status' => 'pending',
        ]);

        // Another member who is not the owner
        $other = $this->createUser(['email' => 'other@example.com']);
        $this->attachUserToTenant($other, $tenant, 'member');
        $otherToken = $other->createToken('test')->plainTextToken;

        $response = $this->withHeaders($this->authHeaders($otherToken, $tenant->id))
            ->putJson("/api/v1/access-requests/{$request->id}/approve");

        $response->assertStatus(403);
    }

    public function test_requests_are_tenant_scoped(): void
    {
        [$tenant, $owner, $ownerToken, $requester, $requesterToken] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        AccessRequest::create([
            'tenant_id' => $tenant->id,
            'requester_id' => $requester->id,
            'resource_type' => VaultItem::class,
            'resource_id' => $item->id,
            'resource_owner_id' => $owner->id,
            'requested_permission' => 'view',
            'reason' => 'Need access.',
            'status' => 'pending',
        ]);

        // Create a second tenant
        $tenant2 = $this->createTenant(['name' => 'Other Company', 'slug' => 'other-company']);
        $user2 = $this->createUser(['email' => 'user2@example.com']);
        $this->attachUserToTenant($user2, $tenant2, 'admin');
        $this->setupTenantContext($tenant2);
        $token2 = $user2->createToken('test')->plainTextToken;

        $response = $this->withHeaders($this->authHeaders($token2, $tenant2->id))
            ->getJson('/api/v1/access-requests');

        $response->assertStatus(200);
        $this->assertEmpty($response->json('data'));
    }
}
