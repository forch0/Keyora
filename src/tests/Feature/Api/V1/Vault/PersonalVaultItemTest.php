<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Vault;

use App\Models\PersonalVaultItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\AuthHelper;
use Tests\TestCase;

class PersonalVaultItemTest extends TestCase
{
    use AuthHelper, RefreshDatabase;

    public function test_user_can_create_password_item(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items', [
                'name' => 'GitHub Login',
                'type' => 'password',
                'username' => 'myuser',
                'password' => 's3cret-pass',
                'url' => 'https://github.com',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'GitHub Login',
                'type' => 'password',
                'username' => 'myuser',
                'password' => 's3cret-pass',
                'url' => 'https://github.com',
            ]);

        $this->assertDatabaseHas('personal_vault_items', [
            'user_id' => $user->id,
            'name' => 'GitHub Login',
            'type' => 'password',
        ]);
    }

    public function test_user_can_create_api_key_item(): void
    {
        [, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items', [
                'name' => 'AWS API Key',
                'type' => 'api_key',
                'password' => 'AKIAIOSFODNN7EXAMPLE',
                'metadata' => [
                    'provider' => 'aws',
                    'key_label' => 'production-key',
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'AWS API Key',
                'type' => 'api_key',
            ])
            ->assertJsonPath('data.metadata.provider', 'aws')
            ->assertJsonPath('data.metadata.key_label', 'production-key');
    }

    public function test_user_can_create_server_credential(): void
    {
        [, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items', [
                'name' => 'Production Server',
                'type' => 'server',
                'username' => 'root',
                'password' => 'server-pass-123',
                'metadata' => [
                    'host' => '10.0.0.1',
                    'port' => 22,
                    'protocol' => 'ssh',
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.metadata.host', '10.0.0.1')
            ->assertJsonPath('data.metadata.port', 22)
            ->assertJsonPath('data.metadata.protocol', 'ssh');
    }

    public function test_user_can_create_database_credential(): void
    {
        [, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items', [
                'name' => 'Production DB',
                'type' => 'database',
                'username' => 'dbadmin',
                'password' => 'db-pass-456',
                'metadata' => [
                    'db_type' => 'postgresql',
                    'host' => 'db.internal',
                    'port' => 5432,
                    'database_name' => 'keyora_prod',
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.metadata.db_type', 'postgresql')
            ->assertJsonPath('data.metadata.database_name', 'keyora_prod');
    }

    public function test_user_can_add_custom_fields(): void
    {
        [, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items', [
                'name' => 'Custom Item',
                'type' => 'password',
                'custom_fields' => [
                    ['key' => 'security_question', 'value' => 'My first pet'],
                    ['key' => 'recovery_code', 'value' => 'ABC-123-XYZ'],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.custom_fields.0.key', 'security_question')
            ->assertJsonPath('data.custom_fields.0.value', 'My first pet')
            ->assertJsonPath('data.custom_fields.1.key', 'recovery_code')
            ->assertJsonPath('data.custom_fields.1.value', 'ABC-123-XYZ');
    }

    public function test_user_can_list_vault_items(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        PersonalVaultItem::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/vault/items');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'type']],
                'links',
                'meta',
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_view_single_item(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $item = PersonalVaultItem::factory()->create([
            'user_id' => $user->id,
            'name' => 'Viewable Item',
            'password' => 'decrypted-value',
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson("/api/v1/vault/items/{$item->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $item->id,
                'name' => 'Viewable Item',
                'password' => 'decrypted-value',
            ]);
    }

    public function test_user_can_update_item(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $item = PersonalVaultItem::factory()->create([
            'user_id' => $user->id,
            'name' => 'Old Name',
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->putJson("/api/v1/vault/items/{$item->id}", [
                'name' => 'New Name',
                'password' => 'new-password',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'New Name', 'password' => 'new-password']);
    }

    public function test_user_can_delete_item(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $item = PersonalVaultItem::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->deleteJson("/api/v1/vault/items/{$item->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('personal_vault_items', ['id' => $item->id]);
    }

    public function test_user_cannot_access_other_users_items(): void
    {
        [, $token] = $this->createAndAuthUser();

        $otherUser = $this->createUser(['email' => 'other@example.com']);
        $item = PersonalVaultItem::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson("/api/v1/vault/items/{$item->id}");

        $response->assertStatus(404);
    }

    public function test_sensitive_fields_are_encrypted_in_db(): void
    {
        [, $token] = $this->createAndAuthUser();

        $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items', [
                'name' => 'Encrypted Test',
                'type' => 'password',
                'username' => 'plaintext-user',
                'password' => 'plaintext-pass',
                'notes' => 'secret notes',
            ]);

        // Read raw from DB (bypassing model's decryption)
        $raw = DB::table('personal_vault_items')->where('name', 'Encrypted Test')->first();

        $this->assertNotEquals('plaintext-user', $raw->username);
        $this->assertNotEquals('plaintext-pass', $raw->password);
        $this->assertNotEquals('secret notes', $raw->notes);
    }

    public function test_name_and_url_are_not_encrypted(): void
    {
        [, $token] = $this->createAndAuthUser();

        $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items', [
                'name' => 'Plaintext Name',
                'type' => 'password',
                'url' => 'https://example.com',
            ]);

        $raw = DB::table('personal_vault_items')->where('name', 'Plaintext Name')->first();

        $this->assertEquals('Plaintext Name', $raw->name);
        $this->assertEquals('https://example.com', $raw->url);
    }

    public function test_encryptable_trait_handles_nulls(): void
    {
        [, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/vault/items', [
                'name' => 'Null Fields Test',
                'type' => 'password',
                'username' => null,
                'password' => null,
                'notes' => null,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.username', null)
            ->assertJsonPath('data.password', null)
            ->assertJsonPath('data.notes', null);
    }

    public function test_filter_items_by_type(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        PersonalVaultItem::factory()->create(['user_id' => $user->id, 'type' => 'password']);
        PersonalVaultItem::factory()->create(['user_id' => $user->id, 'type' => 'password']);
        PersonalVaultItem::factory()->create(['user_id' => $user->id, 'type' => 'api_key']);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/vault/items?type=password');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_filter_favorite_items(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        PersonalVaultItem::factory()->create(['user_id' => $user->id, 'favorite' => true]);
        PersonalVaultItem::factory()->create(['user_id' => $user->id, 'favorite' => true]);
        PersonalVaultItem::factory()->create(['user_id' => $user->id, 'favorite' => false]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/vault/items?favorite=true');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}
