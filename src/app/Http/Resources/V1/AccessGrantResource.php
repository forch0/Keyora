<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Enums\Permission;
use App\Models\AccessGrant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AccessGrant */
class AccessGrantResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $subject = $this->subject;
        $subjectName = null;
        if ($subject !== null) {
            $subjectName = $subject->getAttribute('name') ?? $subject->getAttribute('email');
        }

        /** @var Permission $permission */
        $permission = $this->resource->getAttribute('permission');

        return [
            'id' => $this->id,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'subject_name' => $subjectName,
            'permission' => $permission->value,
            'expires_at' => $this->resource->getAttribute('expires_at')?->toIso8601String(),
            'max_views' => $this->max_views,
            'views_count' => $this->views_count,
            'starts_at' => $this->resource->getAttribute('starts_at')?->toIso8601String(),
            'start_on_first_view' => $this->start_on_first_view,
            'first_viewed_at' => $this->resource->getAttribute('first_viewed_at')?->toIso8601String(),
            'granted_by' => $this->grantedBy?->name,
            'granted_by_id' => $this->granted_by,
            'created_at' => $this->resource->getAttribute('created_at')?->toIso8601String(),
            'is_active' => $this->isActive(),
        ];
    }
}
