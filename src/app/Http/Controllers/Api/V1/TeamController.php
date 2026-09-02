<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateTeamAction;
use App\Actions\DeleteTeamAction;
use App\Actions\UpdateTeamAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\CreateTeamRequest;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Http\Resources\V1\TeamResource;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Teams')]
class TeamController extends Controller
{
    public function __construct(
        private readonly CreateTeamAction $createTeam,
        private readonly UpdateTeamAction $updateTeam,
        private readonly DeleteTeamAction $deleteTeam,
    ) {}

    /**
     * List teams for the current tenant.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        $teams = Team::query()
            ->withCount(['members', 'vaultItems'])
            ->orderBy('name')
            ->get();

        // Filter to teams the user can view
        $viewable = $teams->filter(fn (Team $team) => $user->isAdminOf($team->tenant) || $user->isTeamMember($team));

        return TeamResource::collection($viewable->values());
    }

    /**
     * Create a new team.
     */
    public function store(CreateTeamRequest $request, Tenant $tenant): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if (! $user->isAdminOf($tenant)) {
            abort(403);
        }

        $team = ($this->createTeam)($user, $request->validated());

        return (new TeamResource($team))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show team details.
     */
    public function show(Request $request, Tenant $tenant, Team $team): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if (! $user->isAdminOf($team->tenant) && ! $user->isTeamMember($team)) {
            abort(403);
        }

        $team->loadCount(['members', 'vaultItems']);

        return (new TeamResource($team))->response();
    }

    /**
     * Update team.
     */
    public function update(UpdateTeamRequest $request, Tenant $tenant, Team $team): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if (! $user->isAdminOf($team->tenant) && ! $user->isTeamLead($team)) {
            abort(403);
        }

        $team = ($this->updateTeam)($team, $request->validated());

        return (new TeamResource($team))->response();
    }

    /**
     * Delete team. Vault items moved to org-wide (team_id = null).
     */
    public function destroy(Request $request, Tenant $tenant, Team $team): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if (! $user->isAdminOf($team->tenant)) {
            abort(403);
        }

        ($this->deleteTeam)($team);

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
