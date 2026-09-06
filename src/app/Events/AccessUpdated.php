<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\AccessGrant;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccessUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly AccessGrant $grant,
        public readonly User $updatedBy,
    ) {}
}
