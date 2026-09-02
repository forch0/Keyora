<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\GrantAccessAction;
use App\Actions\RevokeAccessAction;
use App\Actions\UpdateAccessAction;
use App\Enums\AccessDuration;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Access\GrantAccessRequest;
use App\Http\Requests\Access\UpdateAccessRequest;
use App\Http\Resources\V1\AccessGrantResource;
use App\Models\AccessGrant;
use App\Models\SecureFile;
use App\Models\User;
use App\Services\AccessResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class FileAccessController extends Controller
{
    public function __construct(
        private readonly AccessResolver $accessResolver,
        private readonly GrantAccessAction $grantAccess,
        private readonly UpdateAccessAction $updateAccess,
        private readonly RevokeAccessAction $revokeAccess,
    ) {}

    /**
     * List all active access grants for a file.
     */
    public function index(Request $request, SecureFile $file): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        if (! $this->accessResolver->can($user, Permission::Share, $file)) {
            if ($file->user_id !== $user->id) {
                $tenant = $file->tenant;
                if ($tenant === null || ! $user->isAdminOf($tenant)) {
                    abort(403);
                }
            }
        }

        $grants = $this->accessResolver->whoHasAccess($file);

        return AccessGrantResource::collection($grants);
    }

    /**
     * Grant access to a user, team, or the entire tenant.
     */
    public function store(GrantAccessRequest $request, SecureFile $file): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $permission = Permission::from($request->validated('permission'));

        $expiresAt = $this->resolveExpiresAt($request);

        $grant = ($this->grantAccess)(
            grantedBy: $user,
            resource: $file,
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
     * Update an access grant (change permission level).
     */
    public function update(UpdateAccessRequest $request, SecureFile $file, AccessGrant $grant): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $grant = ($this->updateAccess)($user, $grant, $request->validated());

        return (new AccessGrantResource($grant))->response();
    }

    /**
     * Revoke an access grant.
     */
    public function destroy(Request $request, SecureFile $file, AccessGrant $grant): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $reason = $request->input('reason');

        ($this->revokeAccess)($user, $grant, is_string($reason) ? $reason : null);

        return response()->json(null, 204);
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
