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
use App\Models\SecureNote;
use App\Models\User;
use App\Services\AccessResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class NoteAccessController extends Controller
{
    public function __construct(
        private readonly AccessResolver $accessResolver,
        private readonly GrantAccessAction $grantAccess,
        private readonly UpdateAccessAction $updateAccess,
        private readonly RevokeAccessAction $revokeAccess,
    ) {}

    public function index(Request $request, SecureNote $note): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        if (! $this->accessResolver->can($user, Permission::Share, $note)) {
            if ($note->user_id !== $user->id) {
                $tenant = $note->tenant;
                if ($tenant === null || ! $user->isAdminOf($tenant)) {
                    abort(403);
                }
            }
        }

        $grants = $this->accessResolver->whoHasAccess($note);

        return AccessGrantResource::collection($grants);
    }

    public function store(GrantAccessRequest $request, SecureNote $note): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $permission = Permission::from($request->validated('permission'));

        $grant = ($this->grantAccess)(
            grantedBy: $user,
            resource: $note,
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

    public function update(UpdateAccessRequest $request, SecureNote $note, AccessGrant $grant): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $grant = ($this->updateAccess)($user, $grant, $request->validated());

        return (new AccessGrantResource($grant))->response();
    }

    public function destroy(Request $request, SecureNote $note, AccessGrant $grant): JsonResponse
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
