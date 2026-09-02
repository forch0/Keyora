<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AccessGrant;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Carbon;
use RuntimeException;

class AcceptInvitationAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * Accept an invitation by token, attaching the user to the tenant.
     *
     * @return Tenant The tenant the user has joined
     *
     * @throws RuntimeException If the invitation is invalid, expired, or already accepted
     */
    public function __invoke(User $user, string $token): Tenant
    {
        /** @var TenantInvitation|null $invitation */
        $invitation = TenantInvitation::where('token', $token)->first();

        if ($invitation === null) {
            throw new RuntimeException('Invalid invitation token.', 422);
        }

        if ($invitation->isAccepted()) {
            throw new RuntimeException('This invitation has already been accepted.', 422);
        }

        if ($invitation->isExpired()) {
            throw new RuntimeException('This invitation has expired.', 422);
        }

        $tenant = $invitation->tenant;

        if ($tenant === null) {
            throw new RuntimeException('Invitation tenant no longer exists.', 422);
        }

        // Attach the user to the tenant with the invited role.
        // If already a member (e.g. re-accepted), update the existing pivot.
        if ($user->tenants()->where('tenants.id', $tenant->id)->exists()) {
            $user->tenants()->updateExistingPivot($tenant->id, [
                'role' => $invitation->role,
                'status' => 'active',
                'left_at' => null,
                'suspended_at' => null,
                'joined_at' => now(),
                'onboarding_completed_at' => null,
            ]);
        } else {
            $user->tenants()->attach($tenant->id, [
                'role' => $invitation->role,
                'status' => 'active',
                'joined_at' => now(),
            ]);
        }

        // Auto-assign to teams specified in the invitation (Module 22)
        $teamIds = $invitation->team_ids ?? [];
        if (count($teamIds) > 0) {
            $validTeamIds = Team::where('tenant_id', $tenant->id)
                ->whereIn('id', $teamIds)
                ->pluck('id')
                ->toArray();

            foreach ($validTeamIds as $teamId) {
                if (! $user->teams()->where('teams.id', $teamId)->exists()) {
                    $user->teams()->attach($teamId, [
                        'role' => 'member',
                        'joined_at' => now(),
                    ]);
                }
            }
        }

        // Create initial access grants if specified (Module 22)
        $initialAccess = $invitation->initial_access ?? [];
        if (count($initialAccess) > 0) {
            $inviter = $invitation->inviter;

            foreach ($initialAccess as $grant) {
                $resourceClass = $grant['resource_type'];
                $resource = $resourceClass::find($grant['resource_id']);

                if ($resource === null) {
                    continue;
                }

                AccessGrant::create([
                    'tenant_id' => $tenant->id,
                    'grantable_type' => $resourceClass,
                    'grantable_id' => $grant['resource_id'],
                    'subject_type' => User::class,
                    'subject_id' => $user->id,
                    'permission' => $grant['permission'],
                    'granted_by' => $inviter !== null ? $inviter->id : $user->id,
                    'granted_at' => Carbon::now(),
                ]);
            }
        }

        $invitation->update(['accepted_at' => now()]);

        $this->activityLogger->log('user.joined', $user, $tenant);

        return $tenant;
    }
}
