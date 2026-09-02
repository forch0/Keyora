<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AccessGrant;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class RevokeTeamAccessAction
{
    /**
     * Revoke all grants where subject_type = Team and subject_id = team.id
     * for a specific resource.
     *
     * @return int Count of revoked grants
     */
    public function __invoke(User $revokedBy, Model $resource, Team $team, string $reason = 'manual'): int
    {
        $now = Carbon::now();

        return AccessGrant::withoutTenant()
            ->where('grantable_type', $resource::class)
            ->where('grantable_id', $resource->getKey())
            ->where('subject_type', 'App\\Models\\Team')
            ->where('subject_id', $team->id)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => $now,
                'revoked_by' => $revokedBy->id,
                'revoke_reason' => $reason,
            ]);
    }
}
