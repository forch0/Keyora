<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\EmptyTrashAction;
use App\Actions\ForceDeleteModelAction;
use App\Actions\ListTrashAction;
use App\Actions\RestoreModelAction;
use App\Actions\UploadFileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Files\ReplaceFileRequest;
use App\Http\Requests\Files\UpdateFileRequest;
use App\Http\Requests\Files\UploadFileRequest;
use App\Http\Resources\V1\SecureFileResource;
use App\Models\SecureFile;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\TenantManager;
use App\Services\ViewTracker;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Group('Secure Files')]
class SecureFileController extends Controller
{
    public function __construct(
        private readonly UploadFileAction $uploadFile,
        private readonly ViewTracker $viewTracker,
        private readonly ActivityLogger $activityLogger,
        private readonly ListTrashAction $listTrash,
        private readonly RestoreModelAction $restoreModel,
        private readonly ForceDeleteModelAction $forceDeleteModel,
        private readonly EmptyTrashAction $emptyTrash,
    ) {}

    /**
     * List files with filters (team, folder, archived, type).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        $query = SecureFile::query();

        // Filter by team
        if ($request->has('team_id')) {
            $query->where('team_id', $request->integer('team_id'));
        }

        // Filter by folder
        if ($request->has('folder_id')) {
            $query->where('folder_id', $request->integer('folder_id'));
        } else {
            $query->whereNull('folder_id');
        }

        // Filter by archived status
        if ($request->boolean('archived')) {
            $query->archived();
        } else {
            $query->notArchived();
        }

        // Filter by mime type
        if ($request->has('type')) {
            $query->where('mime_type', 'like', $request->string('type').'%');
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->string('search').'%');
        }

        $files = $query->orderByDesc('created_at')->paginate(15);

        return SecureFileResource::collection($files);
    }

    /**
     * Upload a single file.
     */
    public function store(UploadFileRequest $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $file = $this->uploadFile->__invoke(
            uploadedBy: $user,
            file: $request->file('file'),
            options: $request->only(['team_id', 'folder_id', 'description']),
        );

        return (new SecureFileResource($file))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Upload multiple files in one request.
     */
    public function bulkStore(Request $request): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'max:10240'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'folder_id' => ['nullable', 'integer', 'exists:file_folders,id'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $options = $request->only(['team_id', 'folder_id', 'description']);
        $files = [];

        foreach ($validated['files'] as $file) {
            $files[] = $this->uploadFile->__invoke(
                uploadedBy: $user,
                file: $file,
                options: $options,
            );
        }

        return SecureFileResource::collection(collect($files));
    }

    /**
     * Get file metadata (not the file content).
     */
    public function show(Request $request, SecureFile $file): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $this->authorize('view', $file);

        $this->viewTracker->recordView($user, $file);

        return (new SecureFileResource($file))->response();
    }

    /**
     * Download file (stream from private storage).
     *
     * @return StreamedResponse|JsonResponse
     */
    public function download(Request $request, SecureFile $file)
    {
        $user = $this->authenticatedUser($request);
        $this->authorize('download', $file);

        // Check expiration — 410 Gone if expired
        $expiresAt = $file->getAttribute('expires_at');
        if ($expiresAt !== null && $expiresAt->isPast()) {
            abort(410, 'This file has expired and is no longer available for download.');
        }

        if (! Storage::disk('private')->exists($file->file_path)) {
            abort(404, 'File not found in storage.');
        }

        // Log download (Module 20)
        $this->activityLogger->log('file.downloaded', $user, $file);

        return Storage::disk('private')->download($file->file_path, $file->name, [
            'Content-Type' => $file->mime_type,
            'Content-Disposition' => 'attachment; filename="'.$file->name.'"',
        ]);
    }

    /**
     * Update file metadata.
     */
    public function update(UpdateFileRequest $request, SecureFile $file): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $this->authorize('update', $file);

        $file->update($request->validated());

        return (new SecureFileResource($file->refresh()))->response();
    }

    /**
     * Replace file content (keep metadata record).
     */
    public function replace(ReplaceFileRequest $request, SecureFile $file): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $this->authorize('replace', $file);

        $uploaded = $request->file('file');

        // Delete old file from storage
        Storage::disk('private')->delete($file->file_path);

        // Store new file
        $uuid = Str::uuid()->toString();
        $filename = $uploaded->getClientOriginalName();
        $directory = "{$file->tenant_id}/{$uuid}";
        $path = $uploaded->storeAs($directory, $filename, 'private');

        if ($path === false) {
            abort(500, 'Failed to store replacement file.');
        }

        $checksum = hash_file('sha256', $uploaded->getRealPath());

        $file->update([
            'name' => $filename,
            'file_path' => $path,
            'mime_type' => $uploaded->getMimeType(),
            'size' => $uploaded->getSize(),
            'checksum' => $checksum,
        ]);

        return (new SecureFileResource($file->refresh()))->response();
    }

    /**
     * Delete file (soft delete in DB, hard delete from storage).
     */
    public function destroy(Request $request, SecureFile $file): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $this->authorize('delete', $file);

        // Hard delete from storage
        Storage::disk('private')->delete($file->file_path);

        // Soft delete in DB
        $file->delete();

        return response()->json(null, 204);
    }

    /**
     * Archive a file.
     */
    public function archive(Request $request, SecureFile $file): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $this->authorize('update', $file);

        $file->update(['archived_at' => now()]);

        return (new SecureFileResource($file->refresh()))->response();
    }

    /**
     * Restore an archived file.
     */
    public function restore(Request $request, SecureFile $file): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $this->authorize('update', $file);

        $file->update(['archived_at' => null]);

        return (new SecureFileResource($file->refresh()))->response();
    }

    /**
     * List trashed files.
     */
    public function trash(Request $request): AnonymousResourceCollection
    {
        $tenantId = app(TenantManager::class)->currentTenantId();
        $items = ($this->listTrash)(SecureFile::class, $this->authenticatedUser($request), $tenantId);

        return SecureFileResource::collection($items);
    }

    /**
     * Restore a trashed file.
     */
    public function restoreFromTrash(Request $request, int $file): JsonResponse
    {
        $model = ($this->restoreModel)(SecureFile::class, $file, $this->authenticatedUser($request), 'tenant_id');

        return (new SecureFileResource($model))->response();
    }

    /**
     * Permanently delete a trashed file (also removes physical file).
     */
    public function forceDelete(Request $request, int $file): JsonResponse
    {
        ($this->forceDeleteModel)(SecureFile::class, $file, $this->authenticatedUser($request), 'tenant_id');

        return response()->json(null, 204);
    }

    /**
     * Permanently delete all trashed files.
     */
    public function emptyTrash(Request $request): JsonResponse
    {
        $tenantId = app(TenantManager::class)->currentTenantId();
        $count = ($this->emptyTrash)(SecureFile::class, $this->authenticatedUser($request), $tenantId);

        return response()->json(['deleted' => $count]);
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Unauthenticated.');
        }

        return $user;
    }
}
