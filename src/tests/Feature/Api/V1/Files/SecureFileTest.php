<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Files;

use App\Models\SecureFile;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Helpers\AuthHelper;
use Tests\Helpers\TenantHelper;
use Tests\TestCase;

class SecureFileTest extends TestCase
{
    use AuthHelper, RefreshDatabase, TenantHelper;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    private function setupTenantAndUser(): array
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

    public function test_user_can_upload_file(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/files', [
                'file' => $file,
                'description' => 'Test document',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'mime_type', 'size', 'size_bytes', 'checksum', 'description', 'download_url'],
            ]);

        $this->assertDatabaseHas('secure_files', [
            'name' => 'document.pdf',
            'user_id' => $user->id,
        ]);
    }

    public function test_file_over_10mb_rejected(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        // 11 MB file
        $file = UploadedFile::fake()->create('large.pdf', 11000, 'application/pdf');

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/files', [
                'file' => $file,
            ]);

        $response->assertStatus(422);
    }

    public function test_file_stored_in_private_storage(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $file = UploadedFile::fake()->create('secret.txt', 100, 'text/plain');

        $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/files', ['file' => $file]);

        // File should be in private disk, not public
        Storage::disk('private')->assertExists($this->getUploadedPath($tenant->id));
        Storage::disk('public')->assertMissing($this->getUploadedPath($tenant->id));
    }

    private function getUploadedPath(int $tenantId): string
    {
        $files = Storage::disk('private')->allFiles("{$tenantId}");
        $this->assertNotEmpty($files, 'No files found in private storage for tenant.');

        return $files[0];
    }

    public function test_file_path_includes_tenant_and_uuid(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $file = UploadedFile::fake()->create('test.txt', 100, 'text/plain');

        $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/files', ['file' => $file]);

        $secureFile = SecureFile::withoutTenant()->where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($secureFile);

        // Path should be {tenant_id}/{uuid}/{filename}
        $this->assertMatchesRegularExpression(
            '#^'.$tenant->id.'/[a-f0-9\-]{36}/test\.txt$#',
            $secureFile->file_path,
        );
    }

    public function test_checksum_calculated(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $file = UploadedFile::fake()->create('test.txt', 100, 'text/plain');

        $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/files', ['file' => $file]);

        $secureFile = SecureFile::withoutTenant()->where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($secureFile);
        // SHA-256 is 64 hex chars
        $this->assertSame(64, strlen($secureFile->checksum));
    }

    public function test_user_can_upload_multiple_files(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $file1 = UploadedFile::fake()->create('file1.txt', 100, 'text/plain');
        $file2 = UploadedFile::fake()->create('file2.txt', 100, 'text/plain');

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/files/bulk', [
                'files' => [$file1, $file2],
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('secure_files', ['name' => 'file1.txt']);
        $this->assertDatabaseHas('secure_files', ['name' => 'file2.txt']);
    }

    public function test_user_can_list_files(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        SecureFile::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson('/api/v1/files');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_user_can_download_file(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $file = UploadedFile::fake()->createWithContent('download.txt', 'Hello World', 'text/plain');

        $uploadResponse = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/files', ['file' => $file]);

        $fileId = $uploadResponse->json('data.id');

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->get("/api/v1/files/{$fileId}/download");

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename="download.txt"');
    }

    public function test_download_checks_permissions(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        // Create the file directly (not via HTTP) to avoid auth state leakage
        $this->setupTenantContext($tenant);
        $secureFile = SecureFile::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'name' => 'protected.txt',
            'download_enabled' => true,
        ]);

        // Create a regular tenant member (not admin, not uploader, no team)
        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');
        $memberToken = $member->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$memberToken,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->get("/api/v1/files/{$secureFile->id}/download");

        $response->assertStatus(403);
    }

    public function test_user_can_update_file_metadata(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $file = UploadedFile::fake()->create('update.txt', 100, 'text/plain');

        $uploadResponse = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/files', ['file' => $file]);

        $fileId = $uploadResponse->json('data.id');

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->putJson("/api/v1/files/{$fileId}", [
                'name' => 'renamed.txt',
                'description' => 'Updated description',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'renamed.txt', 'description' => 'Updated description']);
    }

    public function test_user_can_replace_file(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $originalFile = UploadedFile::fake()->createWithContent('original.txt', 'Original content', 'text/plain');

        $uploadResponse = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/files', ['file' => $originalFile]);

        $fileId = $uploadResponse->json('data.id');
        $originalChecksum = $uploadResponse->json('data.checksum');

        $newFile = UploadedFile::fake()->createWithContent('replacement.txt', 'New content', 'text/plain');

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/files/{$fileId}/replace", ['file' => $newFile]);

        $response->assertStatus(200);
        $this->assertNotEquals($originalChecksum, $response->json('data.checksum'));
    }

    public function test_user_can_archive_file(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $file = UploadedFile::fake()->create('archive.txt', 100, 'text/plain');

        $uploadResponse = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/files', ['file' => $file]);

        $fileId = $uploadResponse->json('data.id');

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/files/{$fileId}/archive");

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data.archived_at'));
    }

    public function test_user_can_restore_file(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $file = UploadedFile::fake()->create('restore.txt', 100, 'text/plain');

        $uploadResponse = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/files', ['file' => $file]);

        $fileId = $uploadResponse->json('data.id');

        // Archive first
        $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/files/{$fileId}/archive");

        // Restore
        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/files/{$fileId}/restore");

        $response->assertStatus(200);
        $this->assertNull($response->json('data.archived_at'));
    }

    public function test_user_can_delete_file(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $file = UploadedFile::fake()->create('delete.txt', 100, 'text/plain');

        $uploadResponse = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/files', ['file' => $file]);

        $fileId = $uploadResponse->json('data.id');

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->deleteJson("/api/v1/files/{$fileId}");

        $response->assertStatus(204);
        // Soft deleted — still in DB with deleted_at
        $this->assertSoftDeleted('secure_files', ['id' => $fileId]);
    }

    public function test_user_can_create_file_folder(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/files/folders', [
                'name' => 'Documents',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Documents']);
        $this->assertDatabaseHas('file_folders', ['name' => 'Documents', 'tenant_id' => $tenant->id]);
    }

    public function test_user_can_organize_files_into_folders(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        // Create folder
        $folderResponse = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/files/folders', ['name' => 'Images']);
        $folderId = $folderResponse->json('data.id');

        // Upload file to folder
        $file = UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg');

        $uploadResponse = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/files', [
                'file' => $file,
                'folder_id' => $folderId,
            ]);

        $uploadResponse->assertStatus(201);
        $this->assertDatabaseHas('secure_files', [
            'name' => 'photo.jpg',
            'folder_id' => $folderId,
        ]);
    }

    public function test_deleting_folder_moves_files_to_root(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        // Create folder
        $folderResponse = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/files/folders', ['name' => 'Temp']);
        $folderId = $folderResponse->json('data.id');

        // Upload file to folder
        $file = UploadedFile::fake()->create('doc.txt', 100, 'text/plain');
        $uploadResponse = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/files', [
                'file' => $file,
                'folder_id' => $folderId,
            ]);
        $fileId = $uploadResponse->json('data.id');

        // Delete folder
        $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->deleteJson("/api/v1/files/folders/{$folderId}")
            ->assertStatus(204);

        // File should still exist but with folder_id = null
        $this->assertDatabaseHas('secure_files', [
            'id' => $fileId,
            'folder_id' => null,
        ]);
    }

    public function test_files_are_tenant_scoped(): void
    {
        [$tenantA, $userA, $tokenA] = $this->setupTenantAndUser();

        // Create a SecureFile record directly (not via HTTP request) in tenant A
        SecureFile::factory()->create([
            'tenant_id' => $tenantA->id,
            'user_id' => $userA->id,
            'name' => 'secret.txt',
        ]);

        // Create tenant B
        $tenantB = $this->createTenant(['name' => 'B', 'slug' => 'b']);
        $userB = $this->createUser(['email' => 'b@example.com']);
        $this->attachUserToTenant($userB, $tenantB, 'admin');
        $tokenB = $userB->createToken('test')->plainTextToken;

        // Tenant B should not see tenant A's files
        $response = $this->withHeaders($this->authHeaders($tokenB, $tenantB->id))
            ->getJson('/api/v1/files');

        $response->assertStatus(200);
        $this->assertEmpty($response->json('data'));
    }
}
