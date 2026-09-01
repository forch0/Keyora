<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Team;
use App\Models\User;

class UpdateTeamMemberAction
{
    /**
     * Update a team member's role.
     */
    public function __invoke(Team $team, User $targetUser, string $role): void
    {
        $team->members()->updateExistingPivot($targetUser->id, ['role' => $role]);
    }
}
