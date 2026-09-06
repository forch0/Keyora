<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SecureLinkCreated;
use App\Services\ActivityLogger;

class LogSecureLinkCreated
{
    public function __construct(
        private readonly ActivityLogger $logger,
    ) {}

    public function handle(SecureLinkCreated $event): void
    {
        $this->logger->log(
            'secure_link.created',
            $event->createdBy,
            $event->link,
            ['uuid' => $event->link->uuid],
        );
    }
}
