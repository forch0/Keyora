<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\ResourceViewed;
use App\Models\AccessGrant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ViewTracker
{
    /**
     * Record a view on a resource by a user.
     *
     * - Finds active grants for this user + resource
     * - For start_on_first_view grants: sets first_viewed_at if null
     * - Increments views_count on all matching grants
     * - Auto-revokes if views_count >= max_views
     * - Dispatches ResourceViewed event
     */
    public function recordView(User $user, Model $resource): void
    {
        $grants = AccessGrant::withoutTenant()
            ->where('grantable_type', $resource::class)
            ->where('grantable_id', $resource->getKey())
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->whereNull('revoked_at')
            ->get();

        foreach ($grants as $grant) {
            // Start the clock on first view if applicable
            if ($grant->start_on_first_view && $grant->first_viewed_at === null) {
                $grant->update(['first_viewed_at' => Carbon::now()]);
            }

            // Increment view count
            $grant->increment('views_count');
            $grant->refresh();

            // Auto-revoke if view limit reached
            if ($grant->max_views !== null && $grant->views_count >= $grant->max_views) {
                $grant->update([
                    'revoked_at' => Carbon::now(),
                    'revoke_reason' => 'view_limit_reached',
                ]);
            }
        }

        ResourceViewed::dispatch($resource, $user);
    }
}
