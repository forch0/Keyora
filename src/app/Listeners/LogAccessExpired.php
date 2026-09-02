<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AccessExpired;
use App\Services\ActivityLogger;

class LogAccessExpired
{
    public function __construct(
        private readonly ActivityLogger $logger,
    ) {}

    public function handle(AccessExpired $event): void
    {
        $this->logger->log(
            'access.expired',
            null,
            $event->grant,
            ['subject_type' => $event->grant->subject_type, 'subject_id' => $event->grant->subject_id],
        );
    }
}
