<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\VaultItem;

class CreateOrgVaultItemAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(User $creator, array $attributes): VaultItem
    {
        return VaultItem::create([
            'team_id' => null,
            'user_id' => $creator->id,
            'name' => $attributes['name'],
            'type' => $attributes['type'],
            'username' => $attributes['username'] ?? null,
            'password' => $attributes['password'] ?? null,
            'url' => $attributes['url'] ?? null,
            'notes' => $attributes['notes'] ?? null,
            'metadata' => $attributes['metadata'] ?? null,
            'custom_fields' => $attributes['custom_fields'] ?? null,
            'folder_id' => $attributes['folder_id'] ?? null,
        ]);
    }
}
