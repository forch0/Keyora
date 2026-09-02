<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AccessRevoked;

/**
 * Stub listener — full activity log implementation in Module 20.
 */
class LogAccessRevoked
{
    public function handle(AccessRevoked $event): void
    {
        // TODO: Module 20 — log to activity log
    }
}
