<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Tenant;
use App\Models\User;

class OffboardEmployeeAction
{
    public function __construct(
        private readonly EmergencyRevokeAction $emergencyRevoke,
    ) {}

    /**
     * Offboard an employee — detach from tenant and revoke all access.
     *
     * @return int Count of revoked grants
     */
    public function __invoke(User $offboardedBy, User $targetUser, Tenant $tenant): int
    {
        // Revoke all access for the user
        $count = ($this->emergencyRevoke)($offboardedBy, $targetUser, 'offboarding');

        // Detach from tenant
        $tenant->users()->detach($targetUser->id);

        return $count;
    }
}
