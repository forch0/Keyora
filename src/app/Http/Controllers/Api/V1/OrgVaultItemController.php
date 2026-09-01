<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateOrgVaultItemAction;
use App\Actions\DeleteTeamVaultItemAction;
use App\Actions\UpdateTeamVaultItemAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\CreateVaultItemRequest;
use App\Http\Requests\Team\UpdateVaultItemRequest;
use App\Http\Resources\V1\VaultItemResource;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VaultItem;
use App\Services\TenantManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrgVaultItemController extends Controller
{
    public function __construct(
        private readonly TenantManager $tenantManager,
        private readonly CreateOrgVaultItemAction $createItem,
        private readonly UpdateTeamVaultItemAction $updateItem,
        private readonly DeleteTeamVaultItemAction $deleteItem,
    ) {}

    /**
     * List org-wide vault items (team_id = null).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);
        $tenant = $this->currentTenant();

        $this->ensureTenantMember($user, $tenant);

        $items = VaultItem::orgWide()
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return VaultItemResource::collection($items);
    }

    /**
     * Create an org-wide vault item.
     */
    public function store(CreateVaultItemRequest $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $tenant = $this->currentTenant();

        $this->ensureTenantMember($user, $tenant);

        $item = ($this->createItem)($user, $request->validated());

        return (new VaultItemResource($item))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show an org-wide vault item.
     */
    public function show(Request $request, VaultItem $item): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $tenant = $this->currentTenant();

        $this->ensureTenantMember($user, $tenant);

        if ($item->team_id !== null) {
            abort(404);
        }

        return (new VaultItemResource($item))->response();
    }

    /**
     * Update an org-wide vault item.
     */
    public function update(UpdateVaultItemRequest $request, VaultItem $item): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $tenant = $this->currentTenant();

        $this->ensureTenantMember($user, $tenant);

        if ($item->team_id !== null) {
            abort(404);
        }

        if (! $user->isAdminOf($tenant) && $item->user_id !== $user->id) {
            abort(403);
        }

        $item = ($this->updateItem)($item, $request->validated());

        return (new VaultItemResource($item))->response();
    }

    /**
     * Delete an org-wide vault item.
     */
    public function destroy(Request $request, VaultItem $item): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $tenant = $this->currentTenant();

        $this->ensureTenantMember($user, $tenant);

        if ($item->team_id !== null) {
            abort(404);
        }

        if (! $user->isAdminOf($tenant) && $item->user_id !== $user->id) {
            abort(403);
        }

        ($this->deleteItem)($item);

        return response()->json(null, 204);
    }

    private function currentTenant(): Tenant
    {
        if (! $this->tenantManager->hasCurrentTenant()) {
            abort(400, 'No tenant context. Provide X-Tenant-ID header.');
        }

        $tenant = Tenant::find($this->tenantManager->currentTenantId());

        if ($tenant === null) {
            abort(404, 'Tenant not found.');
        }

        return $tenant;
    }

    private function ensureTenantMember(User $user, Tenant $tenant): void
    {
        if (! $user->isMemberOf($tenant)) {
            abort(403);
        }
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
