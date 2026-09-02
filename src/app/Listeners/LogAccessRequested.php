<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AccessRequested;
use App\Models\User;
use App\Services\ActivityLogger;

class LogAccessRequested
{
    public function __construct(
        private readonly ActivityLogger $logger,
    ) {}

    public function handle(AccessRequested $event): void
    {
        $requester = User::find($event->request->requester_id);

        $this->logger->log(
            'access_request.created',
            $requester,
            $event->request,
            ['resource_type' => $event->request->resource_type, 'resource_id' => $event->request->resource_id],
        );
    }
}
