<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vault\CreateFolderRequest;
use App\Http\Requests\Vault\UpdateFolderRequest;
use App\Http\Resources\V1\PersonalVaultFolderResource;
use App\Models\PersonalVaultFolder;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PersonalVaultFolderController extends Controller
{
    /**
     * List all folders for the authenticated user as a tree.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        $folders = PersonalVaultFolder::where('user_id', $user->id)
            ->with('children.children')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return PersonalVaultFolderResource::collection($folders);
    }

    /**
     * Create a new folder.
     */
    public function store(CreateFolderRequest $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $folder = PersonalVaultFolder::create([
            'user_id' => $user->id,
            'name' => $request->validated('name'),
            'parent_id' => $request->validated('parent_id'),
            'icon' => $request->validated('icon'),
            'color' => $request->validated('color'),
            'sort_order' => $request->validated('sort_order', 0),
        ]);

        return (new PersonalVaultFolderResource($folder))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a folder with its items.
     */
    public function show(Request $request, PersonalVaultFolder $folder): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if ($folder->user_id !== $user->id) {
            abort(404);
        }

        $folder->load(['items', 'children']);

        return (new PersonalVaultFolderResource($folder))->response();
    }

    /**
     * Update a folder.
     */
    public function update(UpdateFolderRequest $request, PersonalVaultFolder $folder): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if ($folder->user_id !== $user->id) {
            abort(404);
        }

        $folder->update($request->validated());

        return (new PersonalVaultFolderResource($folder->fresh()))->response();
    }

    /**
     * Delete a folder. Items are moved to root (folder_id set to null).
     */
    public function destroy(Request $request, PersonalVaultFolder $folder): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if ($folder->user_id !== $user->id) {
            abort(404);
        }

        // Move items to root before deleting
        $folder->items()->update(['folder_id' => null]);

        // Move child folders to root
        $folder->children()->update(['parent_id' => null]);

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
