<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\GrantAccessAction;
use App\Actions\RevokeAccessAction;
use App\Actions\UpdateAccessAction;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Access\GrantAccessRequest;
use App\Http\Requests\Access\UpdateAccessRequest;
use App\Http\Resources\V1\AccessGrantResource;
use App\Models\AccessGrant;
use App\Models\Team;
use App\Models\User;
use App\Models\VaultItem;
use App\Services\AccessResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class AccessGrantController extends Controller
{
    public function __construct(
        private readonly AccessResolver $accessResolver,
        private readonly GrantAccessAction $grantAccess,
        private readonly UpdateAccessAction $updateAccess,
        private readonly RevokeAccessAction $revokeAccess,
    ) {}

    /**
     * List all active access grants for a vault item.
     */
    public function index(Request $request, VaultItem $item): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        if (! $this->accessResolver->can($user, Permission::Share, $item)) {
            if ($item->user_id !== $user->id && ! $user->isAdminOf($item->tenant)) {
                abort(403);
            }
        }

        $grants = $this->accessResolver->whoHasAccess($item);

        return AccessGrantResource::collection($grants);
    }

    /**
     * Summary of who has access, grouped by subject type.
     */
    public function summary(Request $request, VaultItem $item): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if (! $this->accessResolver->can($user, Permission::Share, $item)) {
            if ($item->user_id !== $user->id && ! $user->isAdminOf($item->tenant)) {
                abort(403);
            }
        }

        $grants = $this->accessResolver->whoHasAccess($item);

        $grouped = $grants->groupBy('subject_type')->map(fn ($group) => [
            'count' => $group->count(),
            'grants' => AccessGrantResource::collection($group),
        ]);

        return response()->json([
            'total' => $grants->count(),
            'groups' => $grouped,
        ]);
    }

    /**
     * Grant access to a user, team, or the entire tenant.
     */
    public function store(GrantAccessRequest $request, VaultItem $item): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $permission = Permission::from($request->validated('permission'));

        $grant = ($this->grantAccess)(
            grantedBy: $user,
            resource: $item,
            subjectType: $request->validated('subject_type'),
            subjectId: (int) $request->validated('subject_id'),
            permission: $permission,
            expiresAt: $request->validated('expires_at') ? Carbon::parse($request->validated('expires_at')) : null,
            maxViews: $request->validated('max_views') ? (int) $request->validated('max_views') : null,
            startOnFirstView: (bool) $request->validated('start_on_first_view', false),
            startsAt: $request->validated('starts_at') ? Carbon::parse($request->validated('starts_at')) : null,
        );

        return (new AccessGrantResource($grant))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Bulk grant access to multiple teams.
     */
    public function bulkStore(Request $request, VaultItem $item): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        $validated = $request->validate([
            'team_ids' => ['required', 'array', 'min:1'],
            'team_ids.*' => ['required', 'integer', 'exists:'.Team::class.',id'],
            'permission' => ['required', 'string', 'in:view,download,edit,share,manage'],
        ]);

        $permission = Permission::from($validated['permission']);
        $grants = [];

        foreach ($validated['team_ids'] as $teamId) {
            $grants[] = ($this->grantAccess)(
                grantedBy: $user,
                resource: $item,
                subjectType: Team::class,
                subjectId: (int) $teamId,
                permission: $permission,
            );
        }

        return AccessGrantResource::collection(collect($grants));
    }

    /**
     * Update an access grant (change permission level).
     */
    public function update(UpdateAccessRequest $request, VaultItem $item, AccessGrant $grant): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $grant = ($this->updateAccess)($user, $grant, $request->validated());

        return (new AccessGrantResource($grant))->response();
    }

    /**
     * Revoke an access grant (soft delete — sets revoked_at).
     */
    public function destroy(Request $request, VaultItem $item, AccessGrant $grant): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $reason = $request->input('reason');

        ($this->revokeAccess)($user, $grant, is_string($reason) ? $reason : null);

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
