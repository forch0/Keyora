<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\SoftDeletes;

use App\Models\AccessGrant;
use App\Models\ActivityLog;
use App\Models\PersonalVaultItem;
use App\Models\SecureFile;
use App\Models\SecureNote;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Helpers\AuthHelper;
use Tests\Helpers\TenantHelper;
use Tests\TestCase;

class SoftDeletesTest extends TestCase
{
    use AuthHelper, RefreshDatabase, TenantHelper;

    public function test_personal_vault_item_soft_deleted(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $item = PersonalVaultItem::create([
            'user_id' => $user->id,
            'name' => 'Test Item',
            'type' => 'password',
            'username' => 'user1',
            'password' => 'secret',
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->deleteJson("/api/v1/vault/items/{$item->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('personal_vault_items', ['id' => $item->id]);
        // Row still exists in the database
        $this->assertDatabaseHas('personal_vault_items', ['id' => $item->id]);
    }

    public function test_trashed_items_excluded_from_list(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $item1 = PersonalVaultItem::create([
            'user_id' => $user->id,
            'name' => 'Active Item',
            'type' => 'password',
            'username' => 'user1',
            'password' => 'secret',
        ]);
        $item2 = PersonalVaultItem::create([
            'user_id' => $user->id,
            'name' => 'Trashed Item',
            'type' => 'password',
            'username' => 'user2',
            'password' => 'secret',
        ]);
        $item2->delete();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/vault/items');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Active Item']);
        $response->assertJsonMissing(['name' => 'Trashed Item']);
    }

    public function test_restore_personal_vault_item(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $item = PersonalVaultItem::create([
            'user_id' => $user->id,
            'name' => 'To Restore',
            'type' => 'password',
            'username' => 'user1',
            'password' => 'secret',
        ]);
        $item->delete();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/vault/trash/{$item->id}/restore");

        $response->assertStatus(200);
        $this->assertDatabaseHas('personal_vault_items', [
            'id' => $item->id,
            'deleted_at' => null,
        ]);
    }

    public function test_force_delete_personal_vault_item(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $item = PersonalVaultItem::create([
            'user_id' => $user->id,
            'name' => 'To Force Delete',
            'type' => 'password',
            'username' => 'user1',
            'password' => 'secret',
        ]);
        $item->delete();

        $response = $this->withHeaders($this->authHeaders($token))
            ->deleteJson("/api/v1/vault/trash/{$item->id}/force");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('personal_vault_items', ['id' => $item->id]);
    }

    public function test_team_soft_deleted(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant();
        $this->attachUserToTenant($user, $tenant, 'admin');
        $this->setupTenantContext($tenant);

        $team = Team::create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Team',
            'created_by' => $user->id,
        ]);

        $response = $this->withHeaders(array_merge(
            $this->authHeaders($token),
            ['X-Tenant-ID' => (string) $tenant->id],
        ))->deleteJson("/api/v1/tenants/{$tenant->id}/teams/{$team->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('teams', ['id' => $team->id]);
    }

