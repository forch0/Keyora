<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use RuntimeException;

class AcceptInvitationAction
{
    /**
     * Accept an invitation by token, attaching the user to the tenant.
     *
     * @return Tenant The tenant the user has joined
     *
     * @throws RuntimeException If the invitation is invalid, expired, or already accepted
     */
    public function __invoke(User $user, string $token): Tenant
    {
        /** @var TenantInvitation|null $invitation */
        $invitation = TenantInvitation::where('token', $token)->first();

        if ($invitation === null) {
            throw new RuntimeException('Invalid invitation token.', 422);
        }

        if ($invitation->isAccepted()) {
            throw new RuntimeException('This invitation has already been accepted.', 422);
        }

        if ($invitation->isExpired()) {
            throw new RuntimeException('This invitation has expired.', 422);
        }

        $tenant = $invitation->tenant;

        if ($tenant === null) {
            throw new RuntimeException('Invitation tenant no longer exists.', 422);
        }

        // Attach the user to the tenant with the invited role.
        // If already a member (e.g. re-accepted), update the existing pivot.
        if ($user->tenants()->where('tenants.id', $tenant->id)->exists()) {
            $user->tenants()->updateExistingPivot($tenant->id, [
                'role' => $invitation->role,
                'status' => 'active',
                'left_at' => null,
                'suspended_at' => null,
                'joined_at' => now(),
            ]);
        } else {
            $user->tenants()->attach($tenant->id, [
                'role' => $invitation->role,
                'status' => 'active',
                'joined_at' => now(),
            ]);
        }

        $invitation->update(['accepted_at' => now()]);

        return $tenant;
    }
}
