<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SecureFile;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FileUploaded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly SecureFile $file,
        public readonly User $uploadedBy,
    ) {}
}
