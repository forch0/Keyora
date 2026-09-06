<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UserDevice */
class DeviceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'browser' => $this->browser,
            'os' => $this->os,
            'device_type' => $this->device_type,
            'ip_address' => $this->ip_address,
            'last_seen_at' => $this->last_seen_at->toIso8601String(),
            'first_seen_at' => $this->first_seen_at->toIso8601String(),
            'is_current_device' => $this->when(isset($this->is_current_device), fn () => $this->is_current_device ?? false),
        ];
    }
}
