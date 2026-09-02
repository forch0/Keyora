<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AccessGranted;
use App\Services\ActivityLogger;

class LogAccessGranted
{
    public function __construct(
        private readonly ActivityLogger $logger,
    ) {}

    public function handle(AccessGranted $event): void
    {
        $this->logger->log(
            'access.granted',
            $event->grantedBy,
            $event->grant,
            [
                'subject_type' => $event->grant->subject_type,
                'subject_id' => $event->grant->subject_id,
                'permission' => $event->grant->permission,
            ],
        );
    }
}
