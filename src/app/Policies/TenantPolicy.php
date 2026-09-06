<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    /**
     * View the tenant. Any member can view.
     */
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->isMemberOf($tenant);
    }

    /**
     * View the tenant activity feed. Admin or owner only.
     */
    public function viewActivityFeed(User $user, Tenant $tenant): bool
    {
        return $user->isAdminOf($tenant);
    }

    /**
     * Delete the tenant. Owner only.
     */
    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->ownsTenant($tenant);
    }
}
