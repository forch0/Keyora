<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ActivityLog */
class ActivityLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'user' => $this->when($this->user_id !== null, fn (): array => [
                'id' => $this->user_id,
                'name' => $this->user?->name,
            ]),
            'subject' => $this->when($this->subject_type !== null, fn (): array => [
                'type' => $this->subject_type,
                'id' => $this->subject_id,
            ]),
            'properties' => $this->properties,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
