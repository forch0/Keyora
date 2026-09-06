<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\SecureLinks;

use App\Models\SecureLink;
use App\Models\SecureLinkAccess;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VaultItem;
use App\Notifications\SecureLinkEmailVerificationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\Helpers\AuthHelper;
use Tests\Helpers\TenantHelper;
use Tests\TestCase;

class PublicLinkAccessTest extends TestCase
{
    use AuthHelper, RefreshDatabase, TenantHelper;

    private function setupTenantAndOwner(): array
    {
        $tenant = $this->createTenant();
        $owner = $this->createUser(['email' => 'owner@example.com']);
        $this->attachUserToTenant($owner, $tenant, 'admin');
        $this->setupTenantContext($tenant);

        return [$tenant, $owner];
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
            'password' => 'secret_password',
        ]);
    }

    private function createLink(Tenant $tenant, User $owner, VaultItem $item, array $overrides = []): SecureLink
    {
        return SecureLink::create(array_merge([
            'tenant_id' => $tenant->id,
            'uuid' => Str::uuid()->toString(),
            'resource_type' => VaultItem::class,
            'resource_id' => $item->id,
            'created_by' => $owner->id,
            'permission' => 'view',
            'download_enabled' => true,
            'views_count' => 0,
            'is_one_time' => false,
        ], $overrides));
    }

    public function test_can_get_link_info(): void
    {
        [$tenant, $owner] = $this->setupTenantAndOwner();
        $item = $this->createVaultItem($tenant, $owner);
        $link = $this->createLink($tenant, $owner, $item);

        $response = $this->getJson("/api/v1/s/{$link->uuid}");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['uuid', 'resource_type', 'resource_name', 'requires_password', 'requires_otp']]);
        // Resource content should NOT be returned
        $response->assertJsonMissing(['content' => 'secret_password']);
    }

    public function test_password_verification_works(): void
    {
        [$tenant, $owner] = $this->setupTenantAndOwner();
        $item = $this->createVaultItem($tenant, $owner);
        $link = $this->createLink($tenant, $owner, $item, [
            'password_hash' => bcrypt('mypassword'),
        ]);

        $response = $this->postJson("/api/v1/s/{$link->uuid}/verify", [
            'password' => 'mypassword',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['access_token']]);
    }

    public function test_wrong_password_rejected(): void
    {
        [$tenant, $owner] = $this->setupTenantAndOwner();
        $item = $this->createVaultItem($tenant, $owner);
        $link = $this->createLink($tenant, $owner, $item, [
            'password_hash' => bcrypt('mypassword'),
        ]);

        $response = $this->postJson("/api/v1/s/{$link->uuid}/verify", [
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_otp_verification_works(): void
    {
        [$tenant, $owner] = $this->setupTenantAndOwner();
        $item = $this->createVaultItem($tenant, $owner);
        $link = $this->createLink($tenant, $owner, $item, [
            'otp_code_hash' => bcrypt('123456'),
        ]);

        $response = $this->postJson("/api/v1/s/{$link->uuid}/verify", [
            'otp_code' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['access_token']]);
    }

    public function test_wrong_otp_rejected(): void
    {
        [$tenant, $owner] = $this->setupTenantAndOwner();
        $item = $this->createVaultItem($tenant, $owner);
        $link = $this->createLink($tenant, $owner, $item, [
            'otp_code_hash' => bcrypt('123456'),
        ]);

        $response = $this->postJson("/api/v1/s/{$link->uuid}/verify", [
            'otp_code' => '999999',
        ]);

        $response->assertStatus(422);
    }

    public function test_email_verification_flow(): void
    {
        Notification::fake();

        [$tenant, $owner] = $this->setupTenantAndOwner();
        $item = $this->createVaultItem($tenant, $owner);
        $link = $this->createLink($tenant, $owner, $item);

        // Step 1: Send code
        $sendResponse = $this->postJson("/api/v1/s/{$link->uuid}/email-verify", [
            'email' => 'external@example.com',
        ]);

        $sendResponse->assertStatus(200);

        Notification::assertSentTo(
            new AnonymousNotifiable,
            SecureLinkEmailVerificationNotification::class
        );

        // Get the code from the link (it was hashed, so we need to capture it differently)
        // In test, we can use the action directly
        $link->refresh();
        // The code was sent via notification — extract it
        $notification = Notification::sent(
            new AnonymousNotifiable,
            SecureLinkEmailVerificationNotification::class
        )->first();

        $code = $notification?->code ?? '000000';

        // Step 2: Confirm code
        $confirmResponse = $this->postJson("/api/v1/s/{$link->uuid}/email-confirm", [
            'code' => $code,
        ]);

        $confirmResponse->assertStatus(200)
            ->assertJsonStructure(['data' => ['access_token']]);
    }

    public function test_resource_access_after_verification(): void
    {
        [$tenant, $owner] = $this->setupTenantAndOwner();
        $item = $this->createVaultItem($tenant, $owner);
        $link = $this->createLink($tenant, $owner, $item);

        // Get a signed URL directly
        $token = URL::temporarySignedRoute(
            'api.v1.public-link.resource',
            now()->addMinutes(15),
            ['uuid' => $link->uuid],
        );

        $response = $this->getJson($token);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['resource_type', 'resource_id', 'content']]);
    }

    public function test_resource_access_without_verification(): void
    {
        [$tenant, $owner] = $this->setupTenantAndOwner();
        $item = $this->createVaultItem($tenant, $owner);
        $link = $this->createLink($tenant, $owner, $item);

        // Access without a signed URL
        $response = $this->getJson("/api/v1/s/{$link->uuid}/resource");

        $response->assertStatus(403);
    }

    public function test_view_count_increments(): void
    {
        [$tenant, $owner] = $this->setupTenantAndOwner();
        $item = $this->createVaultItem($tenant, $owner);
        $link = $this->createLink($tenant, $owner, $item);

        $token = URL::temporarySignedRoute(
            'api.v1.public-link.resource',
            now()->addMinutes(15),
            ['uuid' => $link->uuid],
        );

        $this->getJson($token);
        $this->getJson($token);

        $link->refresh();
        $this->assertSame(2, $link->views_count);
    }

    public function test_first_view_sets_timestamp(): void
    {
        [$tenant, $owner] = $this->setupTenantAndOwner();
        $item = $this->createVaultItem($tenant, $owner);
        $link = $this->createLink($tenant, $owner, $item);

        $token = URL::temporarySignedRoute(
            'api.v1.public-link.resource',
            now()->addMinutes(15),
            ['uuid' => $link->uuid],
        );

        $this->assertNull($link->first_viewed_at);
        $this->getJson($token);
        $link->refresh();
        $this->assertNotNull($link->first_viewed_at);
    }

    public function test_one_time_link_self_destructs(): void
    {
        [$tenant, $owner] = $this->setupTenantAndOwner();
        $item = $this->createVaultItem($tenant, $owner);
        $link = $this->createLink($tenant, $owner, $item, [
            'is_one_time' => true,
            'max_views' => 1,
        ]);

        $token = URL::temporarySignedRoute(
            'api.v1.public-link.resource',
            now()->addMinutes(15),
            ['uuid' => $link->uuid],
        );

        // First access — should work
        $first = $this->getJson($token);
        $first->assertStatus(200);

        // Second access — should be 410
        $second = $this->getJson($token);
        $second->assertStatus(410);
    }

    public function test_max_views_enforced(): void
    {
        [$tenant, $owner] = $this->setupTenantAndOwner();
        $item = $this->createVaultItem($tenant, $owner);
        $link = $this->createLink($tenant, $owner, $item, [
            'max_views' => 2,
        ]);

        $token = URL::temporarySignedRoute(
            'api.v1.public-link.resource',
            now()->addMinutes(15),
            ['uuid' => $link->uuid],
        );

        // Two views should work
        $this->getJson($token)->assertStatus(200);
        $this->getJson($token)->assertStatus(200);

        // Third should be 410
        $this->getJson($token)->assertStatus(410);
    }

    public function test_expired_link_returns_410(): void
    {
        [$tenant, $owner] = $this->setupTenantAndOwner();
        $item = $this->createVaultItem($tenant, $owner);
        $link = $this->createLink($tenant, $owner, $item, [
            'expires_at' => now()->subHour(),
        ]);

        $token = URL::temporarySignedRoute(
            'api.v1.public-link.resource',
            now()->addMinutes(15),
            ['uuid' => $link->uuid],
        );

        $response = $this->getJson($token);
        $response->assertStatus(410);
    }

    public function test_revoked_link_returns_410(): void
    {
        [$tenant, $owner] = $this->setupTenantAndOwner();
        $item = $this->createVaultItem($tenant, $owner);
        $link = $this->createLink($tenant, $owner, $item, [
            'revoked_at' => now(),
            'revoke_reason' => 'manual',
        ]);

        $token = URL::temporarySignedRoute(
            'api.v1.public-link.resource',
            now()->addMinutes(15),
            ['uuid' => $link->uuid],
        );

        $response = $this->getJson($token);
        $response->assertStatus(410);
    }

    public function test_first_view_expiration(): void
    {
        [$tenant, $owner] = $this->setupTenantAndOwner();
        $item = $this->createVaultItem($tenant, $owner);
        $link = $this->createLink($tenant, $owner, $item, [
            'first_view_expires_hours' => 1,
            'first_viewed_at' => now()->subHours(2),
        ]);

        $token = URL::temporarySignedRoute(
            'api.v1.public-link.resource',
            now()->addMinutes(15),
            ['uuid' => $link->uuid],
        );

        $response = $this->getJson($token);
        $response->assertStatus(410);
    }

    public function test_access_logged(): void
    {
        [$tenant, $owner] = $this->setupTenantAndOwner();
        $item = $this->createVaultItem($tenant, $owner);
        $link = $this->createLink($tenant, $owner, $item);

        $token = URL::temporarySignedRoute(
            'api.v1.public-link.resource',
            now()->addMinutes(15),
            ['uuid' => $link->uuid],
        );

        $this->getJson($token);

        $this->assertDatabaseHas('secure_link_accesses', [
            'secure_link_id' => $link->id,
        ]);
        $this->assertSame(1, SecureLinkAccess::where('secure_link_id', $link->id)->count());
    }

    public function test_creator_can_view_activity(): void
    {
        [$tenant, $owner] = $this->setupTenantAndOwner();
        $item = $this->createVaultItem($tenant, $owner);
        $link = $this->createLink($tenant, $owner, $item);

        SecureLinkAccess::create([
            'secure_link_id' => $link->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'TestBrowser',
            'email' => 'external@example.com',
            'accessed_at' => now(),
        ]);

        $token = $owner->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->getJson("/api/v1/share-links/{$link->id}/activity");

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_non_creator_cannot_view_activity(): void
    {
        [$tenant, $owner] = $this->setupTenantAndOwner();
        $item = $this->createVaultItem($tenant, $owner);
        $link = $this->createLink($tenant, $owner, $item);

        $other = $this->createUser(['email' => 'other@example.com']);
        $this->attachUserToTenant($other, $tenant, 'member');
        $token = $other->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->getJson("/api/v1/share-links/{$link->id}/activity");

        $response->assertStatus(403);
    }
}
