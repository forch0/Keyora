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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PersonalVaultItemController extends Controller
{
    public function __construct(
        private readonly CreateVaultItemAction $createVaultItem,
        private readonly UpdateVaultItemAction $updateVaultItem,
        private readonly DeleteVaultItemAction $deleteVaultItem,
    ) {}

    /**
     * List the authenticated user's vault items.
     * Supports filtering by type, favorite, and archived status.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        $query = PersonalVaultItem::where('user_id', $user->id);

        if ($request->boolean('archived')) {
            // Show only archived items
            $query->whereNotNull('archived_at');
        } else {
            // Show only active (non-archived) items
            $query->whereNull('archived_at');
        }

        if ($type = $request->query('type')) {
            $query->ofType((string) $type);
        }

        if ($request->boolean('favorite')) {
            $query->favorite();
        }

        $items = $query->orderBy('favorite', 'desc')
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return PersonalVaultItemResource::collection($items);
    }

    /**
     * Create a new vault item.
     */
    public function store(CreateItemRequest $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $item = ($this->createVaultItem)($user, $request->validated());

        return (new PersonalVaultItemResource($item))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a single vault item with decrypted sensitive fields.
     */
    public function show(Request $request, PersonalVaultItem $item): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if ($item->user_id !== $user->id) {
            abort(404);
        }

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
     * Get the authenticated user or throw.
     */
    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Unauthenticated.');
        }

        return $user;
    }
}
