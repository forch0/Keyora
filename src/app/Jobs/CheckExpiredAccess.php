<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AccessGrant;
use App\Models\SecurityAlert;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class CheckExpiredAccess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $oneHourAgo = Carbon::now()->subHour();

        $grants = AccessGrant::withoutTenant()
            ->whereNull('revoked_at')
            ->where('expires_at', '<=', Carbon::now())
            ->where('expires_at', '>=', $oneHourAgo)
            ->get();

        foreach ($grants as $grant) {
            if ($grant->subject_type !== User::class) {
                continue;
            }

            $expiresAt = $grant->getAttribute('expires_at');
            if (! $expiresAt instanceof Carbon) {
                continue;
            }

            $exists = SecurityAlert::where('user_id', $grant->subject_id)
                ->where('type', SecurityAlert::TYPE_ACCESS_EXPIRED)
                ->whereJsonContains('properties->grant_id', $grant->id)
                ->exists();

            if ($exists) {
                continue;
            }

            SecurityAlert::create([
                'tenant_id' => $grant->tenant_id,
                'user_id' => $grant->subject_id,
                'type' => SecurityAlert::TYPE_ACCESS_EXPIRED,
                'severity' => SecurityAlert::SEVERITY_INFO,
                'title' => 'Access expired',
                'message' => 'Your access to a resource has expired.',
                'properties' => [
                    'grant_id' => $grant->id,
                    'resource_type' => $grant->grantable_type,
                    'resource_id' => $grant->grantable_id,
                    'expired_at' => $expiresAt->toIso8601String(),
                ],
            ]);
        }
    }
}
