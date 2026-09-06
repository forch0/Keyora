<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\SecureLinks;

use App\Models\SecureLink;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VaultItem;
use App\Notifications\SecureLinkOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\Helpers\AuthHelper;
use Tests\Helpers\TenantHelper;
use Tests\TestCase;

class SecureLinkTest extends TestCase
{
    use AuthHelper, RefreshDatabase, TenantHelper;

    private function setupTenantAndUsers(): array
    {
        $tenant = $this->createTenant();
        $owner = $this->createUser(['email' => 'owner@example.com']);
        $this->attachUserToTenant($owner, $tenant, 'admin');
        $this->setupTenantContext($tenant);
        $token = $owner->createToken('test')->plainTextToken;

        return [$tenant, $owner, $token];
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

    public function test_can_create_basic_link(): void
    {
        [$tenant, $owner, $token] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/vault/items/{$item->id}/share-links", [
                'permission' => 'view',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'uuid', 'url', 'permission']]);
        $this->assertDatabaseHas('secure_links', [
            'resource_type' => VaultItem::class,
            'resource_id' => $item->id,
            'created_by' => $owner->id,
        ]);
    }

    public function test_can_create_password_protected_link(): void
    {
        [$tenant, $owner, $token] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/vault/items/{$item->id}/share-links", [
                'permission' => 'view',
                'password' => 'secret123',
            ]);

        $response->assertStatus(201);
        $link = SecureLink::first();
        $this->assertNotNull($link->password_hash);
        $this->assertNotEquals('secret123', $link->password_hash);
        $this->assertTrue(password_verify('secret123', $link->password_hash));
    }

    public function test_can_create_otp_protected_link(): void
    {
        Notification::fake();

        [$tenant, $owner, $token] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/vault/items/{$item->id}/share-links", [
                'permission' => 'view',
                'require_otp' => true,
                'recipient_email' => 'external@example.com',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['otp_code']]);
        $link = SecureLink::first();
        $this->assertNotNull($link->otp_code_hash);
        $this->assertNotNull($response->json('data.otp_code'));

        Notification::assertSentTo(
            new AnonymousNotifiable,
            SecureLinkOtpNotification::class
        );
    }

    public function test_otp_requires_email(): void
    {
        [$tenant, $owner, $token] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/vault/items/{$item->id}/share-links", [
                'permission' => 'view',
                'require_otp' => true,
            ]);

        $response->assertStatus(422);
    }

    public function test_can_create_expiring_link(): void
    {
        [$tenant, $owner, $token] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        $expiresAt = now()->addDay()->toIso8601String();

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/vault/items/{$item->id}/share-links", [
                'permission' => 'view',
                'expires_at' => $expiresAt,
            ]);

        $response->assertStatus(201);
        $link = SecureLink::first();
        $this->assertNotNull($link->expires_at);
    }

    public function test_can_create_first_view_expiring_link(): void
    {
        [$tenant, $owner, $token] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/vault/items/{$item->id}/share-links", [
                'permission' => 'view',
                'first_view_expires_hours' => 24,
            ]);

        $response->assertStatus(201);
        $link = SecureLink::first();
        $this->assertSame(24, $link->first_view_expires_hours);
    }

    public function test_can_create_max_views_link(): void
    {
        [$tenant, $owner, $token] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/vault/items/{$item->id}/share-links", [
                'permission' => 'view',
                'max_views' => 5,
            ]);

        $response->assertStatus(201);
        $link = SecureLink::first();
        $this->assertSame(5, $link->max_views);
    }

    public function test_can_create_one_time_link(): void
    {
        [$tenant, $owner, $token] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/vault/items/{$item->id}/share-links", [
                'permission' => 'view',
                'is_one_time' => true,
            ]);

        $response->assertStatus(201);
        $link = SecureLink::first();
        $this->assertTrue($link->is_one_time);
        $this->assertSame(1, $link->max_views);
    }

    public function test_can_disable_downloads(): void
    {
        [$tenant, $owner, $token] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/vault/items/{$item->id}/share-links", [
                'permission' => 'view',
                'download_enabled' => false,
            ]);

        $response->assertStatus(201);
        $link = SecureLink::first();
        $this->assertFalse($link->download_enabled);
    }

    public function test_can_list_links_for_resource(): void
    {
        [$tenant, $owner, $token] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        SecureLink::create([
            'tenant_id' => $tenant->id,
            'uuid' => Str::uuid()->toString(),
            'resource_type' => VaultItem::class,
            'resource_id' => $item->id,
            'created_by' => $owner->id,
            'permission' => 'view',
            'download_enabled' => true,
            'views_count' => 0,
            'is_one_time' => false,
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson("/api/v1/vault/items/{$item->id}/share-links");

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_can_revoke_link(): void
    {
        [$tenant, $owner, $token] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        $link = SecureLink::create([
            'tenant_id' => $tenant->id,
            'uuid' => Str::uuid()->toString(),
            'resource_type' => VaultItem::class,
            'resource_id' => $item->id,
            'created_by' => $owner->id,
            'permission' => 'view',
            'download_enabled' => true,
            'views_count' => 0,
            'is_one_time' => false,
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->deleteJson("/api/v1/share-links/{$link->id}");

        $response->assertStatus(204);
        $link->refresh();
        $this->assertNotNull($link->revoked_at);
    }

    public function test_password_never_in_response(): void
    {
        [$tenant, $owner, $token] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/vault/items/{$item->id}/share-links", [
                'permission' => 'view',
                'password' => 'secret123',
            ]);

        $response->assertStatus(201);
        $response->assertJsonMissing(['password' => 'secret123']);
        $response->assertJsonMissing(['password_hash' => SecureLink::first()->password_hash]);
        $this->assertTrue($response->json('data.has_password'));
    }

    public function test_otp_only_in_creation_response(): void
    {
        Notification::fake();

        [$tenant, $owner, $token] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        // Create link with OTP
        $createResponse = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/vault/items/{$item->id}/share-links", [
                'permission' => 'view',
                'require_otp' => true,
                'recipient_email' => 'external@example.com',
            ]);

        $createResponse->assertStatus(201);
        $this->assertNotNull($createResponse->json('data.otp_code'));

        // List links — OTP should NOT be present
        $listResponse = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson("/api/v1/vault/items/{$item->id}/share-links");

        $listResponse->assertStatus(200);
        $listResponse->assertJsonMissingPath('data.0.otp_code');
    }

    public function test_only_share_permission_can_create(): void
    {
        [$tenant, $owner, $ownerToken] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');
        $memberToken = $member->createToken('test')->plainTextToken;

        $response = $this->withHeaders($this->authHeaders($memberToken, $tenant->id))
            ->postJson("/api/v1/vault/items/{$item->id}/share-links", [
                'permission' => 'view',
            ]);

        $response->assertStatus(403);
    }

    public function test_links_are_tenant_scoped(): void
    {
        [$tenant, $owner, $token] = $this->setupTenantAndUsers();
        $item = $this->createVaultItem($tenant, $owner);

        SecureLink::create([
            'tenant_id' => $tenant->id,
            'uuid' => Str::uuid()->toString(),
            'resource_type' => VaultItem::class,
            'resource_id' => $item->id,
            'created_by' => $owner->id,
            'permission' => 'view',
            'download_enabled' => true,
            'views_count' => 0,
            'is_one_time' => false,
        ]);

        // Second tenant
        $tenant2 = $this->createTenant(['name' => 'Other', 'slug' => 'other']);
        $user2 = $this->createUser(['email' => 'user2@example.com']);
        $this->attachUserToTenant($user2, $tenant2, 'admin');
        $this->setupTenantContext($tenant2);
        $token2 = $user2->createToken('test')->plainTextToken;

        $item2 = $this->createVaultItem($tenant2, $user2);

        $response = $this->withHeaders($this->authHeaders($token2, $tenant2->id))
            ->getJson("/api/v1/vault/items/{$item2->id}/share-links");

        $response->assertStatus(200);
        $this->assertEmpty($response->json('data'));
    }
}
