<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Files;

use App\Models\AccessGrant;
use App\Models\SecureFile;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\FileExpiredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Helpers\AuthHelper;
use Tests\Helpers\TenantHelper;
use Tests\TestCase;

class FileAccessTest extends TestCase
{
    use AuthHelper, RefreshDatabase, TenantHelper;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    private function setupTenantAndAdmin(): array
    {
        $tenant = $this->createTenant();
        $owner = $this->createUser(['email' => 'owner@example.com']);
        $this->attachUserToTenant($owner, $tenant, 'admin');
        $this->setupTenantContext($tenant);
        $token = $owner->createToken('test')->plainTextToken;

        return [$tenant, $owner, $token];
    }

    private function authHeaders(string $token, int $tenantId): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenantId,
        ];
    }

    private function uploadFile(Tenant $tenant, User $owner, string $token, string $name = 'doc.txt'): SecureFile
    {
        $file = UploadedFile::fake()->createWithContent($name, 'File content', 'text/plain');

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/files', ['file' => $file]);

        return SecureFile::withoutTenant()->findOrFail($response->json('data.id'));
    }

    public function test_can_share_file_with_individual(): void
    {
        [$tenant, $owner, $token] = $this->setupTenantAndAdmin();
        $file = $this->uploadFile($tenant, $owner, $token);

        $recipient = $this->createUser(['email' => 'recipient@example.com']);
        $this->attachUserToTenant($recipient, $tenant, 'member');

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/files/{$file->id}/access", [
                'subject_type' => User::class,
                'subject_id' => $recipient->id,
                'permission' => 'view',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['permission' => 'view']);
        $this->assertDatabaseHas('access_grants', [
            'grantable_type' => SecureFile::class,
            'grantable_id' => $file->id,
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
        ]);
    }

    public function test_can_share_file_with_team(): void
    {
        [$tenant, $owner, $token] = $this->setupTenantAndAdmin();
        $file = $this->uploadFile($tenant, $owner, $token);

        $team = Team::create(['tenant_id' => $tenant->id, 'name' => 'Eng', 'created_by' => $owner->id]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/files/{$file->id}/access", [
                'subject_type' => Team::class,
                'subject_id' => $team->id,
                'permission' => 'view',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('access_grants', [
            'grantable_type' => SecureFile::class,
            'grantable_id' => $file->id,
            'subject_type' => Team::class,
            'subject_id' => $team->id,
        ]);
    }

    public function test_view_permission_cannot_download(): void
    {
        [$tenant, $owner, $token] = $this->setupTenantAndAdmin();

        // Create file directly to avoid auth state leakage from upload
        $file = SecureFile::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'name' => 'doc.txt',
            'file_path' => "{$tenant->id}/".Str::uuid().'/doc.txt',
            'download_enabled' => true,
        ]);
        Storage::disk('private')->put($file->file_path, 'File content');

        $viewer = $this->createUser(['email' => 'viewer@example.com']);
        $this->attachUserToTenant($viewer, $tenant, 'member');
        $viewerToken = $viewer->createToken('test')->plainTextToken;

        // Grant view-only permission
        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => SecureFile::class,
            'grantable_id' => $file->id,
            'subject_type' => User::class,
            'subject_id' => $viewer->id,
            'permission' => 'view',
            'granted_by' => $owner->id,
            'granted_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($viewerToken, $tenant->id))
            ->get("/api/v1/files/{$file->id}/download");

        $response->assertStatus(403);
    }

    public function test_download_permission_can_download(): void
    {
        [$tenant, $owner, $token] = $this->setupTenantAndAdmin();
        $file = $this->uploadFile($tenant, $owner, $token);

        $downloader = $this->createUser(['email' => 'downloader@example.com']);
        $this->attachUserToTenant($downloader, $tenant, 'member');
        $downloaderToken = $downloader->createToken('test')->plainTextToken;

        // Grant download permission
        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => SecureFile::class,
            'grantable_id' => $file->id,
            'subject_type' => User::class,
            'subject_id' => $downloader->id,
            'permission' => 'download',
            'granted_by' => $owner->id,
            'granted_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($downloaderToken, $tenant->id))
            ->get("/api/v1/files/{$file->id}/download");

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename="doc.txt"');
    }

    public function test_download_disabled_blocks_all(): void
    {
        [$tenant, $owner, $token] = $this->setupTenantAndAdmin();
        $file = $this->uploadFile($tenant, $owner, $token);

        // Disable downloads on the file
        $file->update(['download_enabled' => false]);

        // Even the owner cannot download
        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->get("/api/v1/files/{$file->id}/download");

        $response->assertStatus(403);
    }

    public function test_can_change_file_permission(): void
    {
        [$tenant, $owner, $token] = $this->setupTenantAndAdmin();
        $file = $this->uploadFile($tenant, $owner, $token);

        $recipient = $this->createUser(['email' => 'recipient@example.com']);
        $this->attachUserToTenant($recipient, $tenant, 'member');

        $grant = AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => SecureFile::class,
            'grantable_id' => $file->id,
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
            'permission' => 'view',
            'granted_by' => $owner->id,
            'granted_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->putJson("/api/v1/files/{$file->id}/access/{$grant->id}", [
                'permission' => 'download',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['permission' => 'download']);
        $this->assertDatabaseHas('access_grants', [
            'id' => $grant->id,
            'permission' => 'download',
        ]);
    }

    public function test_can_revoke_file_access(): void
    {
        [$tenant, $owner, $token] = $this->setupTenantAndAdmin();
        $file = $this->uploadFile($tenant, $owner, $token);

        $recipient = $this->createUser(['email' => 'recipient@example.com']);
        $this->attachUserToTenant($recipient, $tenant, 'member');

        $grant = AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => SecureFile::class,
            'grantable_id' => $file->id,
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
            'permission' => 'view',
            'granted_by' => $owner->id,
            'granted_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->deleteJson("/api/v1/files/{$file->id}/access/{$grant->id}");

        $response->assertStatus(204);
        $this->assertDatabaseHas('access_grants', [
            'id' => $grant->id,
            'revoked_at' => $grant->refresh()->revoked_at,
        ]);
    }

    public function test_can_view_file_access_list(): void
    {
        [$tenant, $owner, $token] = $this->setupTenantAndAdmin();
        $file = $this->uploadFile($tenant, $owner, $token);

        $recipient = $this->createUser(['email' => 'recipient@example.com']);
        $this->attachUserToTenant($recipient, $tenant, 'member');

        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => SecureFile::class,
            'grantable_id' => $file->id,
            'subject_type' => User::class,
            'subject_id' => $recipient->id,
            'permission' => 'view',
            'granted_by' => $owner->id,
            'granted_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson("/api/v1/files/{$file->id}/access");

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_expired_file_cannot_be_downloaded(): void
    {
        [$tenant, $owner, $token] = $this->setupTenantAndAdmin();
        $file = $this->uploadFile($tenant, $owner, $token);

        // Set expiration in the past
        $file->update(['expires_at' => now()->subDay()]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->get("/api/v1/files/{$file->id}/download");

        $response->assertStatus(410);
    }

    public function test_expire_command_deletes_files(): void
    {
        [$tenant, $owner, $token] = $this->setupTenantAndAdmin();
        $file = $this->uploadFile($tenant, $owner, $token);

        // Set expiration in the past
        $file->update(['expires_at' => now()->subDay()]);

        $this->artisan('files:expire')
            ->assertSuccessful()
            ->expectsOutputToContain('Expired');

        $this->assertSoftDeleted('secure_files', ['id' => $file->id]);
    }

    public function test_file_owner_notified_on_expiry(): void
    {
        Notification::fake();

        [$tenant, $owner, $token] = $this->setupTenantAndAdmin();
        $file = $this->uploadFile($tenant, $owner, $token);

        // Set expiration in the past
        $file->update(['expires_at' => now()->subDay()]);

        $this->artisan('files:expire');

        Notification::assertSentTo($owner, FileExpiredNotification::class);
    }

    public function test_non_shared_user_cannot_access_file(): void
    {
        [$tenant, $owner, $token] = $this->setupTenantAndAdmin();

        // Create file directly to avoid auth state leakage from upload
        $file = SecureFile::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'name' => 'doc.txt',
            'file_path' => "{$tenant->id}/".Str::uuid().'/doc.txt',
        ]);

        // Create a member with no grant
        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');
        $memberToken = $member->createToken('test')->plainTextToken;

        // Try to view metadata
        $response = $this->withHeaders($this->authHeaders($memberToken, $tenant->id))
            ->getJson("/api/v1/files/{$file->id}");

        $response->assertStatus(403);
    }
}
