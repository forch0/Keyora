<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notes\CreateNoteFolderRequest;
use App\Http\Requests\Notes\UpdateNoteFolderRequest;
use App\Http\Resources\V1\NoteFolderResource;
use App\Models\NoteFolder;
use App\Models\SecureNote;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NoteFolderController extends Controller
{
    public function __construct(
        private readonly TenantManager $tenantManager,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);
        $tenantId = $this->tenantManager->currentTenantId();

        $folders = NoteFolder::query()
            ->where(function ($q) use ($user, $tenantId): void {
                // Personal folders
                $q->where('user_id', $user->id)
                    ->whereNull('tenant_id');

                // Tenant folders
                if ($tenantId !== null) {
                    $q->orWhere('tenant_id', $tenantId);
                }
            })
            ->whereNull('parent_id')
            ->with(['children', 'notes'])
            ->get();

        return NoteFolderResource::collection($folders);
    }

    public function store(CreateNoteFolderRequest $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $tenantId = $this->tenantManager->currentTenantId();

        $folder = NoteFolder::create([
            'tenant_id' => $request->validated('team_id') !== null ? $tenantId : $tenantId,
            'team_id' => $request->validated('team_id'),
            'user_id' => $user->id,
            'name' => $request->validated('name'),
            'parent_id' => $request->validated('parent_id'),
            'created_by' => $user->id,
        ]);

        return (new NoteFolderResource($folder))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateNoteFolderRequest $request, NoteFolder $folder): JsonResponse
    {
        $folder->update($request->validated());

        return (new NoteFolderResource($folder->refresh()))->response();
    }

    public function destroy(Request $request, NoteFolder $folder): JsonResponse
    {
        $this->authenticatedUser($request);

        // Move notes to root
        SecureNote::where('folder_id', $folder->id)->update(['folder_id' => null]);

        // Move child folders to root
        NoteFolder::where('parent_id', $folder->id)->update(['parent_id' => null]);

        $folder->delete();

        return response()->json(null, 204);
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
