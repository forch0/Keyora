<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AccessGranted;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\AccessGrantedNotification;

class NotifyAccessGranted
{
    public function handle(AccessGranted $event): void
    {
        $grant = $event->grant;

        // Only notify direct user subjects
        if ($grant->subject_type === User::class) {
            $user = User::find($grant->subject_id);
            $user?->notify(new AccessGrantedNotification($grant));
        }

        // For team subjects, notify all team members
        if ($grant->subject_type === Team::class) {
            $team = Team::find($grant->subject_id);
            $team?->members->each(fn (User $member) => $member->notify(new AccessGrantedNotification($grant)));
        }

        // For tenant-wide grants, skip notification (too noisy)
    }
}
