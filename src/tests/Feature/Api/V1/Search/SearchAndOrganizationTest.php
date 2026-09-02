<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Search;

use App\Models\AccessGrant;
use App\Models\PersonalVaultFolder;
use App\Models\PersonalVaultItem;
use App\Models\PersonalVaultTag;
use App\Models\ResourceView;
use App\Models\SecureFile;
use App\Models\SecureNote;
use App\Models\User;
use App\Models\VaultItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Helpers\AuthHelper;
use Tests\Helpers\TenantHelper;
use Tests\TestCase;

class SearchAndOrganizationTest extends TestCase
{
    use AuthHelper, RefreshDatabase, TenantHelper;

    private function setupTenantAndUser(): array
    {
        $tenant = $this->createTenant();
        $user = $this->createUser(['email' => 'user@example.com', 'name' => 'Test User']);
        $this->attachUserToTenant($user, $tenant, 'admin');
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

    private function createPersonalVaultItem(User $user, array $overrides = []): PersonalVaultItem
    {
        return PersonalVaultItem::create(array_merge([
            'user_id' => $user->id,
            'name' => 'GitHub Deploy Key',
            'type' => 'password',
            'username' => 'user',
            'password' => 'secret',
            'url' => 'https://github.com',
        ], $overrides));
    }

    public function test_global_search_returns_all_types(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();
        $this->createPersonalVaultItem($user, ['name' => 'GitHub Token']);

        SecureFile::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'name' => 'GitHub Config.pdf',
            'description' => 'GitHub configuration',
        ]);

        SecureNote::create([
            'tenant_id' => $tenant->id, 'user_id' => $user->id,
            'title' => 'GitHub Notes', 'content' => 'encrypted_content',
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson('/api/v1/search?q=GitHub');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['vault_items', 'files', 'notes']]);
        $this->assertNotEmpty($response->json('data.vault_items'));
        $this->assertNotEmpty($response->json('data.files'));
        $this->assertNotEmpty($response->json('data.notes'));
    }

    public function test_search_filtered_by_type(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();
        $this->createPersonalVaultItem($user, ['name' => 'GitHub Token']);
        SecureNote::create([
            'tenant_id' => $tenant->id, 'user_id' => $user->id,
            'title' => 'GitHub Notes', 'content' => 'encrypted',
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson('/api/v1/search?q=GitHub&type=vault_items');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data.vault_items'));
        $this->assertArrayNotHasKey('notes', $response->json('data'));
    }

    public function test_search_only_returns_accessible_resources(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $other = $this->createUser(['email' => 'other@example.com', 'name' => 'Other']);
        $this->attachUserToTenant($other, $tenant, 'member');
        $this->createPersonalVaultItem($other, ['name' => 'Other GitHub Token']);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson('/api/v1/search?q=GitHub');

        $response->assertStatus(200);
        $this->assertEmpty($response->json('data.vault_items'));
    }

    public function test_search_does_not_search_encrypted_fields(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();
        $this->createPersonalVaultItem($user, [
            'name' => 'My Item',
            'password' => 'supersecretpassword123',
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson('/api/v1/search?q=supersecretpassword123');

        $response->assertStatus(200);
        $this->assertEmpty($response->json('data.vault_items'));
    }

    public function test_search_results_limited_per_type(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        for ($i = 0; $i < 25; $i++) {
            $this->createPersonalVaultItem($user, ['name' => "GitHub Item {$i}"]);
        }

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson('/api/v1/search?q=GitHub');

        $response->assertStatus(200);
        $this->assertCount(20, $response->json('data.vault_items'));
    }

    public function test_recently_accessed(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();
        $item = $this->createPersonalVaultItem($user, ['name' => 'Accessed Item']);

        ResourceView::create([
            'user_id' => $user->id,
            'resource_type' => PersonalVaultItem::class,
            'resource_id' => $item->id,
            'viewed_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson('/api/v1/recent');

        $response->assertStatus(200);
        $items = $response->json('data')[PersonalVaultItem::class] ?? [];
        $this->assertNotEmpty($items);
    }

    public function test_recently_created(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();
        $this->createPersonalVaultItem($user, ['name' => 'New Item']);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson('/api/v1/recent/created');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data.vault_items'));
    }

    public function test_expiring_soon(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();
        $item = VaultItem::create([
            'tenant_id' => $tenant->id, 'team_id' => null, 'user_id' => $user->id,
            'name' => 'Shared Item', 'type' => 'password', 'username' => 'u', 'password' => 'p',
        ]);

        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => VaultItem::class,
            'grantable_id' => $item->id,
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'permission' => 'view',
            'views_count' => 0,
            'granted_by' => $user->id,
            'granted_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson('/api/v1/expiring');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data.access_grants'));
    }

    public function test_filter_by_tag(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $item = $this->createPersonalVaultItem($user, ['name' => 'Tagged Item']);
        $tag = PersonalVaultTag::create([
            'user_id' => $user->id,
            'name' => 'ci-cd',
        ]);
        $item->tags()->attach($tag->id);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson('/api/v1/vault/items?tag=ci-cd');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_filter_by_folder(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $folder = PersonalVaultFolder::create([
            'user_id' => $user->id,
            'name' => 'My Folder',
        ]);

        $this->createPersonalVaultItem($user, [
            'name' => 'Folder Item',
            'folder_id' => $folder->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson("/api/v1/vault/items?folder_id={$folder->id}");

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_filter_shared_only(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $other = $this->createUser(['email' => 'other@example.com']);
        $this->attachUserToTenant($other, $tenant, 'member');
        $item = VaultItem::create([
            'tenant_id' => $tenant->id, 'team_id' => null, 'user_id' => $other->id,
            'name' => 'Shared Item', 'type' => 'password', 'username' => 'u', 'password' => 'p',
        ]);

        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => VaultItem::class,
            'grantable_id' => $item->id,
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'permission' => 'view',
            'views_count' => 0,
            'granted_by' => $other->id,
            'granted_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson('/api/v1/vault/items?shared=true');

        $response->assertStatus(200);
    }

    public function test_sort_by_name_ascending(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();
        $this->createPersonalVaultItem($user, ['name' => 'Zebra']);
        $this->createPersonalVaultItem($user, ['name' => 'Alpha']);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson('/api/v1/vault/items?sort=name');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertSame('Alpha', $data[0]['name']);
    }

    public function test_sort_by_name_descending(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();
        $this->createPersonalVaultItem($user, ['name' => 'Alpha']);
        $this->createPersonalVaultItem($user, ['name' => 'Zebra']);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson('/api/v1/vault/items?sort=-name');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertSame('Zebra', $data[0]['name']);
    }

    public function test_default_sort_is_newest(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();
        $this->createPersonalVaultItem($user, ['name' => 'First']);
        Carbon::setTestNow(now()->addMinute());
        $this->createPersonalVaultItem($user, ['name' => 'Second']);
        Carbon::setTestNow();

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson('/api/v1/vault/items');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertSame('Second', $data[0]['name']);
    }

    public function test_empty_query_returns_empty(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();
        $this->createPersonalVaultItem($user, ['name' => 'Some Item']);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson('/api/v1/search?q=');

        $response->assertStatus(200);
        $this->assertSame(0, $response->json('meta.total_results'));
    }
}
