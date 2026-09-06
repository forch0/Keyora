<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Team;
use App\Models\User;

class AddTeamMemberAction
{
    /**
     * Attach a user to a team with the given role.
     * Validates that the user is a member of the team's tenant.
     *
     * @return array{success: bool, message?: string}
     */
    public function __invoke(Team $team, User $targetUser, string $role): array
    {
        if (! $targetUser->isMemberOf($team->tenant)) {
            return ['success' => false, 'message' => 'User is not a member of this tenant.'];
        }

        if ($team->members()->where('user_id', $targetUser->id)->exists()) {
            return ['success' => false, 'message' => 'User is already a member of this team.'];
        }

        $team->members()->attach($targetUser->id, [
            'role' => $role,
            'joined_at' => now(),
        ]);

        return ['success' => true];
    }
}