    public function test_restore_team(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant();
        $this->attachUserToTenant($user, $tenant, 'admin');
        $this->setupTenantContext($tenant);

        $team = Team::create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Team',
            'created_by' => $user->id,
        ]);
        $team->delete();

        $response = $this->withHeaders(array_merge(
            $this->authHeaders($token),
            ['X-Tenant-ID' => (string) $tenant->id],
        ))->postJson("/api/v1/tenants/{$tenant->id}/teams/{$team->id}/restore");

        $response->assertStatus(200);
        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'deleted_at' => null,
        ]);
    }

    public function test_secure_file_force_delete_removes_file(): void
    {
        Storage::fake('private');

        [$user, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant();
        $this->attachUserToTenant($user, $tenant, 'admin');
        $this->setupTenantContext($tenant);

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $uploadResponse = $this->withHeaders(array_merge(
            $this->authHeaders($token),
            ['X-Tenant-ID' => (string) $tenant->id],
        ))->postJson('/api/v1/files', [
            'file' => $file,
            'name' => 'Test Document',
        ]);

        $uploadResponse->assertStatus(201);
        $fileId = $uploadResponse->json('data.id');
        $filePath = SecureFile::find($fileId)->path;

        // Soft delete
        $this->withHeaders(array_merge(
            $this->authHeaders($token),
            ['X-Tenant-ID' => (string) $tenant->id],
        ))->deleteJson("/api/v1/files/{$fileId}");

        // Force delete
        $response = $this->withHeaders(array_merge(
            $this->authHeaders($token),
            ['X-Tenant-ID' => (string) $tenant->id],
        ))->deleteJson("/api/v1/files/trash/{$fileId}/force");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('secure_files', ['id' => $fileId]);
        Storage::disk('private')->assertMissing($filePath);
    }

    public function test_trash_listing_returns_only_trashed(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $activeItem = PersonalVaultItem::create([
            'user_id' => $user->id,
            'name' => 'Active',
            'type' => 'password',
            'username' => 'u1',
            'password' => 's',
        ]);
        $trashedItem = PersonalVaultItem::create([
            'user_id' => $user->id,
            'name' => 'Trashed',
            'type' => 'password',
            'username' => 'u2',
            'password' => 's',
        ]);
        $trashedItem->delete();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/vault/trash');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Trashed']);
        $response->assertJsonMissing(['name' => 'Active']);
    }

    public function test_empty_trash_deletes_all(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $item1 = PersonalVaultItem::create([
            'user_id' => $user->id,
            'name' => 'Item 1',
            'type' => 'password',
            'username' => 'u1',
            'password' => 's',
        ]);
        $item2 = PersonalVaultItem::create([
            'user_id' => $user->id,
            'name' => 'Item 2',
            'type' => 'password',
            'username' => 'u2',
            'password' => 's',
        ]);
        $item1->delete();
        $item2->delete();

        $response = $this->withHeaders($this->authHeaders($token))
            ->deleteJson('/api/v1/vault/trash');

        $response->assertStatus(200);
        $response->assertJsonFragment(['deleted' => 2]);
        $this->assertDatabaseMissing('personal_vault_items', ['id' => $item1->id]);
        $this->assertDatabaseMissing('personal_vault_items', ['id' => $item2->id]);
    }

    public function test_activity_log_not_soft_deletable(): void
    {
        // ActivityLog should NOT have a deleted_at column
        $this->assertFalse(
            in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(ActivityLog::class)),
            'ActivityLog should not use SoftDeletes.'
        );
    }

    public function test_force_delete_revokes_access_grants(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant();
        $this->attachUserToTenant($user, $tenant, 'admin');
        $this->setupTenantContext($tenant);

        $note = SecureNote::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'title' => 'Test Note',
            'content' => 'Secret content',
        ]);

        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => SecureNote::class,
            'grantable_id' => $note->id,
            'subject_type' => SecureNote::class,
            'subject_id' => $note->id,
            'grantee_id' => $user->id,
            'permission' => 'view',
            'granted_by' => $user->id,
        ]);

        // Soft delete
        $this->withHeaders(array_merge(
            $this->authHeaders($token),
            ['X-Tenant-ID' => (string) $tenant->id],
        ))->deleteJson("/api/v1/notes/{$note->id}");

        // Force delete
        $response = $this->withHeaders(array_merge(
            $this->authHeaders($token),
            ['X-Tenant-ID' => (string) $tenant->id],
        ))->deleteJson("/api/v1/notes/trash/{$note->id}/force");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('secure_notes', ['id' => $note->id]);
        $this->assertDatabaseMissing('access_grants', [
            'grantable_type' => SecureNote::class,
            'grantable_id' => $note->id,
        ]);
    }

    public function test_force_delete_requires_reauth(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $item = PersonalVaultItem::create([
            'user_id' => $user->id,
            'name' => 'Test',
            'type' => 'password',
            'username' => 'u',
            'password' => 's',
        ]);
        $item->delete();

        // Make the token old enough to require re-auth (>15 min)
        $user->tokens()->update(['created_at' => now()->subMinutes(20)]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->deleteJson("/api/v1/vault/trash/{$item->id}/force");

        $response->assertStatus(423);
    }
}
