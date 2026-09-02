<?php

declare(strict_types=1);

namespace App\Actions;

use App\Events\FileUploaded;
use App\Models\SecureFile;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadFileAction
{
    private const MAX_SIZE = 10485760; // 10 MB in bytes

    public function __construct(
        private readonly TenantManager $tenantManager,
    ) {}

    /**
     * Upload a file to private storage and create a SecureFile record.
     *
     * @param  array<string, mixed>  $options  (team_id, folder_id, description)
     */
    public function __invoke(User $uploadedBy, UploadedFile $file, array $options = []): SecureFile
    {
        // Validate file size
        if ($file->getSize() > self::MAX_SIZE) {
            abort(422, 'File exceeds the 10 MB maximum size.');
        }

        $tenantId = $this->tenantManager->currentTenantId();
        $uuid = Str::uuid()->toString();
        $filename = $file->getClientOriginalName();
        $directory = "{$tenantId}/{$uuid}";
        $path = $file->storeAs($directory, $filename, 'private');

        if ($path === false) {
            abort(500, 'Failed to store file.');
        }

        $checksum = hash_file('sha256', $file->getRealPath());

        $secureFile = SecureFile::create([
            'tenant_id' => $tenantId,
            'team_id' => $options['team_id'] ?? null,
            'user_id' => $uploadedBy->id,
            'folder_id' => $options['folder_id'] ?? null,
            'name' => $filename,
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'checksum' => $checksum,
            'description' => $options['description'] ?? null,
            'metadata' => null,
            'download_enabled' => true,
            'expires_at' => null,
            'archived_at' => null,
        ]);

        FileUploaded::dispatch($secureFile, $uploadedBy);

        return $secureFile;
    }
}
