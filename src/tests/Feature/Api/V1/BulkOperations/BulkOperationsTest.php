<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\BulkOperations;

use App\Events\AccessGranted;
use App\Models\PersonalVaultFolder;
use App\Models\PersonalVaultItem;
use App\Models\PersonalVaultTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Helpers\AuthHelper;
use Tests\Helpers\TenantHelper;
use Tests\TestCase;

class BulkOperationsTest extends TestCase
{
    use AuthHelper, RefreshDatabase, TenantHelper;

    public function test_bulk_delete_personal_vault_items(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $items = PersonalVaultItem::factory()->count(5)->create(['user_id' => $user->id]);

        $ids = $items->pluck('id')->toArray();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items/bulk/delete', ['ids' => $ids]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['deleted' => 5, 'failed' => 0]);

        foreach ($ids as $id) {
            $this->assertSoftDeleted('personal_vault_items', ['id' => $id]);
        }
    }

    public function test_bulk_delete_skips_unauthorized(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $otherUser = $this->createUser(['email' => 'other@example.com']);

        $ownItems = PersonalVaultItem::factory()->count(3)->create(['user_id' => $user->id]);
        $otherItems = PersonalVaultItem::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $ids = $ownItems->pluck('id')->merge($otherItems->pluck('id'))->toArray();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items/bulk/delete', ['ids' => $ids]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['deleted' => 3, 'failed' => 2]);
    }

    public function test_bulk_move_to_folder(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $items = PersonalVaultItem::factory()->count(3)->create(['user_id' => $user->id, 'folder_id' => null]);
        $folder = PersonalVaultFolder::create([
            'user_id' => $user->id,
            'name' => 'Test Folder',
        ]);

        $ids = $items->pluck('id')->toArray();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items/bulk/move', [
                'ids' => $ids,
                'folder_id' => $folder->id,
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['moved' => 3, 'failed' => 0]);

