<?php

declare(strict_types=1);

namespace App\Actions;

use App\Events\AllAccessRevokedForResource;
use App\Models\AccessGrant;
use App\Models\User;
use App\Notifications\AccessRevokedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class RevokeAllAccessAction
{
    /**
     * Revoke all active grants for a specific resource.
     * Notifies each affected user.
     *
     * @return int Count of revoked grants
     */
    public function __invoke(User $revokedBy, Model $resource, string $reason = 'manual'): int
    {
        $now = Carbon::now();

        $resourceName = $resource->getAttribute('title')
            ?? $resource->getAttribute('name')
            ?? 'Resource #'.$resource->getKey();

        // Get affected user subjects before revoking
        $affectedUserIds = AccessGrant::withoutTenant()
            ->where('grantable_type', $resource::class)
            ->where('grantable_id', $resource->getKey())
            ->whereNull('revoked_at')
            ->where('subject_type', User::class)
            ->pluck('subject_id')
            ->unique();

        $count = AccessGrant::withoutTenant()
            ->where('grantable_type', $resource::class)
            ->where('grantable_id', $resource->getKey())
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => $now,
                'revoked_by' => $revokedBy->id,
                'revoke_reason' => $reason,
            ]);

        // Notify each affected user
        foreach ($affectedUserIds as $userId) {
            $affectedUser = User::find($userId);
            if ($affectedUser instanceof User) {
                $affectedUser->notify(new AccessRevokedNotification($resourceName, $reason));
            }
        }

        AllAccessRevokedForResource::dispatch($resource, $revokedBy, $count);

        return $count;
    }
}
