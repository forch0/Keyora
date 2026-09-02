<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\AccessGrant;
use App\Models\AccessRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccessRequestApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly AccessRequest $request,
        public readonly AccessGrant $grant,
    ) {}
}
