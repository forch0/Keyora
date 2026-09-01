<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantMemberPolicy
{
    /**
     * View the list of members or a specific member's profile.
     * User must be an active member of the tenant.
     */
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->isMemberOf($tenant);
    }

    /**
     * Invite a new member. User must be admin or owner.
     */
    public function invite(User $user, Tenant $tenant): bool
    {
        return $user->isAdminOf($tenant);
    }

    /**
     * Change a member's role. User must be admin or owner.
     * Cannot change the owner's role.
     */
    public function update(User $user, Tenant $tenant, User $member): bool
    {
        if (! $user->isAdminOf($tenant)) {
            return false;
        }

        // Cannot change the owner's role
        if ($member->ownsTenant($tenant)) {
            return false;
        }

        return true;
    }

    /**
     * Suspend a member. User must be admin or owner.
     * Cannot suspend the owner.
     */
    public function suspend(User $user, Tenant $tenant, User $member): bool
    {
        if (! $user->isAdminOf($tenant)) {
            return false;
        }

        if ($member->ownsTenant($tenant)) {
            return false;
        }

        return true;
    }

    /**
     * Restore a suspended member. User must be admin or owner.
     */
    public function restore(User $user, Tenant $tenant, User $member): bool
    {
        if (! $user->isAdminOf($tenant)) {
            return false;
        }

        if ($member->ownsTenant($tenant)) {
            return false;
        }

        return true;
    }

    /**
     * Remove a member from the tenant. User must be admin or owner.
     * Cannot remove the owner.
     */
    public function remove(User $user, Tenant $tenant, User $member): bool
    {
        if (! $user->isAdminOf($tenant)) {
            return false;
        }

        if ($member->ownsTenant($tenant)) {
            return false;
        }

        return true;
    }

    /**
     * List or cancel pending invitations. User must be admin or owner.
     */
    public function manageInvitations(User $user, Tenant $tenant): bool
    {
        return $user->isAdminOf($tenant);
    }
}
