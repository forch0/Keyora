<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PersonalVaultItem;
use App\Models\User;

class CreateVaultItemAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(User $user, array $attributes): PersonalVaultItem
    {
        return PersonalVaultItem::create([
            'user_id' => $user->id,
            'name' => $attributes['name'],
            'type' => $attributes['type'],
            'username' => $attributes['username'] ?? null,
            'password' => $attributes['password'] ?? null,
            'url' => $attributes['url'] ?? null,
            'notes' => $attributes['notes'] ?? null,
            'metadata' => $attributes['metadata'] ?? null,
            'custom_fields' => $attributes['custom_fields'] ?? null,
            'favorite' => $attributes['favorite'] ?? false,
        ]);
    }
}
