<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\AccessRequest;
use App\Models\User;
use App\Services\AccessResolver;

class AccessRequestPolicy
{
    public function __construct(
        private readonly AccessResolver $accessResolver,
    ) {}

    public function view(User $user, AccessRequest $request): bool
    {
        return $request->requester_id === $user->id
            || $request->resource_owner_id === $user->id;
    }

    public function approve(User $user, AccessRequest $request): bool
    {
        if ($request->resource_owner_id === $user->id) {
            return true;
        }

        $resource = $request->resource;

        if ($resource === null) {
            return false;
        }

        return $this->accessResolver->can($user, Permission::Share, $resource);
    }

    public function reject(User $user, AccessRequest $request): bool
    {
        return $this->approve($user, $request);
    }

    public function cancel(User $user, AccessRequest $request): bool
    {
        return $request->requester_id === $user->id
            && $request->status === 'pending';
    }
}
