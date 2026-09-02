<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Team;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Carbon;

class AssignTeamAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * Assign a user to a team.
     *
     * @param  string  $role  Team role: 'lead' or 'member'
     */
    public function __invoke(User $assignedBy, User $user, Team $team, string $role = 'member'): void
    {
        if ($user->teams()->where('teams.id', $team->id)->exists()) {
            return;
        }

        $user->teams()->attach($team->id, [
            'role' => $role,
            'joined_at' => Carbon::now(),
        ]);

        $this->activityLogger->log('member.team_assigned', $assignedBy, $team, [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'team_role' => $role,
        ]);
    }
}
