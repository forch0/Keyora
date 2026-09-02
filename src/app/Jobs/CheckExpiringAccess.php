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

class CheckExpiringAccess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $threshold = Carbon::now()->addHours(24);

        $grants = AccessGrant::withoutTenant()
            ->whereNull('revoked_at')
            ->where('expires_at', '<=', $threshold)
            ->where('expires_at', '>', Carbon::now())
            ->get();

        foreach ($grants as $grant) {
            // Only create alert for user subjects
            if ($grant->subject_type !== User::class) {
                continue;
            }

            $expiresAt = $grant->getAttribute('expires_at');
            if (! $expiresAt instanceof Carbon) {
                continue;
            }

            // Check if alert already exists for this grant
            $exists = SecurityAlert::where('user_id', $grant->subject_id)
                ->where('type', SecurityAlert::TYPE_EXPIRING_ACCESS)
                ->whereJsonContains('properties->grant_id', $grant->id)
                ->exists();

            if ($exists) {
                continue;
            }

            SecurityAlert::create([
                'tenant_id' => $grant->tenant_id,
                'user_id' => $grant->subject_id,
                'type' => SecurityAlert::TYPE_EXPIRING_ACCESS,
                'severity' => SecurityAlert::SEVERITY_INFO,
                'title' => 'Access expiring soon',
                'message' => 'Your access to a resource will expire at '.$expiresAt->toDateTimeString().'.',
                'properties' => [
                    'grant_id' => $grant->id,
                    'resource_type' => $grant->grantable_type,
                    'resource_id' => $grant->grantable_id,
                    'expires_at' => $expiresAt->toIso8601String(),
                ],
            ]);
        }
    }
}
