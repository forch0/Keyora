<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class TeamMemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pivot = $this->getRelation('pivot');
        $role = $pivot instanceof Pivot ? $pivot->getAttribute('role') : null;
        $joinedAt = $pivot instanceof Pivot ? $pivot->getAttribute('joined_at') : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $role,
            'joined_at' => $joinedAt,
        ];
    }
}
