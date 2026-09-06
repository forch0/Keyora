<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\VaultItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin VaultItem */
class VaultItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'team_id' => $this->team_id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'type' => $this->type,
            'username' => $this->username,
            'password' => $this->password,
            'url' => $this->url,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'custom_fields' => $this->custom_fields,
            'favorite' => $this->favorite,
            'folder_id' => $this->folder_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
