<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateTenantAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CreateTenantRequest;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use App\Http\Resources\V1\TenantResource;
use App\Models\Tenant;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Tenant Management')]
class TenantController extends Controller
{
    public function __construct(private readonly CreateTenantAction $createTenant) {}

    /**
     * List the authenticated user's workspaces.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        $tenants = $user->tenants()
            ->whereNull('tenant_user.left_at')
            ->orderBy('tenant_user.joined_at', 'desc')
            ->get();

        return TenantResource::collection($tenants);
    }

    /**
     * Create a new workspace; the authenticated user becomes the owner.
     */
    public function store(CreateTenantRequest $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $tenant = ($this->createTenant)($user, $request->validated());

        return (new TenantResource($tenant->fresh(['users'])))
            ->additional(['role' => 'owner'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a workspace the user is a member of.
     */
    public function show(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authenticatedUser($request);

        return (new TenantResource($tenant))->response();
    }

    /**
     * Update a workspace (owner or admin only).
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant): JsonResponse
    {
        $tenant->update($request->validated());

        return (new TenantResource($tenant->fresh()))->response();
    }

    /**
     * Soft-delete a workspace (owner only).
     */
    public function destroy(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authenticatedUser($request);

        $tenant->delete();

        return response()->json(null, 204);
    }
}
