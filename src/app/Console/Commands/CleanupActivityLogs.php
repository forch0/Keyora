<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CleanupActivityLogs extends Command
{
    protected $signature = 'activity-logs:cleanup {--days=365 : Number of days to retain logs}';

    protected $description = 'Delete activity logs older than the retention period.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = Carbon::now()->subDays($days);

        $count = ActivityLog::where('created_at', '<', $cutoff)->count();

        if ($count === 0) {
            $this->info('No activity logs older than '.$days.' days found.');

            return self::SUCCESS;
        }

        ActivityLog::where('created_at', '<', $cutoff)->delete();

        $this->info("Deleted {$count} activity logs older than {$days} days.");

        return self::SUCCESS;
    }
}
