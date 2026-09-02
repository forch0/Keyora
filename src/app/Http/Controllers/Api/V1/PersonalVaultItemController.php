<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateVaultItemAction;
use App\Actions\DeleteVaultItemAction;
use App\Actions\UpdateVaultItemAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vault\CreateItemRequest;
use App\Http\Requests\Vault\UpdateItemRequest;
use App\Http\Resources\V1\PersonalVaultItemResource;
use App\Models\PersonalVaultItem;
use App\Models\User;
use App\Models\VaultItem;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PersonalVaultItemController extends Controller
{
    public function __construct(
        private readonly CreateVaultItemAction $createVaultItem,
        private readonly UpdateVaultItemAction $updateVaultItem,
        private readonly DeleteVaultItemAction $deleteVaultItem,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * List the authenticated user's vault items.
     * Supports filtering by type, favorite, archived, folder, and tag.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        $query = PersonalVaultItem::where('user_id', $user->id);

        if ($request->boolean('archived')) {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }

        if ($type = $request->query('type')) {
            $query->ofType((string) $type);
        }

        if ($request->boolean('favorite')) {
            $query->favorite();
        }

        if ($folderId = $request->query('folder_id')) {
            $query->where('folder_id', (int) $folderId);
        }

        if ($tagId = $request->query('tag_id')) {
            $query->whereHas('tags', fn ($q) => $q->where('personal_vault_tags.id', (int) $tagId));
        }

        if ($tagName = $request->query('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('personal_vault_tags.name', (string) $tagName));
        }

        if ($request->has('shared')) {
            $currentUser = $request->user();
            if ($currentUser instanceof User) {
                if ($request->boolean('shared')) {
                    $userId = $currentUser->id;
                    $query->whereRaw('id IN (SELECT grantable_id FROM access_grants WHERE grantable_type = ? AND subject_type = ? AND subject_id = ? AND revoked_at IS NULL)', [
                        VaultItem::class,
                        User::class,
                        $userId,
                    ]);
                } else {
                    $query->where('user_id', $currentUser->id);
                }
            }
        }

        // Sorting (Module 19)
        $sort = $request->query('sort', '-created_at');
        $direction = str_starts_with((string) $sort, '-') ? 'desc' : 'asc';
        $sortColumn = ltrim((string) $sort, '-');

        $validSorts = ['name', 'created_at', 'updated_at'];
        if (in_array($sortColumn, $validSorts, true)) {
            $query->orderBy($sortColumn, $direction);
        } else {
            $query->orderBy('favorite', 'desc')->orderBy('updated_at', 'desc');
        }

        $items = $query->paginate(20);

        return PersonalVaultItemResource::collection($items);
    }

    /**
     * Create a new vault item.
     */
    public function store(CreateItemRequest $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $item = ($this->createVaultItem)($user, $request->validated());

        // Attach tags if provided
        if ($tagIds = $request->validated('tag_ids')) {
            $item->tags()->attach($tagIds);
        }

        return (new PersonalVaultItemResource($item))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a single vault item with decrypted sensitive fields.
     * Updates last_accessed_at timestamp.
     */
    public function show(Request $request, PersonalVaultItem $item): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if ($item->user_id !== $user->id) {
            abort(404);
        }

        $item->update(['last_accessed_at' => now()]);
        $item->refresh();

        $this->activityLogger->log('vault_item.viewed', $user, $item);

        return (new PersonalVaultItemResource($item))->response();
    }

    /**
     * Update a vault item.
     */
    public function update(UpdateItemRequest $request, PersonalVaultItem $item): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if ($item->user_id !== $user->id) {
            abort(404);
        }

        $item = ($this->updateVaultItem)($item, $request->validated());

        // Sync tags if provided
        if ($request->has('tag_ids')) {
            $item->tags()->sync($request->validated('tag_ids', []));
        }

        return (new PersonalVaultItemResource($item))->response();
    }

    /**
     * Delete a vault item.
     */
    public function destroy(Request $request, PersonalVaultItem $item): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if ($item->user_id !== $user->id) {
            abort(404);
        }

        ($this->deleteVaultItem)($item);

        return response()->json(null, 204);
    }

    /**
     * Toggle the favorite status of an item.
     */
    public function toggleFavorite(Request $request, PersonalVaultItem $item): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if ($item->user_id !== $user->id) {
            abort(404);
        }

        $item->update(['favorite' => ! $item->favorite]);
        $item->refresh();

        return (new PersonalVaultItemResource($item))->response();
    }

    /**
     * Archive an item (set archived_at).
     */
    public function archive(Request $request, PersonalVaultItem $item): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if ($item->user_id !== $user->id) {
            abort(404);
        }

        $item->update(['archived_at' => now()]);

        return response()->json(null, 204);
    }

    /**
     * Restore an archived item (clear archived_at).
     */
    public function restore(Request $request, PersonalVaultItem $item): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if ($item->user_id !== $user->id) {
            abort(404);
        }

        $item->update(['archived_at' => null]);

        return response()->json(null, 204);
    }

    /**
     * List recently accessed items (by last_accessed_at, limit 20).
     */
    public function recent(Request $request): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        $items = PersonalVaultItem::where('user_id', $user->id)
            ->whereNotNull('last_accessed_at')
            ->whereNull('archived_at')
            ->orderBy('last_accessed_at', 'desc')
            ->limit(20)
            ->get();

        return PersonalVaultItemResource::collection($items);
    }

    /**
     * List favorite items.
     */
    public function favorites(Request $request): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        $items = PersonalVaultItem::where('user_id', $user->id)
            ->where('favorite', true)
            ->whereNull('archived_at')
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return PersonalVaultItemResource::collection($items);
    }

    /**
     * List archived items.
     */
    public function archived(Request $request): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        $items = PersonalVaultItem::where('user_id', $user->id)
            ->whereNotNull('archived_at')
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return PersonalVaultItemResource::collection($items);
    }

    /**
     * Search vault items by name and URL (plaintext columns only).
     * Cannot search encrypted fields.
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        $query = $request->query('q', '');

        if ($query === '') {
            return PersonalVaultItemResource::collection(collect());
        }

        $items = PersonalVaultItem::where('user_id', $user->id)
            ->whereNull('archived_at')
            ->where(function ($q) use ($query): void {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('url', 'like', "%{$query}%");
            });

        if ($type = $request->query('type')) {
            $items->ofType((string) $type);
        }

        $results = $items->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get();

        return PersonalVaultItemResource::collection($results);
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
