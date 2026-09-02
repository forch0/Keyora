<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\EmergencyRevoked;
use App\Services\ActivityLogger;

class LogEmergencyRevoked
{
    public function __construct(
        private readonly ActivityLogger $logger,
    ) {}

    public function handle(EmergencyRevoked $event): void
    {
        $this->logger->log(
            'access.emergency_revoked',
            $event->revokedBy,
            $event->targetUser,
            ['count' => $event->count],
        );
    }
}
