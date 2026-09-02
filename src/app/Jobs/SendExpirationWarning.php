<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AccessGrant;
use App\Models\User;
use App\Notifications\AccessExpiringSoon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SendExpirationWarning implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $now = Carbon::now();
        $warningWindow = Carbon::now()->addHours(24);

        // Find grants expiring within 24 hours that haven't had a warning sent
        $grants = AccessGrant::withoutTenant()
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', $now)
            ->where('expires_at', '<=', $warningWindow)
            ->whereNull('revoked_at')
            ->whereNull('warning_sent_at')
            ->get();

        foreach ($grants as $grant) {
            // Only notify user subjects
            if ($grant->subject_type !== 'App\\Models\\User') {
                continue;
            }

            $subject = $grant->subject;
            if (! $subject instanceof User) {
                continue;
            }

            $resourceName = $this->getResourceName($grant);

            $subject->notify(new AccessExpiringSoon($grant, $resourceName));

            $grant->update(['warning_sent_at' => $now]);
        }
    }

    private function getResourceName(AccessGrant $grant): string
    {
        $resource = $grant->grantable;

        if ($resource === null) {
            return 'Unknown Resource';
        }

        return $resource->getAttribute('title')
            ?? $resource->getAttribute('name')
            ?? 'Resource #'.$grant->grantable_id;
    }
}
