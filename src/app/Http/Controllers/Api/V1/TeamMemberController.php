<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\AddTeamMemberAction;
use App\Actions\RemoveTeamMemberAction;
use App\Actions\UpdateTeamMemberAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\AddTeamMemberRequest;
use App\Http\Requests\Team\UpdateTeamMemberRequest;
use App\Http\Resources\V1\TeamMemberResource;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TeamMemberController extends Controller
{
    public function __construct(
        private readonly AddTeamMemberAction $addTeamMember,
        private readonly UpdateTeamMemberAction $updateTeamMember,
        private readonly RemoveTeamMemberAction $removeTeamMember,
    ) {}

    /**
     * List team members.
     */
    public function index(Request $request, Tenant $tenant, Team $team): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        if (! $user->isAdminOf($team->tenant) && ! $user->isTeamMember($team)) {
            abort(403);
        }

        $members = $team->members()->orderBy('name')->get();

        return TeamMemberResource::collection($members);
    }

    /**
     * Add a member to the team.
     */
    public function store(AddTeamMemberRequest $request, Tenant $tenant, Team $team): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if (! $user->isAdminOf($team->tenant) && ! $user->isTeamLead($team)) {
            abort(403);
        }

        $targetUser = User::query()->find((int) $request->validated('user_id'));

        if (! $targetUser instanceof User) {
            abort(404);
        }

        $result = ($this->addTeamMember)($team, $targetUser, $request->validated('role'));

        if (! $result['success']) {
            return response()->json(['message' => $result['message'] ?? 'Could not add member.'], 422);
        }

        $member = $team->members()->where('user_id', $targetUser->id)->first();

        return (new TeamMemberResource($member))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a member's role.
     */
    public function update(UpdateTeamMemberRequest $request, Tenant $tenant, Team $team, User $user): JsonResponse
    {
        $authUser = $this->authenticatedUser($request);

        if (! $authUser->isAdminOf($team->tenant) && ! $authUser->isTeamLead($team)) {
            abort(403);
        }

        if (! $team->members()->where('user_id', $user->id)->exists()) {
            abort(404);
        }

        ($this->updateTeamMember)($team, $user, $request->validated('role'));

        $member = $team->members()->where('user_id', $user->id)->first();

        return (new TeamMemberResource($member))->response();
    }

    /**
     * Remove a member from the team.
     */
    public function destroy(Request $request, Tenant $tenant, Team $team, User $user): JsonResponse
    {
        $authUser = $this->authenticatedUser($request);

        if (! $authUser->isAdminOf($team->tenant) && ! $authUser->isTeamLead($team)) {
            abort(403);
        }

        if (! $team->members()->where('user_id', $user->id)->exists()) {
            abort(404);
        }

        ($this->removeTeamMember)($team, $user);

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
