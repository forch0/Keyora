<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    /**
     * View: must be member of tenant AND member of team (or admin/owner).
     */
    public function view(User $user, Team $team): bool
    {
        if (! $user->isMemberOf($team->tenant)) {
            return false;
        }

        // Admins/owners can view all teams
        if ($user->isAdminOf($team->tenant)) {
            return true;
        }

        return $user->isTeamMember($team);
    }

    /**
     * Create: admin or owner of tenant.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->isAdminOf($team->tenant);
    }

    /**
     * Update: admin, owner, or team lead.
     */
    public function update(User $user, Team $team): bool
    {
        if ($user->isAdminOf($team->tenant)) {
            return true;
        }

        return $user->isTeamLead($team);
    }

    /**
     * Delete: admin or owner.
     */
    public function delete(User $user, Team $team): bool
    {
        return $user->isAdminOf($team->tenant);
    }
}