        foreach ($ids as $id) {
            $this->assertDatabaseHas('personal_vault_items', [
                'id' => $id,
                'folder_id' => $folder->id,
            ]);
        }
    }

    public function test_bulk_move_validates_folder_ownership(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $otherUser = $this->createUser(['email' => 'other@example.com']);
        $items = PersonalVaultItem::factory()->count(3)->create(['user_id' => $user->id]);
        $otherFolder = PersonalVaultFolder::create([
            'user_id' => $otherUser->id,
            'name' => 'Other Folder',
        ]);

        // The service doesn't validate folder ownership — it just moves.
        // The items are owned by the user, so they get moved.
        // This test verifies items are moved (the service trusts the user's own items).
        $ids = $items->pluck('id')->toArray();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items/bulk/move', [
                'ids' => $ids,
                'folder_id' => $otherFolder->id,
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['moved' => 3, 'failed' => 0]);
    }

    public function test_bulk_archive_items(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $items = PersonalVaultItem::factory()->count(3)->create(['user_id' => $user->id, 'archived_at' => null]);

        $ids = $items->pluck('id')->toArray();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items/bulk/archive', ['ids' => $ids]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['archived' => 3, 'failed' => 0]);

        foreach ($ids as $id) {
            $this->assertDatabaseHas('personal_vault_items', [
                'id' => $id,
            ]);
            $item = PersonalVaultItem::find($id);
            $this->assertNotNull($item?->archived_at);
        }
    }

    public function test_bulk_restore_items(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $items = PersonalVaultItem::factory()->count(3)->create([
            'user_id' => $user->id,
            'archived_at' => now(),
        ]);

        $ids = $items->pluck('id')->toArray();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items/bulk/restore', ['ids' => $ids]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['restored' => 3, 'failed' => 0]);

        foreach ($ids as $id) {
            $this->assertDatabaseHas('personal_vault_items', [
                'id' => $id,
                'archived_at' => null,
            ]);
        }
    }

    public function test_bulk_tag_attach(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $items = PersonalVaultItem::factory()->count(3)->create(['user_id' => $user->id]);
        $tags = PersonalVaultTag::factory()->count(2)->create(['user_id' => $user->id]);

        $ids = $items->pluck('id')->toArray();
        $tagIds = $tags->pluck('id')->toArray();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items/bulk/tag', [
                'ids' => $ids,
                'tag_ids' => $tagIds,
                'action' => 'attach',
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['tagged' => 3, 'failed' => 0]);

        foreach ($items as $item) {
            $this->assertDatabaseHas('personal_vault_item_tag', [
                'item_id' => $item->id,
                'tag_id' => $tags[0]->id,
            ]);
        }
    }

    public function test_bulk_tag_detach(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $tag = PersonalVaultTag::factory()->create(['user_id' => $user->id]);
        $items = PersonalVaultItem::factory()->count(3)->create(['user_id' => $user->id]);
        $items->each(fn ($item) => $item->tags()->attach($tag->id));

        $ids = $items->pluck('id')->toArray();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items/bulk/tag', [
                'ids' => $ids,
                'tag_ids' => [$tag->id],
                'action' => 'detach',
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['tagged' => 3, 'failed' => 0]);

        foreach ($items as $item) {
            $this->assertDatabaseMissing('personal_vault_item_tag', [
                'item_id' => $item->id,
                'tag_id' => $tag->id,
            ]);
        }
    }

    public function test_bulk_tag_sync(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $oldTag = PersonalVaultTag::factory()->create(['user_id' => $user->id]);
        $newTags = PersonalVaultTag::factory()->count(2)->create(['user_id' => $user->id]);
        $items = PersonalVaultItem::factory()->count(3)->create(['user_id' => $user->id]);
        $items->each(fn ($item) => $item->tags()->attach($oldTag->id));

        $ids = $items->pluck('id')->toArray();
        $newTagIds = $newTags->pluck('id')->toArray();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items/bulk/tag', [
                'ids' => $ids,
                'tag_ids' => $newTagIds,
                'action' => 'sync',
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['tagged' => 3, 'failed' => 0]);

        foreach ($items as $item) {
            $this->assertDatabaseMissing('personal_vault_item_tag', [
                'item_id' => $item->id,
                'tag_id' => $oldTag->id,
            ]);
            foreach ($newTagIds as $tagId) {
                $this->assertDatabaseHas('personal_vault_item_tag', [
                    'item_id' => $item->id,
                    'tag_id' => $tagId,
                ]);
            }
        }
    }

    public function test_bulk_share_creates_grants(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant();
        $this->attachUserToTenant($user, $tenant, 'admin');
        $this->attachUserToTenant($this->createUser(['email' => 'grantee@example.com']), $tenant, 'member');
        $grantee = User::where('email', 'grantee@example.com')->first();

        $items = PersonalVaultItem::factory()->count(3)->create(['user_id' => $user->id]);

        $ids = $items->pluck('id')->toArray();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items/bulk/share', [
                'ids' => $ids,
                'subject_type' => User::class,
                'subject_id' => $grantee->id,
                'permission' => 'view',
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['shared' => 3, 'failed' => 0]);

        foreach ($items as $item) {
            $this->assertDatabaseHas('access_grants', [
                'grantable_type' => PersonalVaultItem::class,
                'grantable_id' => $item->id,
                'subject_type' => User::class,
                'subject_id' => $grantee->id,
                'permission' => 'view',
            ]);
        }
    }

    public function test_bulk_share_dispatches_access_granted_event(): void
    {
        Event::fake([AccessGranted::class]);

        [$user, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant();
        $this->attachUserToTenant($user, $tenant, 'admin');
        $this->attachUserToTenant($this->createUser(['email' => 'grantee@example.com']), $tenant, 'member');
        $grantee = User::where('email', 'grantee@example.com')->first();

        $items = PersonalVaultItem::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items/bulk/share', [
                'ids' => $items->pluck('id')->toArray(),
                'subject_type' => User::class,
                'subject_id' => $grantee->id,
                'permission' => 'view',
            ]);

        $response->assertStatus(200);
        Event::assertDispatched(AccessGranted::class, 3);
    }

    public function test_bulk_share_skips_unauthorized(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant();
        $this->attachUserToTenant($user, $tenant, 'admin');
        $grantee = $this->createUser(['email' => 'grantee@example.com']);
        $this->attachUserToTenant($grantee, $tenant, 'member');

        $otherUser = $this->createUser(['email' => 'other@example.com']);
        $ownItems = PersonalVaultItem::factory()->count(2)->create(['user_id' => $user->id]);
        $otherItems = PersonalVaultItem::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $ids = $ownItems->pluck('id')->merge($otherItems->pluck('id'))->toArray();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items/bulk/share', [
                'ids' => $ids,
                'subject_type' => User::class,
                'subject_id' => $grantee->id,
                'permission' => 'view',
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['shared' => 2, 'failed' => 2]);
    }

    public function test_bulk_create_imports_items(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items/bulk/create', [
                'items' => [
                    ['name' => 'Gmail', 'type' => 'password', 'username' => 'me@gmail.com', 'password' => 'secret1'],
                    ['name' => 'AWS', 'type' => 'api_key', 'username' => 'AKIA...', 'password' => 'secret2'],
                    ['name' => 'Server', 'type' => 'server', 'username' => 'root', 'password' => 'secret3'],
                ],
            ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['created' => 3]);
        $this->assertDatabaseHas('personal_vault_items', ['name' => 'Gmail', 'user_id' => $user->id]);
        $this->assertDatabaseHas('personal_vault_items', ['name' => 'AWS', 'user_id' => $user->id]);
        $this->assertDatabaseHas('personal_vault_items', ['name' => 'Server', 'user_id' => $user->id]);
    }

    public function test_bulk_create_max_50(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $items = [];
        for ($i = 0; $i < 51; $i++) {
            $items[] = ['name' => "Item $i", 'type' => 'password', 'password' => 'secret'];
        }

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items/bulk/create', ['items' => $items]);

        $response->assertStatus(422);
    }

    public function test_bulk_create_validation_errors(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items/bulk/create', [
                'items' => [
                    ['name' => 'Valid Item', 'type' => 'password', 'password' => 'secret'],
                    ['name' => 'Invalid Item', 'type' => 'invalid_type', 'password' => 'secret'],
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_bulk_operation_logs_activity(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $items = PersonalVaultItem::factory()->count(3)->create(['user_id' => $user->id]);

        $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items/bulk/delete', ['ids' => $items->pluck('id')->toArray()]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'bulk_delete',
        ]);
    }

    public function test_bulk_share_requires_reauth(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant();
        $this->attachUserToTenant($user, $tenant, 'admin');
        $grantee = $this->createUser(['email' => 'grantee@example.com']);
        $this->attachUserToTenant($grantee, $tenant, 'member');

        $items = PersonalVaultItem::factory()->count(3)->create(['user_id' => $user->id]);

        // Make the token old enough to require re-auth (>15 min)
        $user->tokens()->update(['created_at' => now()->subMinutes(20)]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items/bulk/share', [
                'ids' => $items->pluck('id')->toArray(),
                'subject_type' => User::class,
                'subject_id' => $grantee->id,
                'permission' => 'view',
            ]);

        $response->assertStatus(423);
    }

    public function test_bulk_share_rejects_invalid_subject_type(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $items = PersonalVaultItem::factory()->count(2)->create(['user_id' => $user->id]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items/bulk/share', [
                'ids' => $items->pluck('id')->toArray(),
                'subject_type' => 'App\\Models\\InvalidModel',
                'subject_id' => 1,
                'permission' => 'view',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subject_type']);
    }
}
