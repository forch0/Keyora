<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\AcceptInvitationAction;
use App\Actions\AssignTeamAction;
use App\Actions\CompleteOnboardingAction;
use App\Actions\InviteEmployeeAction;
use App\Actions\OffboardEmployeeAction;
use App\Actions\RemoveFromTeamAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\AcceptInvitationRequest;
use App\Http\Requests\Tenant\AssignTeamsRequest;
use App\Http\Requests\Tenant\ChangeRoleRequest;
use App\Http\Requests\Tenant\InviteMemberRequest;
use App\Http\Requests\Tenant\OffboardEmployeeRequest;
use App\Http\Requests\Tenant\UpdateMemberRequest;
use App\Http\Resources\V1\TenantInvitationResource;
use App\Http\Resources\V1\TenantMemberResource;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use RuntimeException;

#[Group('Employee Lifecycle')]
class TenantMemberController extends Controller
{
    public function __construct(
        private readonly InviteEmployeeAction $inviteEmployee,
        private readonly AcceptInvitationAction $acceptInvitation,
        private readonly AssignTeamAction $assignTeam,
        private readonly RemoveFromTeamAction $removeFromTeam,
        private readonly OffboardEmployeeAction $offboardEmployee,
        private readonly CompleteOnboardingAction $completeOnboarding,
    ) {}

