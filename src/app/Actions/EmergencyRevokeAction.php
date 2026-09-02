<?php

declare(strict_types=1);

namespace App\Actions;

use App\Events\EmergencyRevoked;
use App\Models\AccessGrant;
use App\Models\User;
use App\Notifications\AccessRevokedNotification;
use Illuminate\Support\Carbon;

class EmergencyRevokeAction
{
    /**
     * Emergency revoke ALL access for a user.
     *
     * Revokes:
     * - All direct user grants
     * - All team grants for the user's teams
     * - (Secure links will be added in Module 17)
     * Notifies the user of the revocation.
     *
     * @return int Count of revoked grants
     */
    public function __invoke(User $revokedBy, User $targetUser, string $reason = 'emergency'): int
    {
        $now = Carbon::now();
        $count = 0;

        // Revoke all direct user grants
        $directCount = AccessGrant::withoutTenant()
            ->where('subject_type', User::class)
            ->where('subject_id', $targetUser->id)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => $now,
                'revoked_by' => $revokedBy->id,
                'revoke_reason' => $reason,
            ]);

        $count += $directCount;

        // Revoke all team grants for user's teams
        $teamIds = $targetUser->teams()->pluck('teams.id');

        if ($teamIds->isNotEmpty()) {
            $teamCount = AccessGrant::withoutTenant()
                ->where('subject_type', 'App\\Models\\Team')
                ->whereIn('subject_id', $teamIds)
                ->whereNull('revoked_at')
                ->update([
                    'revoked_at' => $now,
                    'revoked_by' => $revokedBy->id,
                    'revoke_reason' => $reason,
                ]);

            $count += $teamCount;
        }

        // Secure links will be revoked here once Module 17 is implemented

        // Notify the user
        if ($count > 0) {
            $targetUser->notify(new AccessRevokedNotification('All Resources', $reason));
        }

        EmergencyRevoked::dispatch($targetUser, $revokedBy, $count);

        return $count;
    }
}
