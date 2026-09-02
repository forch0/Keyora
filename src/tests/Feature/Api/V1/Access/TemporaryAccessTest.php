<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Access;

use App\Jobs\SendExpirationWarning;
use App\Models\AccessGrant;
use App\Models\SecureNote;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VaultItem;
use App\Notifications\AccessExpiredNotification;
use App\Notifications\AccessExpiringSoon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Helpers\AuthHelper;
use Tests\Helpers\TenantHelper;
use Tests\TestCase;

class TemporaryAccessTest extends TestCase
{
    use AuthHelper, RefreshDatabase, TenantHelper;

    private function setupTenantAndUsers(): array
    {
        $tenant = $this->createTenant();
        $owner = $this->createUser(['email' => 'owner@example.com']);
        $this->attachUserToTenant($owner, $tenant, 'admin');
        $this->setupTenantContext($tenant);
        $token = $owner->createToken('test')->plainTextToken;

        $recipient = $this->createUser(['email' => 'recipient@example.com']);
        $this->attachUserToTenant($recipient, $tenant, 'member');
        $recipientToken = $recipient->createToken('test')->plainTextToken;

        return [$tenant, $owner, $token, $recipient, $recipientToken];
    }

    private function authHeaders(string $token, int $tenantId): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenantId,
        ];
    }

    private function createNote(Tenant $tenant, User $owner): SecureNote
    {
        return SecureNote::create([
            'tenant_id' => $tenant->id,
            'team_id' => null,
            'user_id' => $owner->id,
            'title' => 'Shared Note',
            'content' => 'Secret content.',
            'content_format' => 'markdown',
        ]);
    }

    public function test_grant_with_preset_duration(): void
    {
        [$tenant, $owner, $token, $recipient] = $this->setupTenantAndUsers();
        $note = $this->createNote($tenant, $owner);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/notes/{$note->id}/access", [
                'subject_type' => User::class,
                'subject_id' => $recipient->id,
                'permission' => 'view',
                'duration' => '1h',
            ]);

        $response->assertStatus(201);
        $grant = AccessGrant::where('grantable_type', SecureNote::class)->first();
        $this->assertNotNull($grant);
        $this->assertNotNull($grant->expires_at);
        // expires_at should be approximately 1 hour from now
        $this->assertTrue($grant->expires_at->isAfter(now()));
        $this->assertTrue($grant->expires_at->isBefore(now()->addSeconds(3700)));
    }

    public function test_grant_with_custom_duration(): void
    {
        [$tenant, $owner, $token, $recipient] = $this->setupTenantAndUsers();
        $note = $this->createNote($tenant, $owner);

        $startsAt = now()->addHour();
        $expiresAt = now()->addHours(2);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/notes/{$note->id}/access", [
                'subject_type' => User::class,
                'subject_id' => $recipient->id,
                'permission' => 'view',
                'starts_at' => $startsAt->toIso8601String(),
                'expires_at' => $expiresAt->toIso8601String(),
            ]);

        $response->assertStatus(201);
        $grant = AccessGrant::where('grantable_type', SecureNote::class)->first();
        $this->assertNotNull($grant);
        $this->assertNotNull($grant->starts_at);
        $this->assertNotNull($grant->expires_at);
    }

    public function test_grant_with_start_on_first_view(): void
    {
        [$tenant, $owner, $token, $recipient] = $this->setupTenantAndUsers();
        $note = $this->createNote($tenant, $owner);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/notes/{$note->id}/access", [
                'subject_type' => User::class,
                'subject_id' => $recipient->id,
                'permission' => 'view',
                'duration' => '1h',
                'start_on_first_view' => true,
            ]);

        $response->assertStatus(201);
        $grant = AccessGrant::where('grantable_type', SecureNote::class)->first();
        $this->assertNotNull($grant);
        $this->assertTrue($grant->start_on_first_view);
        $this->assertNull($grant->first_viewed_at);
    }

    public function test_first_view_starts_clock(): void
    {
        [$tenant, $owner, $token, $recipient, $recipientToken] = $this->setupTenantAndUsers();
        $note = $this->createNote($tenant, $owner);

        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => SecureNote::class,
            'grantable_id' => $note->id,
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
            'permission' => 'view',
            'expires_at' => now()->addHour(),
            'start_on_first_view' => true,
            'first_viewed_at' => null,
            'views_count' => 0,
            'granted_by' => $owner->id,
            'granted_at' => now(),
        ]);

        // View the note
        $this->withHeaders($this->authHeaders($recipientToken, $tenant->id))
            ->getJson("/api/v1/notes/{$note->id}");

        $grant = AccessGrant::where('grantable_type', SecureNote::class)->first();
        $this->assertNotNull($grant->first_viewed_at);
        $this->assertSame(1, $grant->views_count);
    }

    public function test_grant_with_max_views(): void
    {
        [$tenant, $owner, $token, $recipient] = $this->setupTenantAndUsers();
        $note = $this->createNote($tenant, $owner);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/notes/{$note->id}/access", [
                'subject_type' => User::class,
                'subject_id' => $recipient->id,
                'permission' => 'view',
                'max_views' => 3,
            ]);

        $response->assertStatus(201);
        $grant = AccessGrant::where('grantable_type', SecureNote::class)->first();
        $this->assertNotNull($grant);
        $this->assertSame(3, $grant->max_views);
    }

    public function test_one_time_access_self_destructs(): void
    {
        [$tenant, $owner, $token, $recipient, $recipientToken] = $this->setupTenantAndUsers();
        $note = $this->createNote($tenant, $owner);

        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => SecureNote::class,
            'grantable_id' => $note->id,
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
            'permission' => 'view',
            'max_views' => 1,
            'views_count' => 0,
            'granted_by' => $owner->id,
            'granted_at' => now(),
        ]);

        // First view — should succeed
        $first = $this->withHeaders($this->authHeaders($recipientToken, $tenant->id))
            ->getJson("/api/v1/notes/{$note->id}");
        $first->assertStatus(200);

        // Grant should be revoked
        $grant = AccessGrant::where('grantable_type', SecureNote::class)->first();
        $this->assertNotNull($grant->revoked_at);
        $this->assertSame('view_limit_reached', $grant->revoke_reason);

        // Second view — should be 403
        $second = $this->withHeaders($this->authHeaders($recipientToken, $tenant->id))
            ->getJson("/api/v1/notes/{$note->id}");
        $second->assertStatus(403);
    }

    public function test_auto_revoke_on_view_limit(): void
    {
        [$tenant, $owner, $token, $recipient, $recipientToken] = $this->setupTenantAndUsers();
        $note = $this->createNote($tenant, $owner);

        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => SecureNote::class,
            'grantable_id' => $note->id,
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
            'permission' => 'view',
            'max_views' => 2,
            'views_count' => 0,
            'granted_by' => $owner->id,
            'granted_at' => now(),
        ]);

        // View 1
        $this->withHeaders($this->authHeaders($recipientToken, $tenant->id))
            ->getJson("/api/v1/notes/{$note->id}");

        $grant = AccessGrant::where('grantable_type', SecureNote::class)->first();
        $this->assertSame(1, $grant->views_count);
        $this->assertNull($grant->revoked_at);

        // View 2 — should auto-revoke
        $this->withHeaders($this->authHeaders($recipientToken, $tenant->id))
            ->getJson("/api/v1/notes/{$note->id}");

        $grant->refresh();
        $this->assertSame(2, $grant->views_count);
        $this->assertNotNull($grant->revoked_at);
        $this->assertSame('view_limit_reached', $grant->revoke_reason);
    }

    public function test_auto_revoke_on_expiration(): void
    {
        [$tenant, $owner, $token, $recipient] = $this->setupTenantAndUsers();
        $note = $this->createNote($tenant, $owner);

        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => SecureNote::class,
            'grantable_id' => $note->id,
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
            'permission' => 'view',
            'expires_at' => now()->subHour(),
            'views_count' => 0,
            'granted_by' => $owner->id,
            'granted_at' => now(),
        ]);

        $this->artisan('access:check-expired')
            ->assertSuccessful();

        $grant = AccessGrant::where('grantable_type', SecureNote::class)->first();
        $this->assertNotNull($grant->revoked_at);
        $this->assertSame('expired', $grant->revoke_reason);
    }

    public function test_expiration_countdown_endpoint(): void
    {
        [$tenant, $owner, $token, $recipient, $recipientToken] = $this->setupTenantAndUsers();

        // Create a vault item for the countdown endpoint
        $item = VaultItem::create([
            'tenant_id' => $tenant->id,
            'team_id' => null,
            'user_id' => $owner->id,
            'name' => 'Shared Item',
            'type' => 'password',
            'username' => 'user',
            'password' => 'pass',
        ]);

        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => VaultItem::class,
            'grantable_id' => $item->id,
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
            'permission' => 'view',
            'expires_at' => now()->addHour(),
            'max_views' => 5,
            'views_count' => 2,
            'granted_by' => $owner->id,
            'granted_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($recipientToken, $tenant->id))
            ->getJson("/api/v1/vault/items/{$item->id}/access/countdown");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'grant_id', 'expires_at', 'seconds_remaining',
                    'views_remaining', 'max_views', 'views_count',
                ],
            ]);
        $this->assertSame(3, $response->json('data.views_remaining'));
    }

    public function test_expiration_warning_sent(): void
    {
        Notification::fake();

        [$tenant, $owner, $token, $recipient] = $this->setupTenantAndUsers();
        $note = $this->createNote($tenant, $owner);

        // Grant expiring in 12 hours (within 24h warning window)
        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => SecureNote::class,
            'grantable_id' => $note->id,
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
            'permission' => 'view',
            'expires_at' => now()->addHours(12),
            'views_count' => 0,
            'granted_by' => $owner->id,
            'granted_at' => now(),
        ]);

        // Dispatch the job
        SendExpirationWarning::dispatchSync();

        Notification::assertSentTo($recipient, AccessExpiringSoon::class);

        $grant = AccessGrant::where('grantable_type', SecureNote::class)->first();
        $this->assertNotNull($grant->warning_sent_at);
    }

    public function test_expiration_notification_sent(): void
    {
        Notification::fake();

        [$tenant, $owner, $token, $recipient] = $this->setupTenantAndUsers();
        $note = $this->createNote($tenant, $owner);

        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => SecureNote::class,
            'grantable_id' => $note->id,
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
            'permission' => 'view',
            'expires_at' => now()->subHour(),
            'views_count' => 0,
            'granted_by' => $owner->id,
            'granted_at' => now(),
        ]);

        $this->artisan('access:check-expired');

        Notification::assertSentTo($recipient, AccessExpiredNotification::class);
    }

    public function test_duration_and_expires_at_mutually_exclusive(): void
    {
        [$tenant, $owner, $token, $recipient] = $this->setupTenantAndUsers();
        $note = $this->createNote($tenant, $owner);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/notes/{$note->id}/access", [
                'subject_type' => User::class,
                'subject_id' => $recipient->id,
                'permission' => 'view',
                'duration' => '1h',
                'expires_at' => now()->addDay()->toIso8601String(),
            ]);

        $response->assertStatus(422);
    }

    public function test_start_on_first_view_with_starts_at_invalid(): void
    {
        [$tenant, $owner, $token, $recipient] = $this->setupTenantAndUsers();
        $note = $this->createNote($tenant, $owner);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/notes/{$note->id}/access", [
                'subject_type' => User::class,
                'subject_id' => $recipient->id,
                'permission' => 'view',
                'start_on_first_view' => true,
                'starts_at' => now()->addHour()->toIso8601String(),
            ]);

        $response->assertStatus(422);
    }

    public function test_scheduled_command_revokes_expired(): void
    {
        [$tenant, $owner, $token, $recipient] = $this->setupTenantAndUsers();
        $note = $this->createNote($tenant, $owner);

        // Create multiple expired grants
        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => SecureNote::class,
            'grantable_id' => $note->id,
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
            'permission' => 'view',
            'expires_at' => now()->subMinutes(5),
            'views_count' => 0,
            'granted_by' => $owner->id,
            'granted_at' => now(),
        ]);

        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => SecureNote::class,
            'grantable_id' => $note->id,
            'subject_type' => User::class,
            'subject_id' => $owner->id,
            'permission' => 'view',
            'expires_at' => now()->subMinutes(10),
            'views_count' => 0,
            'granted_by' => $owner->id,
            'granted_at' => now(),
        ]);

        $this->artisan('access:check-expired')
            ->assertSuccessful()
            ->expectsOutputToContain('Revoked');

        $this->assertSame(0, AccessGrant::whereNull('revoked_at')->count());
    }

    public function test_view_count_increments_on_view(): void
    {
        [$tenant, $owner, $token, $recipient, $recipientToken] = $this->setupTenantAndUsers();
        $note = $this->createNote($tenant, $owner);

        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => SecureNote::class,
            'grantable_id' => $note->id,
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
            'permission' => 'view',
            'max_views' => 10,
            'views_count' => 0,
            'granted_by' => $owner->id,
            'granted_at' => now(),
        ]);

        // View 1
        $this->withHeaders($this->authHeaders($recipientToken, $tenant->id))
            ->getJson("/api/v1/notes/{$note->id}");

        $grant = AccessGrant::where('grantable_type', SecureNote::class)->first();
        $this->assertSame(1, $grant->views_count);

        // View 2
        $this->withHeaders($this->authHeaders($recipientToken, $tenant->id))
            ->getJson("/api/v1/notes/{$note->id}");

        $grant->refresh();
        $this->assertSame(2, $grant->views_count);
    }
}
