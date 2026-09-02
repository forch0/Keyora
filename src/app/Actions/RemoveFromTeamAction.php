<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AccessGrant;
use App\Models\Team;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Carbon;

class RemoveFromTeamAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * Remove a user from a team and revoke team-based access grants.
     */
    public function __invoke(User $removedBy, User $user, Team $team): void
    {
        $user->teams()->detach($team->id);

        // Revoke team-based access grants where the user was the subject
        // (direct user grants for team resources remain unless explicitly revoked)
        AccessGrant::withoutTenant()
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('grantable_type', 'App\\Models\\VaultItem')
            ->where('tenant_id', $team->tenant_id)
            ->whereNull('revoked_at')
            ->whereHas('grantable', function ($query) use ($team): void {
                $query->where('team_id', $team->id);
            })
            ->update([
                'revoked_at' => Carbon::now(),
                'revoked_by' => $removedBy->id,
                'revoke_reason' => 'removed_from_team',
            ]);

        $this->activityLogger->log('member.team_removed', $removedBy, $team, [
            'user_id' => $user->id,
            'user_name' => $user->name,
        ]);
    }
}
