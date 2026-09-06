<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AllAccessRevokedForResource;
use App\Services\ActivityLogger;

class LogAllAccessRevokedForResource
{
    public function __construct(
        private readonly ActivityLogger $logger,
    ) {}

    public function handle(AllAccessRevokedForResource $event): void
    {
        $this->logger->log(
            'access.emergency_revoked',
            $event->revokedBy,
            $event->resource,
            ['count' => $event->count],
        );
    }
}
