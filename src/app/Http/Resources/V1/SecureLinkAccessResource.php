<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\SecureLinkAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SecureLinkAccess */
class SecureLinkAccessResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'email' => $this->email,
            'accessed_at' => $this->getAttribute('accessed_at')?->toIso8601String(),
        ];
    }
}
