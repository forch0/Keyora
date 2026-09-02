<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SecureLink;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SecureLinkCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly SecureLink $link,
        public readonly User $createdBy,
    ) {}
}
