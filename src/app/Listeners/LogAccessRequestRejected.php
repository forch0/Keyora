<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AccessRequestRejected;
use App\Models\User;
use App\Services\ActivityLogger;

class LogAccessRequestRejected
{
    public function __construct(
        private readonly ActivityLogger $logger,
    ) {}

    public function handle(AccessRequestRejected $event): void
    {
        $reviewer = $event->request->reviewed_by !== null ? User::find($event->request->reviewed_by) : null;

        $this->logger->log(
            'access_request.rejected',
            $reviewer,
            $event->request,
        );
    }
}
