<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AccessRevoked;
use App\Services\ActivityLogger;

class LogAccessRevoked
{
    public function __construct(
        private readonly ActivityLogger $logger,
    ) {}

    public function handle(AccessRevoked $event): void
    {
        $this->logger->log(
            'access.revoked',
            $event->revokedBy,
            $event->grant,
            [
                'subject_type' => $event->grant->subject_type,
                'subject_id' => $event->grant->subject_id,
                'reason' => $event->reason,
            ],
        );
    }
}
