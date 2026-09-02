<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\AccessRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AccessRequest */
class AccessRequestResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $morphResource = $this->resource()->first();

        return [
            'id' => $this->id,
            'resource' => [
                'type' => $this->resource_type,
                'id' => $this->resource_id,
                'name' => $morphResource?->getAttribute('title')
                    ?? $morphResource?->getAttribute('name')
                    ?? 'Resource #'.$this->resource_id,
            ],
            'requester' => [
                'id' => $this->requester_id,
                'name' => $this->requester?->name,
            ],
            'resource_owner' => [
                'id' => $this->resource_owner_id,
                'name' => $this->resourceOwner?->name,
            ],
            'requested_permission' => $this->requested_permission,
            'granted_permission' => $this->granted_permission,
            'requested_duration' => $this->requested_duration,
            'granted_expires_at' => $this->getAttribute('granted_expires_at')?->toIso8601String(),
            'reason' => $this->reason,
            'status' => $this->status,
            'review_note' => $this->review_note,
            'reviewed_by' => $this->reviewer?->name,
            'reviewed_at' => $this->getAttribute('reviewed_at')?->toIso8601String(),
            'created_at' => $this->getAttribute('created_at')?->toIso8601String(),
        ];
    }
}
