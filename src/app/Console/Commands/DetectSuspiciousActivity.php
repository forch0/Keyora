<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SecurityAlert;
use App\Models\User;
use App\Notifications\SuspiciousActivityAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DetectSuspiciousActivity extends Command
{
    protected $signature = 'security:detect-suspicious';

    protected $description = 'Detect suspicious activity patterns (failed logins, mass revocations).';

    public function handle(): int
    {
        $this->detectFailedLogins();
        $this->detectMassRevocations();

        return self::SUCCESS;
    }

    /**
     * Detect 5+ failed logins from the same IP in 15 minutes.
     */
    private function detectFailedLogins(): void
    {
        $cutoff = Carbon::now()->subMinutes(15);

        // Check activity logs for failed login attempts
        $failedAttempts = DB::table('activity_logs')
            ->where('action', 'auth.login_failed')
            ->where('created_at', '>=', $cutoff)
            ->select('ip_address', 'user_id', DB::raw('COUNT(*) as count'))
            ->groupBy('ip_address', 'user_id')
            ->having('count', '>=', 5)
            ->get();

        foreach ($failedAttempts as $attempt) {
            if ($attempt->user_id === null) {
                continue;
            }

            $user = User::find((int) $attempt->user_id);
            if ($user === null) {
                continue;
            }

            // Check if alert already exists recently
            $exists = SecurityAlert::where('user_id', $user->id)
                ->where('type', SecurityAlert::TYPE_SUSPICIOUS_ACTIVITY)
                ->where('created_at', '>=', $cutoff)
                ->exists();

            if ($exists) {
                continue;
            }

            $alert = SecurityAlert::create([
                'user_id' => $user->id,
                'type' => SecurityAlert::TYPE_SUSPICIOUS_ACTIVITY,
                'severity' => SecurityAlert::SEVERITY_WARNING,
                'title' => 'Suspicious activity detected',
                'message' => "Multiple failed login attempts detected from IP {$attempt->ip_address}.",
                'properties' => [
                    'activity_type' => 'failed_logins',
                    'ip_address' => $attempt->ip_address,
                    'count' => $attempt->count,
                ],
            ]);

            $user->notify(new SuspiciousActivityAlert([
                'activity_type' => 'failed_logins',
                'ip_address' => $attempt->ip_address,
                'count' => $attempt->count,
            ]));
        }
    }

    /**
     * Detect multiple emergency revocations in a short time.
     */
    private function detectMassRevocations(): void
    {
        $cutoff = Carbon::now()->subMinutes(15);

        $revocations = DB::table('activity_logs')
            ->where('action', 'access.emergency_revoked')
            ->where('created_at', '>=', $cutoff)
            ->select('user_id', DB::raw('COUNT(*) as count'))
            ->groupBy('user_id')
            ->having('count', '>=', 3)
            ->get();

        foreach ($revocations as $revocation) {
            if ($revocation->user_id === null) {
                continue;
            }

            $user = User::find((int) $revocation->user_id);
            if ($user === null) {
                continue;
            }

            $exists = SecurityAlert::where('user_id', $user->id)
                ->where('type', SecurityAlert::TYPE_SUSPICIOUS_ACTIVITY)
                ->where('created_at', '>=', $cutoff)
                ->exists();

            if ($exists) {
                continue;
            }

            SecurityAlert::create([
                'user_id' => $user->id,
                'type' => SecurityAlert::TYPE_SUSPICIOUS_ACTIVITY,
                'severity' => SecurityAlert::SEVERITY_WARNING,
                'title' => 'Mass revocation detected',
                'message' => "Multiple emergency revocations ({$revocation->count}) detected in the last 15 minutes.",
                'properties' => [
                    'activity_type' => 'mass_revocation',
                    'count' => $revocation->count,
                ],
            ]);
        }
    }
}
