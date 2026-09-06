<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ResourceViewed;
use App\Services\ActivityLogger;

class LogResourceViewed
{
    public function __construct(
        private readonly ActivityLogger $logger,
    ) {}

    public function handle(ResourceViewed $event): void
    {
        $action = match (true) {
            str_contains($event->resource::class, 'VaultItem') => 'vault_item.viewed',
            str_contains($event->resource::class, 'SecureNote') => 'note.viewed',
            default => 'resource.viewed',
        };

        $this->logger->log($action, $event->user, $event->resource);
    }
}
