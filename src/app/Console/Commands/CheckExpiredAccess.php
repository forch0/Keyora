<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Events\AccessExpired;
use App\Models\AccessGrant;
use App\Models\User;
use App\Notifications\AccessExpiredNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckExpiredAccess extends Command
{
    protected $signature = 'access:check-expired';

    protected $description = 'Revoke access grants that have expired (expires_at <= now).';

    public function handle(): int
    {
        $now = Carbon::now();

        $expiredGrants = AccessGrant::withoutTenant()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->whereNull('revoked_at')
            ->get();

        if ($expiredGrants->isEmpty()) {
            $this->info('No expired access grants found.');

            return self::SUCCESS;
        }

        $count = 0;

        foreach ($expiredGrants as $grant) {
            $grant->update([
                'revoked_at' => $now,
                'revoke_reason' => 'expired',
            ]);

            AccessExpired::dispatch($grant);

            // Notify the grantee if it's a user grant
            if ($grant->subject_type === 'App\\Models\\User') {
                $subject = $grant->subject;
                if ($subject instanceof User) {
                    $resourceName = $this->getResourceName($grant);
                    $subject->notify(new AccessExpiredNotification($grant, $resourceName));
                }
            }

            $count++;
        }

        $this->info("Revoked {$count} expired access grant(s).");

        return self::SUCCESS;
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
