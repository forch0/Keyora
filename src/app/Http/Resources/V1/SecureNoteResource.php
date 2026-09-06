<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\SecureNote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SecureNote */
class SecureNoteResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'content_format' => $this->content_format,
            'is_pinned' => $this->is_pinned,
            'folder_id' => $this->folder_id,
            'team_id' => $this->team_id,
            'tenant_id' => $this->tenant_id,
            'created_by' => $this->user?->name,
            'created_by_id' => $this->user_id,
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color,
            ])),
            'created_at' => $this->resource->getAttribute('created_at')?->toIso8601String(),
            'updated_at' => $this->resource->getAttribute('updated_at')?->toIso8601String(),
        ];
    }
}
