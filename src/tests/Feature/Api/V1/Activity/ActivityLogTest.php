<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Activity;

use App\Models\AccessGrant;
use App\Models\ActivityLog;
use App\Models\PersonalVaultItem;
use App\Models\SecureFile;
use App\Models\SecureLink;
use App\Models\User;
use App\Models\VaultItem;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\Helpers\AuthHelper;
use Tests\Helpers\TenantHelper;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use AuthHelper, RefreshDatabase, TenantHelper;

    private function setupTenantAndUser(string $role = 'admin'): array
    {
        $tenant = $this->createTenant();
        $user = $this->createUser(['email' => 'user@example.com', 'name' => 'Test User']);
        $this->attachUserToTenant($user, $tenant, $role);
        $this->setupTenantContext($tenant);
        $token = $user->createToken('test')->plainTextToken;

        return [$tenant, $user, $token];
    }

    private function authHeaders(string $token, int $tenantId): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenantId,
        ];
    }

    public function test_login_is_logged(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser(['email' => 'login@example.com', 'password' => bcrypt('password')]);
        $this->attachUserToTenant($user, $tenant, 'admin');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'auth.login',
        ]);
    }

    public function test_logout_is_logged(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/auth/logout');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'auth.logout',
        ]);
    }

    public function test_vault_item_creation_logged(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/vault/items', [
                'name' => 'New Item',
                'type' => 'password',
                'username' => 'user',
                'password' => 'secret',
            ]);

        // Vault item creation logs via the action (Module 20)
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'vault_item.created',
        ]);
    }

    public function test_vault_item_view_logged(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();
        $item = PersonalVaultItem::create([
            'user_id' => $user->id,
            'name' => 'Test Item',
            'type' => 'password',
            'username' => 'u',
            'password' => 'p',
        ]);

        $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson("/api/v1/vault/items/{$item->id}");

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'vault_item.viewed',
            'subject_type' => PersonalVaultItem::class,
            'subject_id' => $item->id,
        ]);
    }

    public function test_access_grant_logged(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();
        $other = $this->createUser(['email' => 'other@example.com']);
        $this->attachUserToTenant($other, $tenant, 'member');

        $item = VaultItem::create([
            'tenant_id' => $tenant->id, 'team_id' => null, 'user_id' => $user->id,
            'name' => 'Shared', 'type' => 'password', 'username' => 'u', 'password' => 'p',
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/vault/items/{$item->id}/access", [
                'subject_type' => User::class,
                'subject_id' => $other->id,
                'permission' => 'view',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'access.granted',
        ]);
    }

    public function test_access_revoke_logged(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();
        $other = $this->createUser(['email' => 'other@example.com']);
        $this->attachUserToTenant($other, $tenant, 'member');

        $item = VaultItem::create([
            'tenant_id' => $tenant->id, 'team_id' => null, 'user_id' => $user->id,
            'name' => 'Shared', 'type' => 'password', 'username' => 'u', 'password' => 'p',
        ]);

        $grant = AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => VaultItem::class,
            'grantable_id' => $item->id,
            'subject_type' => User::class,
            'subject_id' => $other->id,
            'permission' => 'view',
            'views_count' => 0,
            'granted_by' => $user->id,
            'granted_at' => now(),
        ]);

        $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->deleteJson("/api/v1/vault/items/{$item->id}/access/{$grant->id}");

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'access.revoked',
        ]);
    }

    public function test_file_download_logged(): void
    {
        Queue::fake(); // Prevent queue jobs from interfering

        [$tenant, $user, $token] = $this->setupTenantAndUser();

        // Create a file with actual storage
        $file = SecureFile::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'file_path' => 'test_download.txt',
        ]);

        // Create the file in storage
        Storage::fake('private');
        Storage::disk('private')->put('test_download.txt', 'test content');

        $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson("/api/v1/files/{$file->id}/download");

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'file.downloaded',
            'subject_type' => SecureFile::class,
            'subject_id' => $file->id,
        ]);
    }

    public function test_secure_link_access_logged(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();
        $item = VaultItem::create([
            'tenant_id' => $tenant->id, 'team_id' => null, 'user_id' => $user->id,
            'name' => 'Shared', 'type' => 'password', 'username' => 'u', 'password' => 'p',
        ]);

        $link = SecureLink::create([
            'tenant_id' => $tenant->id,
            'uuid' => Str::uuid()->toString(),
            'resource_type' => VaultItem::class,
            'resource_id' => $item->id,
            'created_by' => $user->id,
            'permission' => 'view',
            'download_enabled' => true,
            'views_count' => 0,
            'is_one_time' => false,
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'api.v1.public-link.resource',
            now()->addMinutes(15),
            ['uuid' => $link->uuid],
        );

        $this->getJson($signedUrl);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'secure_link.accessed',
        ]);
    }

    public function test_personal_history(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $logger = app(ActivityLogger::class);
        $logger->log('vault_item.viewed', $user);
        $logger->log('auth.login', $user);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson('/api/v1/activity-logs');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_company_feed(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser('admin');

        $logger = app(ActivityLogger::class);
        $logger->log('vault_item.viewed', $user);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson("/api/v1/tenants/{$tenant->id}/activity-logs");

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_resource_history(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();
        $item = PersonalVaultItem::create([
            'user_id' => $user->id,
            'name' => 'Test',
            'type' => 'password',
            'username' => 'u',
            'password' => 'p',
        ]);

        $logger = app(ActivityLogger::class);
        $logger->log('vault_item.viewed', $user, $item);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson("/api/v1/vault/items/{$item->id}/activity-logs");

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_employee_overview(): void
    {
        [$tenant, $admin, $token] = $this->setupTenantAndUser('admin');
        $employee = $this->createUser(['email' => 'emp@example.com']);
        $this->attachUserToTenant($employee, $tenant, 'member');

        $logger = app(ActivityLogger::class);
        $logger->log('vault_item.viewed', $employee);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson("/api/v1/tenants/{$tenant->id}/members/{$employee->id}/activity-logs");

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_logs_are_append_only(): void
    {
        // The model has UPDATED_AT = null, meaning no update timestamp
        $this->assertNull(ActivityLog::UPDATED_AT);
    }

    public function test_log_includes_ip_and_user_agent(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $this->withHeaders(array_merge($this->authHeaders($token, $tenant->id), [
            'User-Agent' => 'TestBrowser/1.0',
        ]))->postJson('/api/v1/auth/logout');

        $log = ActivityLog::where('action', 'auth.logout')->first();
        $this->assertNotNull($log);
        $this->assertNotNull($log->ip_address);
        $this->assertSame('TestBrowser/1.0', $log->user_agent);
    }

    public function test_filter_by_action(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $logger = app(ActivityLogger::class);
        $logger->log('vault_item.viewed', $user);
        $logger->log('access.granted', $user);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson('/api/v1/activity-logs?action=access.granted');

        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $entry) {
            $this->assertSame('access.granted', $entry['action']);
        }
    }

    public function test_filter_by_date_range(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $logger = app(ActivityLogger::class);
        $logger->log('vault_item.viewed', $user);

        $yesterday = now()->subDay()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson("/api/v1/activity-logs?from={$yesterday}&to={$tomorrow}");

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_non_admin_cannot_view_company_feed(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser('member');

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson("/api/v1/tenants/{$tenant->id}/activity-logs");

        $response->assertStatus(403);
    }

    public function test_cleanup_command_removes_old_logs(): void
    {
        [$tenant, $user] = $this->setupTenantAndUser();

        $logger = app(ActivityLogger::class);
        $logger->log('vault_item.viewed', $user);

        // Manually create an old log
        ActivityLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'action' => 'auth.login',
            'ip_address' => null,
            'user_agent' => null,
            'created_at' => now()->subDays(400),
        ]);

        $this->artisan('activity-logs:cleanup', ['--days' => 365])
            ->assertSuccessful();

        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'auth.login',
            'created_at' => now()->subDays(400)->toDateTimeString(),
        ]);
    }
}
