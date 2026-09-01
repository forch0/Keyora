<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\PersonalVaultTag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PersonalVaultTag */
class PersonalVaultTagResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
            'items_count' => $this->whenCounted('items', fn () => $this->items()->count()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
