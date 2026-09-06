<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AccessUpdated;
use App\Services\ActivityLogger;

class LogAccessUpdated
{
    public function __construct(
        private readonly ActivityLogger $logger,
    ) {}

    public function handle(AccessUpdated $event): void
    {
        $this->logger->log(
            'access.updated',
            $event->updatedBy,
            $event->grant,
            ['permission' => $event->grant->permission],
        );
    }
}
