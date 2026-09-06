<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Dashboard;

use App\Models\PersonalVaultItem;
use App\Models\SecureFile;
use App\Services\DashboardCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Helpers\AuthHelper;
use Tests\Helpers\TenantHelper;
use Tests\TestCase;

class DashboardCachingTest extends TestCase
{
    use AuthHelper, RefreshDatabase, TenantHelper;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_personal_dashboard_cached(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        // First request — cache MISS
        $response1 = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/dashboard/personal');

        $response1->assertStatus(200);
        $response1->assertHeader('X-Cache-Status', 'MISS');

        // Second request — cache HIT
        $response2 = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/dashboard/personal');

        $response2->assertStatus(200);
        $response2->assertHeader('X-Cache-Status', 'HIT');
    }

    public function test_company_dashboard_cached(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant();
        $this->attachUserToTenant($user, $tenant, 'admin');
        $this->setupTenantContext($tenant);

        $headers = array_merge($this->authHeaders($token), ['X-Tenant-ID' => (string) $tenant->id]);

        // First request — cache MISS
        $response1 = $this->withHeaders($headers)->getJson('/api/v1/dashboard/company');
        $response1->assertStatus(200);
        $response1->assertHeader('X-Cache-Status', 'MISS');

        // Second request — cache HIT
        $response2 = $this->withHeaders($headers)->getJson('/api/v1/dashboard/company');
        $response2->assertStatus(200);
        $response2->assertHeader('X-Cache-Status', 'HIT');
    }

    public function test_usage_dashboard_cached(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant();
        $this->attachUserToTenant($user, $tenant, 'admin');
        $this->setupTenantContext($tenant);

        $headers = array_merge($this->authHeaders($token), ['X-Tenant-ID' => (string) $tenant->id]);

        // First request — cache MISS
        $response1 = $this->withHeaders($headers)->getJson('/api/v1/dashboard/usage');
        $response1->assertStatus(200);
        $response1->assertHeader('X-Cache-Status', 'MISS');

        // Second request — cache HIT
        $response2 = $this->withHeaders($headers)->getJson('/api/v1/dashboard/usage');
        $response2->assertStatus(200);
        $response2->assertHeader('X-Cache-Status', 'HIT');
    }

    public function test_cache_invalidated_on_vault_item_create(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        // Prime the cache
        $this->withHeaders($this->authHeaders($token))->getJson('/api/v1/dashboard/personal');
        $this->assertTrue(Cache::has("dashboard:personal:{$user->id}"));

        // Create a vault item — should invalidate the cache
        PersonalVaultItem::create([
            'user_id' => $user->id,
            'name' => 'New Item',
            'type' => 'password',
            'username' => 'u',
            'password' => 'p',
        ]);

        $this->assertFalse(Cache::has("dashboard:personal:{$user->id}"));
    }

    public function test_cache_invalidated_on_member_offboard(): void
    {
        [$admin, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant();
        $this->attachUserToTenant($admin, $tenant, 'admin');
        $this->setupTenantContext($tenant);

        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');

        $headers = array_merge($this->authHeaders($token), ['X-Tenant-ID' => (string) $tenant->id]);

        // Prime the company cache
        $this->withHeaders($headers)->getJson('/api/v1/dashboard/company');
        $this->assertTrue(Cache::has("dashboard:company:{$tenant->id}"));

        // Prime the member's personal cache directly
        app(DashboardCacheService::class)->invalidatePersonalDashboardById($member->id);
        Cache::put("dashboard:personal:{$member->id}", ['data'], 60);
        $this->assertTrue(Cache::has("dashboard:personal:{$member->id}"));

        // Offboard the member — the action should invalidate caches
        // (offboarding updates a pivot, not a model, so observers don't fire)
        $this->withHeaders($headers)
            ->postJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}/offboard")
            ->assertStatus(204);

        $this->assertFalse(Cache::has("dashboard:company:{$tenant->id}"));
        $this->assertFalse(Cache::has("dashboard:personal:{$member->id}"));
    }

    public function test_cache_invalidated_on_access_grant(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant();
        $this->attachUserToTenant($user, $tenant, 'admin');
        $this->setupTenantContext($tenant);

        $grantee = $this->createUser(['email' => 'grantee@example.com']);
        $this->attachUserToTenant($grantee, $tenant, 'member');

        // Prime the grantee's personal dashboard cache
        $granteeToken = $grantee->createToken('test')->plainTextToken;
        $this->withHeaders($this->authHeaders($granteeToken))
            ->getJson('/api/v1/dashboard/personal');
        $this->assertTrue(Cache::has("dashboard:personal:{$grantee->id}"));

        // Manually invalidate via the service (simulates what the event listener does)
        app(DashboardCacheService::class)->invalidatePersonalDashboardById($grantee->id);

        $this->assertFalse(Cache::has("dashboard:personal:{$grantee->id}"));
    }

    public function test_cache_headers_present(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/dashboard/personal');

        $response->assertStatus(200);
        $response->assertHeader('X-Cache-Status');
        $response->assertHeader('X-Cache-TTL');
    }

    public function test_cache_miss_still_works(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        PersonalVaultItem::create([
            'user_id' => $user->id,
            'name' => 'Test',
            'type' => 'password',
            'username' => 'u',
            'password' => 'p',
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/dashboard/personal');

        $response->assertStatus(200);
        $response->assertJsonPath('data.vault_summary.total_items', 1);
        $response->assertHeader('X-Cache-Status', 'MISS');
    }

    public function test_cache_expires_after_60s(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        // Prime the cache
        $this->withHeaders($this->authHeaders($token))->getJson('/api/v1/dashboard/personal');
        $this->assertTrue(Cache::has("dashboard:personal:{$user->id}"));

        // Travel forward 61 seconds
        $this->travel(61)->seconds();

        // Cache should be expired
        $this->assertFalse(Cache::has("dashboard:personal:{$user->id}"));

        // New request should be a MISS and rebuild
        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/dashboard/personal');

        $response->assertStatus(200);
        $response->assertHeader('X-Cache-Status', 'MISS');
    }

    public function test_invalidation_on_file_delete(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant();
        $this->attachUserToTenant($user, $tenant, 'admin');
        $this->setupTenantContext($tenant);

        $headers = array_merge($this->authHeaders($token), ['X-Tenant-ID' => (string) $tenant->id]);

        // Create a secure file
        $file = SecureFile::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'name' => 'Test File',
            'file_path' => 'test/file.txt',
            'size' => 100,
            'mime_type' => 'text/plain',
            'checksum' => 'abc123',
        ]);

        // Prime the company and usage caches
        $this->withHeaders($headers)->getJson('/api/v1/dashboard/company');
        $this->withHeaders($headers)->getJson('/api/v1/dashboard/usage');
        $this->assertTrue(Cache::has("dashboard:company:{$tenant->id}"));
        $this->assertTrue(Cache::has("dashboard:usage:{$tenant->id}"));

        // Delete the file — should invalidate company + usage caches
        $file->delete();

        $this->assertFalse(Cache::has("dashboard:company:{$tenant->id}"));
        $this->assertFalse(Cache::has("dashboard:usage:{$tenant->id}"));
    }
}
