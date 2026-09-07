<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Files\CreateFileFolderRequest;
use App\Http\Requests\Files\UpdateFileFolderRequest;
use App\Http\Resources\V1\FileFolderResource;
use App\Models\FileFolder;
use App\Models\SecureFile;
use App\Services\TenantManager;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('File Folders')]
class FileFolderController extends Controller
{
    public function __construct(
        private readonly TenantManager $tenantManager,
    ) {}

    /**
     * List file folders as a tree.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authenticatedUser($request);

        $tenantId = $this->tenantManager->currentTenantId();

        $folders = FileFolder::withoutTenant()
            ->where('tenant_id', $tenantId)
            ->whereNull('parent_id')
            ->with([
                'children' => fn ($q) => $q->withoutTenant()->where('tenant_id', $tenantId),
                'files' => fn ($q) => $q->withoutTenant()->where('tenant_id', $tenantId),
            ])
            ->get();

        return FileFolderResource::collection($folders);
    }

    /**
     * Create a folder.
     */
    public function store(CreateFileFolderRequest $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $tenantId = $this->tenantManager->currentTenantId();

        $folder = FileFolder::create([
            'tenant_id' => $tenantId,
            'team_id' => $request->validated('team_id'),
            'name' => $request->validated('name'),
            'parent_id' => $request->validated('parent_id'),
            'created_by' => $user->id,
        ]);

        return (new FileFolderResource($folder))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a folder.
     */
    public function update(UpdateFileFolderRequest $request, FileFolder $folder): JsonResponse
    {
        $folder->update($request->validated());

        return (new FileFolderResource($folder->refresh()))->response();
    }

    /**
     * Delete a folder — files are moved to root (folder_id = null).
     */
    public function destroy(Request $request, FileFolder $folder): JsonResponse
    {
        $this->authenticatedUser($request);

        // Move files to root
        SecureFile::withoutTenant()
            ->where('tenant_id', $folder->tenant_id)
            ->where('folder_id', $folder->id)
            ->update(['folder_id' => null]);

        // Move child folders to root
        FileFolder::withoutTenant()
            ->where('tenant_id', $folder->tenant_id)
            ->where('parent_id', $folder->id)
            ->update(['parent_id' => null]);

        $folder->delete();

        return response()->json(null, 204);
    }
}
