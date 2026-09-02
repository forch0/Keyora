<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\FileUploaded;
use App\Services\ActivityLogger;

class LogFileUploaded
{
    public function __construct(
        private readonly ActivityLogger $logger,
    ) {}

    public function handle(FileUploaded $event): void
    {
        $this->logger->log(
            'file.uploaded',
            $event->uploadedBy,
            $event->file,
            ['name' => $event->file->name],
        );
    }
}
