<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class TenantMemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pivot = $this->resource->getRelation('pivot');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $pivot instanceof Pivot ? $pivot->getAttribute('role') : null,
            'status' => $pivot instanceof Pivot ? $pivot->getAttribute('status') : null,
            'joined_at' => $pivot instanceof Pivot ? $pivot->getAttribute('joined_at') : null,
            'suspended_at' => $pivot instanceof Pivot ? $pivot->getAttribute('suspended_at') : null,
            'teams_count' => $this->whenCounted('teams'),
            'created_at' => $this->created_at,
        ];
    }
}
