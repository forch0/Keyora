<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PersonalVaultItem;
use App\Models\User;
use App\Services\ActivityLogger;

class CreateVaultItemAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(User $user, array $attributes): PersonalVaultItem
    {
        $item = PersonalVaultItem::create([
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

        $this->activityLogger->log('vault_item.created', $user, $item);

        return $item;
    }
}
