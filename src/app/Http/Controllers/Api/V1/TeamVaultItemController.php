<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateTeamVaultItemAction;
use App\Actions\DeleteTeamVaultItemAction;
use App\Actions\UpdateTeamVaultItemAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\CreateVaultItemRequest;
use App\Http\Requests\Team\UpdateVaultItemRequest;
use App\Http\Resources\V1\VaultItemResource;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\VaultItem;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Team Vault Items')]
class TeamVaultItemController extends Controller
{
    public function __construct(
        private readonly CreateTeamVaultItemAction $createItem,
        private readonly UpdateTeamVaultItemAction $updateItem,
        private readonly DeleteTeamVaultItemAction $deleteItem,
    ) {}

    /**
     * List team vault items.
     */
    public function index(Request $request, Tenant $tenant, Team $team): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        if (! $user->isAdminOf($team->tenant) && ! $user->isTeamMember($team)) {
            abort(403);
        }

        $items = VaultItem::where('team_id', $team->id)
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return VaultItemResource::collection($items);
    }

    /**
     * Create a team vault item.
     */
    public function store(CreateVaultItemRequest $request, Tenant $tenant, Team $team): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if (! $user->isAdminOf($team->tenant) && ! $user->isTeamMember($team)) {
            abort(403);
        }

        $item = ($this->createItem)($user, $team, $request->validated());

        return (new VaultItemResource($item))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a team vault item.
     */
    public function show(Request $request, Tenant $tenant, Team $team, VaultItem $item): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if ($item->team_id !== $team->id) {
            abort(404);
        }

        if (! $user->isAdminOf($team->tenant) && ! $user->isTeamMember($team)) {
            abort(403);
        }

        return (new VaultItemResource($item))->response();
    }

    /**
     * Update a team vault item.
     */
    public function update(UpdateVaultItemRequest $request, Tenant $tenant, Team $team, VaultItem $item): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if ($item->team_id !== $team->id) {
            abort(404);
        }

        if (! $user->isAdminOf($team->tenant) && $item->user_id !== $user->id && ! $user->isTeamLead($team)) {
            abort(403);
        }

        $item = ($this->updateItem)($item, $request->validated());

        return (new VaultItemResource($item))->response();
    }

    /**
     * Delete a team vault item.
     */
    public function destroy(Request $request, Tenant $tenant, Team $team, VaultItem $item): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if ($item->team_id !== $team->id) {
            abort(404);
        }

        if (! $user->isAdminOf($team->tenant) && $item->user_id !== $user->id) {
            abort(403);
        }

        ($this->deleteItem)($item);

        return response()->json(null, 204);
    }
}