    /**
     * List all members of the tenant.
     */
    public function index(Request $request, Tenant $tenant): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);
        $this->authorize('member.view', $tenant);

        $members = $tenant->users()
            ->wherePivotNull('left_at')
            ->orderBy('tenant_user.joined_at')
            ->get();

        return TenantMemberResource::collection($members);
    }

    /**
     * Invite a new member to the tenant via email.
     */
    public function invite(InviteMemberRequest $request, Tenant $tenant): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $this->authorize('member.invite', $tenant);

        $invitation = ($this->inviteEmployee)($tenant, $user, $request->validated());

        return (new TenantInvitationResource($invitation))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Accept an invitation using a token.
     *
     * Note: No membership check — the whole point is that the user is
     * NOT yet a member. The invitation token itself is the authorization.
     */
    public function accept(AcceptInvitationRequest $request, Tenant $tenant): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        try {
            $joinedTenant = ($this->acceptInvitation)($user, $request->validated('token'));
        } catch (RuntimeException $e) {
            return response()->json([
                'error' => [
                    'code' => 'INVITATION_INVALID',
                    'message' => $e->getMessage(),
                ],
            ], $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 422);
        }

        return response()->json([
            'data' => [
                'tenant_id' => $joinedTenant->id,
                'tenant_name' => $joinedTenant->name,
                'message' => 'You have joined the workspace successfully.',
            ],
        ], 200);
    }

    /**
     * View a specific member's profile.
     */
    public function show(Request $request, Tenant $tenant, User $user): JsonResponse
    {
        $this->authenticatedUser($request);
        $this->authorize('member.view', $tenant);

        $member = $tenant->users()
            ->wherePivot('user_id', $user->id)
            ->wherePivotNull('left_at')
            ->first();

        if ($member === null) {
            abort(404, 'Member not found in this workspace.');
        }

        return (new TenantMemberResource($member))->response();
    }

    /**
     * Change a member's role (member <-> admin). Cannot change owner's role.
     */
    public function update(UpdateMemberRequest $request, Tenant $tenant, User $user): JsonResponse
    {
        $this->authenticatedUser($request);
        $this->authorize('member.update', [$tenant, $user]);

        $tenant->users()->updateExistingPivot($user->id, [
            'role' => $request->validated('role'),
        ]);

        $member = $tenant->users()->wherePivot('user_id', $user->id)->first();

        return (new TenantMemberResource($member))->response();
    }

    /**
     * Suspend a member — set status to suspended, record suspended_at.
     */
    public function suspend(Request $request, Tenant $tenant, User $user): JsonResponse
    {
        $this->authenticatedUser($request);
        $this->authorize('member.suspend', [$tenant, $user]);

        $tenant->users()->updateExistingPivot($user->id, [
            'status' => 'suspended',
            'suspended_at' => now(),
        ]);

        return response()->json(null, 204);
    }

    /**
     * Restore a suspended member — set status back to active, clear suspended_at.
     */
    public function restore(Request $request, Tenant $tenant, User $user): JsonResponse
    {
        $this->authenticatedUser($request);
        $this->authorize('member.restore', [$tenant, $user]);

        $tenant->users()->updateExistingPivot($user->id, [
            'status' => 'active',
            'suspended_at' => null,
        ]);

        return response()->json(null, 204);
    }

    /**
     * Remove a member from the tenant — set left_at and status to left.
     */
    public function destroy(Request $request, Tenant $tenant, User $user): JsonResponse
    {
        $this->authenticatedUser($request);
        $this->authorize('member.remove', [$tenant, $user]);

        $tenant->users()->updateExistingPivot($user->id, [
            'status' => 'left',
            'left_at' => now(),
        ]);

        return response()->json(null, 204);
    }

    /**
     * List pending invitations for the tenant.
     */
    public function invitations(Request $request, Tenant $tenant): AnonymousResourceCollection
    {
        $this->authenticatedUser($request);
        $this->authorize('member.manageInvitations', $tenant);

        $invitations = $tenant->invitations()
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->get();

        return TenantInvitationResource::collection($invitations);
    }

    /**
     * Cancel (delete) a pending invitation.
     */
    public function cancelInvitation(Request $request, Tenant $tenant, TenantInvitation $invitation): JsonResponse
    {
        $this->authenticatedUser($request);
        $this->authorize('member.manageInvitations', $tenant);

        if ($invitation->tenant_id !== $tenant->id) {
            abort(404);
        }

        $invitation->delete();

        return response()->json(null, 204);
    }

    /**
     * Assign user to additional teams (Module 22).
     */
    public function assignTeams(AssignTeamsRequest $request, Tenant $tenant, User $user): JsonResponse
    {
        $actor = $this->authenticatedUser($request);
        $this->authorize('member.update', [$tenant, $user]);

        $teamIds = $request->validated('team_ids');
        $teams = Team::where('tenant_id', $tenant->id)->whereIn('id', $teamIds)->get();

        foreach ($teams as $team) {
            ($this->assignTeam)($actor, $user, $team);
        }

        return response()->json(null, 204);
    }

    /**
     * Remove user from a team (Module 22).
     */
    public function removeFromTeam(Request $request, Tenant $tenant, User $user, Team $team): JsonResponse
    {
        $actor = $this->authenticatedUser($request);
        $this->authorize('member.update', [$tenant, $user]);

        if ($team->tenant_id !== $tenant->id) {
            abort(404);
        }

        ($this->removeFromTeam)($actor, $user, $team);

        return response()->json(null, 204);
    }

    /**
     * Change user's tenant role (Module 22).
     */
    public function changeRole(ChangeRoleRequest $request, Tenant $tenant, User $user): JsonResponse
    {
        $this->authenticatedUser($request);
        $this->authorize('member.update', [$tenant, $user]);

        $tenant->users()->updateExistingPivot($user->id, [
            'role' => $request->validated('role'),
        ]);

        $member = $tenant->users()->wherePivot('user_id', $user->id)->first();

        return (new TenantMemberResource($member))->response();
    }

    /**
     * Offboard an employee (Module 22).
     */
    public function offboard(OffboardEmployeeRequest $request, Tenant $tenant, User $user): JsonResponse
    {
        $actor = $this->authenticatedUser($request);
        $this->authorize('member.remove', [$tenant, $user]);

        ($this->offboardEmployee)(
            offboardedBy: $actor,
            targetUser: $user,
            tenant: $tenant,
            reason: $request->validated('reason'),
        );

        return response()->json(null, 204);
    }

    /**
     * Complete onboarding for the authenticated user (Module 22).
     */
    public function completeOnboarding(Request $request, Tenant $tenant): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        ($this->completeOnboarding)($user, $tenant);

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
