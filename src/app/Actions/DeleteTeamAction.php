<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Team;

class DeleteTeamAction
{
    /**
     * Delete a team. Vault items are moved to org-wide (team_id = null)
     * before the team is removed, preserving shared credentials.
     */
    public function __invoke(Team $team): void
    {
        $team->vaultItems()->update(['team_id' => null]);

        $team->delete();
    }
}
