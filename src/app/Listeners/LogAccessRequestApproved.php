<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AccessRequestApproved;
use App\Models\User;
use App\Services\ActivityLogger;

class LogAccessRequestApproved
{
    public function __construct(
        private readonly ActivityLogger $logger,
    ) {}

    public function handle(AccessRequestApproved $event): void
    {
        $reviewer = $event->request->reviewed_by !== null ? User::find($event->request->reviewed_by) : null;

        $this->logger->log(
            'access_request.approved',
            $reviewer,
            $event->request,
            ['grant_id' => $event->grant->id],
        );
    }
}
