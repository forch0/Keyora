<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Notes;

use App\Models\AccessGrant;
use App\Models\NoteFolder;
use App\Models\NoteTag;
use App\Models\SecureNote;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AuthHelper;
use Tests\Helpers\TenantHelper;
use Tests\TestCase;

class SecureNoteTest extends TestCase
{
    use AuthHelper, RefreshDatabase, TenantHelper;

    private function setupTenantAndAdmin(): array
    {
        $tenant = $this->createTenant();
        $user = $this->createUser(['email' => 'user@example.com']);
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

    public function test_user_can_create_personal_note(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndAdmin();

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/notes', [
                'title' => 'My Personal Note',
                'content' => 'This is a secret note.',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'My Personal Note']);
        $this->assertDatabaseHas('secure_notes', [
            'title' => 'My Personal Note',
            'user_id' => $user->id,
            'tenant_id' => null,
            'team_id' => null,
        ]);
    }

    public function test_user_can_create_team_note(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndAdmin();

        $team = Team::create(['tenant_id' => $tenant->id, 'name' => 'Eng', 'created_by' => $user->id]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/notes', [
                'title' => 'Team Note',
                'content' => 'Team content.',
                'team_id' => $team->id,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('secure_notes', [
            'title' => 'Team Note',
            'team_id' => $team->id,
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_note_content_is_encrypted(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndAdmin();

        $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/notes', [
                'title' => 'Encrypted Note',
                'content' => 'This is plaintext content that should be encrypted.',
            ]);

        $note = SecureNote::where('title', 'Encrypted Note')->first();
        $this->assertNotNull($note);

        // DB content should NOT be the plaintext
        $this->assertNotEquals('This is plaintext content that should be encrypted.', $note->getRawOriginal('content'));
    }

    public function test_note_title_is_not_encrypted(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndAdmin();

        $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/notes', [
                'title' => 'Plaintext Title',
                'content' => 'Some content.',
            ]);

        $this->assertDatabaseHas('secure_notes', [
            'title' => 'Plaintext Title',
        ]);
    }

    public function test_user_can_list_notes(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndAdmin();

        SecureNote::create([
            'user_id' => $user->id,
            'tenant_id' => null,
            'team_id' => null,
            'title' => 'Note 1',
            'content' => 'Content 1',
            'content_format' => 'markdown',
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson('/api/v1/notes');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_user_can_view_note(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndAdmin();

        $note = SecureNote::create([
            'user_id' => $user->id,
            'tenant_id' => null,
            'team_id' => null,
            'title' => 'Viewable Note',
            'content' => 'Decrypt me.',
            'content_format' => 'markdown',
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson("/api/v1/notes/{$note->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'title' => 'Viewable Note',
                'content' => 'Decrypt me.',
            ]);
    }

    public function test_user_can_update_note(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndAdmin();

        $note = SecureNote::create([
            'user_id' => $user->id,
            'tenant_id' => null,
            'team_id' => null,
            'title' => 'Original Title',
            'content' => 'Original content.',
            'content_format' => 'markdown',
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->putJson("/api/v1/notes/{$note->id}", [
                'title' => 'Updated Title',
                'content' => 'Updated content.',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Updated Title', 'content' => 'Updated content.']);
    }

    public function test_user_can_delete_note(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndAdmin();

        $note = SecureNote::create([
            'user_id' => $user->id,
            'tenant_id' => null,
            'team_id' => null,
            'title' => 'Delete Me',
            'content' => 'Content.',
            'content_format' => 'markdown',
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->deleteJson("/api/v1/notes/{$note->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('secure_notes', ['id' => $note->id]);
    }

    public function test_user_can_pin_note(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndAdmin();

        $note = SecureNote::create([
            'user_id' => $user->id,
            'tenant_id' => null,
            'team_id' => null,
            'title' => 'Pin Me',
            'content' => 'Content.',
            'content_format' => 'markdown',
            'is_pinned' => false,
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/notes/{$note->id}/pin");

        $response->assertStatus(200)
            ->assertJsonFragment(['is_pinned' => true]);
    }

    public function test_user_can_share_note(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndAdmin();

        $note = SecureNote::create([
            'user_id' => $user->id,
            'tenant_id' => null,
            'team_id' => null,
            'title' => 'Share Me',
            'content' => 'Content.',
            'content_format' => 'markdown',
        ]);

        $recipient = $this->createUser(['email' => 'recipient@example.com']);
        $this->attachUserToTenant($recipient, $tenant, 'member');

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/notes/{$note->id}/access", [
                'subject_type' => User::class,
                'subject_id' => $recipient->id,
                'permission' => 'view',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('access_grants', [
            'grantable_type' => SecureNote::class,
            'grantable_id' => $note->id,
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
        ]);
    }

    public function test_shared_user_can_view_note(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndAdmin();

        $note = SecureNote::create([
            'user_id' => $user->id,
            'tenant_id' => null,
            'team_id' => null,
            'title' => 'Shared Note',
            'content' => 'Shared content.',
            'content_format' => 'markdown',
        ]);

        $recipient = $this->createUser(['email' => 'recipient@example.com']);
        $this->attachUserToTenant($recipient, $tenant, 'member');
        $recipientToken = $recipient->createToken('test')->plainTextToken;

        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => SecureNote::class,
            'grantable_id' => $note->id,
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
            'permission' => 'view',
            'granted_by' => $user->id,
            'granted_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($recipientToken, $tenant->id))
            ->getJson("/api/v1/notes/{$note->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Shared Note', 'content' => 'Shared content.']);
    }

    public function test_non_shared_user_cannot_view(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndAdmin();

        $note = SecureNote::create([
            'user_id' => $user->id,
            'tenant_id' => null,
            'team_id' => null,
            'title' => 'Private Note',
            'content' => 'Private content.',
            'content_format' => 'markdown',
        ]);

        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');
        $memberToken = $member->createToken('test')->plainTextToken;

        $response = $this->withHeaders($this->authHeaders($memberToken, $tenant->id))
            ->getJson("/api/v1/notes/{$note->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_search_notes(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndAdmin();

        SecureNote::create([
            'user_id' => $user->id,
            'tenant_id' => null,
            'team_id' => null,
            'title' => 'Laravel Tips',
            'content' => 'Content about Laravel.',
            'content_format' => 'markdown',
        ]);

        SecureNote::create([
            'user_id' => $user->id,
            'tenant_id' => null,
            'team_id' => null,
            'title' => 'Vue Tricks',
            'content' => 'Content about Vue.',
            'content_format' => 'markdown',
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson('/api/v1/notes/search?q=Laravel');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
        $this->assertSame('Laravel Tips', $response->json('data.0.title'));
    }

    public function test_search_cannot_find_encrypted_content(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndAdmin();

        $secretContent = 'SuperSecretPhrase123';

        SecureNote::create([
            'user_id' => $user->id,
            'tenant_id' => null,
            'team_id' => null,
            'title' => 'Innocent Title',
            'content' => $secretContent,
            'content_format' => 'markdown',
        ]);

        // Search for the content text — should NOT find it because content is encrypted
        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson('/api/v1/notes/search?q=SuperSecretPhrase123');

        $response->assertStatus(200);
        $this->assertEmpty($response->json('data'));
    }

    public function test_notes_can_be_organized_into_folders(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndAdmin();

        $folder = NoteFolder::create([
            'tenant_id' => null,
            'team_id' => null,
            'user_id' => $user->id,
            'name' => 'My Notes',
            'created_by' => $user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/notes', [
                'title' => 'Foldered Note',
                'content' => 'Content.',
                'folder_id' => $folder->id,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('secure_notes', [
            'title' => 'Foldered Note',
            'folder_id' => $folder->id,
        ]);
    }

    public function test_notes_can_be_tagged(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndAdmin();

        $note = SecureNote::create([
            'user_id' => $user->id,
            'tenant_id' => null,
            'team_id' => null,
            'title' => 'Tagged Note',
            'content' => 'Content.',
            'content_format' => 'markdown',
        ]);

        $tag = NoteTag::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'name' => 'important',
            'color' => '#ff0000',
        ]);

        $note->tags()->attach($tag->id);

        $this->assertDatabaseHas('secure_note_tag', [
            'note_id' => $note->id,
            'tag_id' => $tag->id,
        ]);

        // Verify via API that tags are loaded
        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson("/api/v1/notes/{$note->id}");

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data.tags'));
    }
}
