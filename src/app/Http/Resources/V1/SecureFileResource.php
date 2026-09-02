<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\SecureFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SecureFile */
class SecureFileResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'mime_type' => $this->mime_type,
            'size' => $this->human_size,
            'size_bytes' => $this->size,
            'checksum' => $this->checksum,
            'description' => $this->description,
            'download_enabled' => $this->download_enabled,
            'expires_at' => $this->resource->getAttribute('expires_at')?->toIso8601String(),
            'archived_at' => $this->resource->getAttribute('archived_at')?->toIso8601String(),
            'folder_id' => $this->folder_id,
            'team_id' => $this->team_id,
            'uploaded_by' => $this->user?->name,
            'uploaded_by_id' => $this->user_id,
            'download_url' => "/api/v1/files/{$this->id}/download",
            'created_at' => $this->resource->getAttribute('created_at')?->toIso8601String(),
            'updated_at' => $this->resource->getAttribute('updated_at')?->toIso8601String(),
        ];
    }
}
