<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use App\Models\VaultItem;
use App\Services\AccessResolver;

class VaultItemPolicy
{
    public function __construct(
        private readonly AccessResolver $accessResolver,
    ) {}

    /**
     * View: owner, or has a grant with at least 'view' permission,
     * or is a team member / tenant member (for team/org items).
     */
    public function view(User $user, VaultItem $item): bool
    {
        // Check AccessResolver first (owner + grants)
        if ($this->accessResolver->can($user, Permission::View, $item)) {
            return true;
        }

        // Fall back to team/org membership for items without explicit grants
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
     * Update: owner, grant with 'edit', team lead, or admin.
     */
    public function update(User $user, VaultItem $item): bool
    {
        // Check AccessResolver (owner + grants)
        if ($this->accessResolver->can($user, Permission::Edit, $item)) {
            return true;
        }

        if ($user->isAdminOf($item->tenant)) {
            return true;
        }

        $team = $item->team;

        return $team !== null && $user->isTeamLead($team);
    }

    /**
     * Delete: owner, grant with 'manage', or admin.
     */
    public function delete(User $user, VaultItem $item): bool
    {
        // Check AccessResolver (owner + grants)
        if ($this->accessResolver->can($user, Permission::Manage, $item)) {
            return true;
        }

        if ($user->isAdminOf($item->tenant)) {
            return true;
        }

        return false;
    }
}
