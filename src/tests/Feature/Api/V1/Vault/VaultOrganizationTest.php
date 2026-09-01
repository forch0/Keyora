<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Vault;

use App\Models\PersonalVaultFolder;
use App\Models\PersonalVaultItem;
use App\Models\PersonalVaultTag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AuthHelper;
use Tests\TestCase;

class VaultOrganizationTest extends TestCase
{
    use AuthHelper, RefreshDatabase;

    public function test_user_can_create_folder(): void
    {
        [, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/folders', [
                'name' => 'Work Credentials',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Work Credentials']);
    }

    public function test_user_can_create_nested_folder(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $parent = PersonalVaultFolder::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/folders', [
                'name' => 'Sub Folder',
                'parent_id' => $parent->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Sub Folder', 'parent_id' => $parent->id]);
    }

    public function test_user_can_list_folders_as_tree(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $parent = PersonalVaultFolder::factory()->create(['user_id' => $user->id, 'name' => 'Parent']);
        PersonalVaultFolder::factory()->create(['user_id' => $user->id, 'parent_id' => $parent->id, 'name' => 'Child']);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/vault/folders');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Parent'])
            ->assertJsonPath('data.0.children.0.name', 'Child');
    }

    public function test_deleting_folder_moves_items_to_root(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $folder = PersonalVaultFolder::factory()->create(['user_id' => $user->id]);
        $item = PersonalVaultItem::factory()->create(['user_id' => $user->id, 'folder_id' => $folder->id]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->deleteJson("/api/v1/vault/folders/{$folder->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('personal_vault_folders', ['id' => $folder->id]);
        $this->assertDatabaseHas('personal_vault_items', ['id' => $item->id, 'folder_id' => null]);
    }

    public function test_user_can_create_tag(): void
    {
        [, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/tags', [
                'name' => 'important',
                'color' => '#ff0000',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'important', 'color' => '#ff0000']);
    }

    public function test_user_can_assign_tags_to_item(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $tag1 = PersonalVaultTag::factory()->create(['user_id' => $user->id, 'name' => 'work']);
        $tag2 = PersonalVaultTag::factory()->create(['user_id' => $user->id, 'name' => 'social']);
        $item = PersonalVaultItem::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->putJson("/api/v1/vault/items/{$item->id}", [
                'tag_ids' => [$tag1->id, $tag2->id],
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('personal_vault_item_tag', ['item_id' => $item->id, 'tag_id' => $tag1->id]);
        $this->assertDatabaseHas('personal_vault_item_tag', ['item_id' => $item->id, 'tag_id' => $tag2->id]);
    }

    public function test_user_can_filter_by_tag(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $tag = PersonalVaultTag::factory()->create(['user_id' => $user->id]);
        $taggedItem = PersonalVaultItem::factory()->create(['user_id' => $user->id, 'name' => 'Tagged']);
        $untaggedItem = PersonalVaultItem::factory()->create(['user_id' => $user->id, 'name' => 'Untagged']);

        $taggedItem->tags()->attach($tag->id);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson("/api/v1/vault/items?tag_id={$tag->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Tagged'])
            ->assertJsonMissing(['name' => 'Untagged']);
    }

    public function test_deleting_tag_removes_from_items(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $tag = PersonalVaultTag::factory()->create(['user_id' => $user->id]);
        $item = PersonalVaultItem::factory()->create(['user_id' => $user->id]);
        $item->tags()->attach($tag->id);

        $response = $this->withHeaders($this->authHeaders($token))
            ->deleteJson("/api/v1/vault/tags/{$tag->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('personal_vault_item_tag', ['tag_id' => $tag->id]);
    }

    public function test_user_can_toggle_favorite(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $item = PersonalVaultItem::factory()->create(['user_id' => $user->id, 'favorite' => false]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/vault/items/{$item->id}/favorite");

        $response->assertStatus(200)
            ->assertJsonFragment(['favorite' => true]);

        // Toggle back
        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/vault/items/{$item->id}/favorite");

        $response->assertStatus(200)
            ->assertJsonFragment(['favorite' => false]);
    }

    public function test_user_can_view_favorites(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        PersonalVaultItem::factory()->create(['user_id' => $user->id, 'favorite' => true, 'name' => 'Fav']);
        PersonalVaultItem::factory()->create(['user_id' => $user->id, 'favorite' => false, 'name' => 'NotFav']);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/vault/items/favorites');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Fav'])
            ->assertJsonMissing(['name' => 'NotFav']);
    }

    public function test_user_can_archive_item(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $item = PersonalVaultItem::factory()->create(['user_id' => $user->id, 'archived_at' => null]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/vault/items/{$item->id}/archive");

        $response->assertStatus(204);
        $this->assertNotNull($item->fresh()->archived_at);
    }

    public function test_user_can_restore_item(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $item = PersonalVaultItem::factory()->create(['user_id' => $user->id, 'archived_at' => now()]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/vault/items/{$item->id}/restore");

        $response->assertStatus(204);
        $this->assertNull($item->fresh()->archived_at);
    }

    public function test_user_can_search_by_name(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        PersonalVaultItem::factory()->create(['user_id' => $user->id, 'name' => 'GitHub Login']);
        PersonalVaultItem::factory()->create(['user_id' => $user->id, 'name' => 'GitLab Token']);
        PersonalVaultItem::factory()->create(['user_id' => $user->id, 'name' => 'AWS Console']);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/vault/search?q=Git');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'GitHub Login'])
            ->assertJsonFragment(['name' => 'GitLab Token'])
            ->assertJsonMissing(['name' => 'AWS Console']);
    }

    public function test_user_can_search_by_url(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        PersonalVaultItem::factory()->create(['user_id' => $user->id, 'url' => 'https://github.com/login']);
        PersonalVaultItem::factory()->create(['user_id' => $user->id, 'url' => 'https://example.com']);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/vault/search?q=github.com');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_search_cannot_find_encrypted_fields(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        PersonalVaultItem::factory()->create([
            'user_id' => $user->id,
            'name' => 'My Bank',
            'password' => 'super-secret-password',
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/vault/search?q=super-secret-password');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_recently_accessed_updates_timestamp(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $item = PersonalVaultItem::factory()->create(['user_id' => $user->id, 'last_accessed_at' => null]);

        $this->withHeaders($this->authHeaders($token))
            ->getJson("/api/v1/vault/items/{$item->id}")
            ->assertStatus(200);

        $this->assertNotNull($item->fresh()->last_accessed_at);
    }

    public function test_user_can_view_recent_items(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $item1 = PersonalVaultItem::factory()->create(['user_id' => $user->id, 'last_accessed_at' => now()->subHour()]);
        $item2 = PersonalVaultItem::factory()->create(['user_id' => $user->id, 'last_accessed_at' => now()->subMinutes(5)]);
        PersonalVaultItem::factory()->create(['user_id' => $user->id, 'last_accessed_at' => null]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/vault/items/recent');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            // Most recent first
            ->assertJsonPath('data.0.id', $item2->id)
            ->assertJsonPath('data.1.id', $item1->id);
    }

    public function test_cannot_access_other_users_folders(): void
    {
        [, $token] = $this->createAndAuthUser();

        $otherUser = $this->createUser(['email' => 'other@example.com']);
        $folder = PersonalVaultFolder::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson("/api/v1/vault/folders/{$folder->id}");

        $response->assertStatus(404);
    }

    public function test_cannot_access_other_users_tags(): void
    {
        [, $token] = $this->createAndAuthUser();

        $otherUser = $this->createUser(['email' => 'other@example.com']);
        $tag = PersonalVaultTag::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->putJson("/api/v1/vault/tags/{$tag->id}", ['name' => 'hacked']);

        $response->assertStatus(404);
    }
}
