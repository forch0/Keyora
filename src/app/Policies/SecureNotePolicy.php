<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\SecureNote;
use App\Models\User;
use App\Services\AccessResolver;

class SecureNotePolicy
{
    public function __construct(
        private readonly AccessResolver $accessResolver,
    ) {}

    /**
     * View: creator, team member, or has access grant.
     */
    public function view(User $user, SecureNote $note): bool
    {
        if ($this->accessResolver->can($user, Permission::View, $note)) {
            return true;
        }

        if ($note->user_id === $user->id) {
            return true;
        }

        $tenant = $note->tenant;
        if ($tenant !== null && $user->isAdminOf($tenant)) {
            return true;
        }

        if ($note->team_id !== null) {
            $team = $note->team;

            return $team !== null && $user->isTeamMember($team);
        }

        return false;
    }

    /**
     * Update: creator or has edit/manage permission.
     */
    public function update(User $user, SecureNote $note): bool
    {
        if ($this->accessResolver->can($user, Permission::Edit, $note)) {
            return true;
        }

        if ($note->user_id === $user->id) {
            return true;
        }

        $tenant = $note->tenant;

        return $tenant !== null && $user->isAdminOf($tenant);
    }

    /**
     * Delete: creator or has manage permission.
     */
    public function delete(User $user, SecureNote $note): bool
    {
        if ($this->accessResolver->can($user, Permission::Manage, $note)) {
            return true;
        }

        if ($note->user_id === $user->id) {
            return true;
        }

        $tenant = $note->tenant;

        return $tenant !== null && $user->isAdminOf($tenant);
    }
}
