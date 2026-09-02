<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\SecureLink;
use App\Models\User;
use App\Services\AccessResolver;

class SecureLinkPolicy
{
    public function __construct(
        private readonly AccessResolver $accessResolver,
    ) {}

    public function view(User $user, SecureLink $link): bool
    {
        return $link->created_by === $user->id
            || $this->isAdminOfTenant($user, $link);
    }

    public function delete(User $user, SecureLink $link): bool
    {
        if ($link->created_by === $user->id) {
            return true;
        }

        if ($this->isAdminOfTenant($user, $link)) {
            return true;
        }

        $resource = $link->resource;

        return $resource !== null
            && $this->accessResolver->can($user, Permission::Manage, $resource);
    }

    private function isAdminOfTenant(User $user, SecureLink $link): bool
    {
        $tenant = $link->tenant;

        return $tenant !== null && $user->isAdminOf($tenant);
    }
}
