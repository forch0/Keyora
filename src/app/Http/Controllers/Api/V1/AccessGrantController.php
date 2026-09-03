<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\GrantAccessAction;
use App\Actions\RevokeAccessAction;
use App\Actions\UpdateAccessAction;
use App\Enums\AccessDuration;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Access\BulkGrantAccessRequest;
use App\Http\Requests\Access\GrantAccessRequest;
use App\Http\Requests\Access\UpdateAccessRequest;
use App\Http\Resources\V1\AccessGrantResource;
use App\Models\AccessGrant;
use App\Models\Team;
use App\Models\User;
use App\Models\VaultItem;
use App\Services\AccessResolver;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

#[Group('Access Grants')]
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
        $this->authenticatedUser($request);

        $this->authorize('viewAccessGrants', $item);

        $grants = $this->accessResolver->whoHasAccess($item);

        return AccessGrantResource::collection($grants);
    }

    /**
     * Summary of who has access, grouped by subject type.
     */
    public function summary(Request $request, VaultItem $item): JsonResponse
    {
        $this->authenticatedUser($request);

        $this->authorize('viewAccessGrants', $item);

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

        $expiresAt = $this->resolveExpiresAt($request);

        $grant = ($this->grantAccess)(
            grantedBy: $user,
            resource: $item,
            subjectType: $request->validated('subject_type'),
            subjectId: (int) $request->validated('subject_id'),
            permission: $permission,
            expiresAt: $expiresAt,
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
    public function bulkStore(BulkGrantAccessRequest $request, VaultItem $item): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        $permission = Permission::from($request->validated('permission'));
        $grants = [];

        foreach ($request->validated('team_ids') as $teamId) {
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

    /**
     * Get expiration countdown for the current user's grant on a resource.
     */
    public function countdown(Request $request, VaultItem $item): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $grant = $this->accessResolver->userGrantFor($user, $item);

        if ($grant === null) {
            abort(404, 'No active access grant found for this resource.');
        }

        $expiresAt = $grant->getAttribute('expires_at');
        $secondsRemaining = null;
        if ($expiresAt !== null) {
            $secondsRemaining = max(0, now()->diffInSeconds($expiresAt));
        }

        $viewsRemaining = null;
        if ($grant->max_views !== null) {
            $viewsRemaining = max(0, $grant->max_views - $grant->views_count);
        }

        return response()->json([
            'data' => [
                'grant_id' => $grant->id,
                'expires_at' => $expiresAt?->toIso8601String(),
                'seconds_remaining' => $secondsRemaining,
                'views_remaining' => $viewsRemaining,
                'max_views' => $grant->max_views,
                'views_count' => $grant->views_count,
                'starts_at' => $grant->getAttribute('starts_at')?->toIso8601String(),
                'start_on_first_view' => $grant->start_on_first_view,
                'first_viewed_at' => $grant->getAttribute('first_viewed_at')?->toIso8601String(),
            ],
        ]);
    }

    private function resolveExpiresAt(GrantAccessRequest $request): ?Carbon
    {
        if ($request->validated('duration')) {
            return AccessDuration::from($request->validated('duration'))->toCarbon();
        }

        if ($request->validated('expires_at')) {
            return Carbon::parse($request->validated('expires_at'));
        }

        return null;
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
