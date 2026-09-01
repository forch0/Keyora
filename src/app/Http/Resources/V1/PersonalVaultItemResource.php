<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\PersonalVaultItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PersonalVaultItem */
class PersonalVaultItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'username' => $this->username,
            'password' => $this->password,
            'url' => $this->url,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'custom_fields' => $this->custom_fields,
            'favorite' => $this->favorite,
            'archived_at' => $this->archived_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
