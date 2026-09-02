<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AccessGranted;

/**
 * Stub listener — full activity log implementation in Module 20.
 */
class LogAccessGranted
{
    public function handle(AccessGranted $event): void
    {
        // TODO: Module 20 — log to activity log
    }
}
