<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\AccessGrant;
use App\Models\User;
use App\Services\AccessResolver;

class AccessGrantPolicy
{
    public function __construct(
        private readonly AccessResolver $accessResolver,
    ) {}

    /**
     * Create (grant): user must have 'share' permission on the grantable resource.
     */
    public function create(User $user, AccessGrant $grant): bool
    {
        $resource = $grant->grantable;

        if ($resource === null) {
            return false;
        }

        return $this->accessResolver->can($user, Permission::Share, $resource);
    }

    /**
     * Update: user must have 'share' or 'manage' permission.
     */
    public function update(User $user, AccessGrant $grant): bool
    {
        $resource = $grant->grantable;

        if ($resource === null) {
            return false;
        }

        return $this->accessResolver->can($user, Permission::Share, $resource);
    }

    /**
     * Delete (revoke): user must have 'share' or 'manage' permission.
     */
    public function delete(User $user, AccessGrant $grant): bool
    {
        $resource = $grant->grantable;

        if ($resource === null) {
            return false;
        }

        return $this->accessResolver->can($user, Permission::Share, $resource);
    }
}
