<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\FileFolder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FileFolder */
class FileFolderResource extends JsonResource
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
            'team_id' => $this->team_id,
            'parent_id' => $this->parent_id,
            'created_by' => $this->createdBy?->name,
            'created_at' => $this->resource->getAttribute('created_at')?->toIso8601String(),
            'children_count' => $this->whenCounted('children', fn () => $this->children_count, 0),
            'files_count' => $this->whenCounted('files', fn () => $this->files_count, 0),
        ];
    }
}
