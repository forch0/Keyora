<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Team;
use App\Models\User;

class RemoveTeamMemberAction
{
    /**
     * Detach a user from a team.
     */
    public function __invoke(Team $team, User $targetUser): void
    {
        $team->members()->detach($targetUser->id);
    }
}
