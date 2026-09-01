<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\VaultItem;

class VaultItemPolicy
{
    /**
     * View: must be member of team (for team items) or any tenant member (for org-wide).
     */
    public function view(User $user, VaultItem $item): bool
    {
        if (! $user->isMemberOf($item->tenant)) {
            return false;
        }

        // Org-wide items: accessible to all tenant members
        if ($item->team_id === null) {
            return true;
        }

        // Team items: must be team member or admin
        if ($user->isAdminOf($item->tenant)) {
            return true;
        }

        $team = $item->team;

        return $team !== null && $user->isTeamMember($team);
    }

    /**
     * Create: must be member of team (for team items) or any tenant member (for org-wide).
     */
    public function create(User $user, VaultItem $item): bool
    {
        if (! $user->isMemberOf($item->tenant)) {
            return false;
        }

        if ($item->team_id === null) {
            return true;
        }

        $team = $item->team;

        return $team !== null && $user->isTeamMember($team);
    }

    /**
     * Update: creator, team lead, or admin.
     */
    public function update(User $user, VaultItem $item): bool
    {
        if ($user->isAdminOf($item->tenant)) {
            return true;
        }

        if ($item->user_id === $user->id) {
            return true;
        }

        $team = $item->team;

        return $team !== null && $user->isTeamLead($team);
    }

    /**
     * Delete: creator or admin.
     */
    public function delete(User $user, VaultItem $item): bool
    {
        if ($user->isAdminOf($item->tenant)) {
            return true;
        }

        return $item->user_id === $user->id;
    }
}
