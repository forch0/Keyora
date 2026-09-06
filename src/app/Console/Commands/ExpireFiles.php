<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Events\FileExpired;
use App\Models\SecureFile;
use App\Notifications\FileExpiredNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ExpireFiles extends Command
{
    protected $signature = 'files:expire';

    protected $description = 'Soft-delete expired files and notify their owners.';

    public function handle(): int
    {
        $now = Carbon::now();

        $expiredFiles = SecureFile::withoutTenant()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->whereNull('deleted_at')
            ->whereNull('archived_at')
            ->get();

        if ($expiredFiles->isEmpty()) {
            $this->info('No expired files found.');

            return self::SUCCESS;
        }

        $count = 0;

        foreach ($expiredFiles as $file) {
            $file->delete();

            FileExpired::dispatch($file);

            $owner = $file->user;
            if ($owner !== null) {
                $owner->notify(new FileExpiredNotification($file));
            }

            $count++;
        }

        $this->info("Expired {$count} file(s).");

        return self::SUCCESS;
    }
}
