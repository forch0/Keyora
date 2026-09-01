<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    /**
     * User must be a member of the tenant to view it.
     */
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->isMemberOf($tenant);
    }

    /**
     * User must be owner or admin to update the tenant.
     */
    public function update(User $user, Tenant $tenant): bool
    {
        return in_array($user->roleIn($tenant), ['owner', 'admin'], true);
    }

    /**
     * User must be the owner to delete the tenant.
     */
    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->ownsTenant($tenant);
    }
}
