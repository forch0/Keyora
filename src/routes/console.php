<?php

use App\Jobs\SendExpirationWarning;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Soft-delete expired files daily at midnight
Schedule::command('files:expire')->dailyAt('00:00')->description('Soft-delete expired secure files and notify owners');

// Revoke expired access grants every minute
Schedule::command('access:check-expired')->everyMinute()->description('Revoke expired access grants');

// Send expiration warning notifications hourly
Schedule::job(new SendExpirationWarning)->hourly()->description('Send access expiration warnings');
