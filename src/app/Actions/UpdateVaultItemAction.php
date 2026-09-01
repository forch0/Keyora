<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PersonalVaultItem;

class UpdateVaultItemAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(PersonalVaultItem $item, array $attributes): PersonalVaultItem
    {
        $item->update($attributes);

        /** @var PersonalVaultItem $fresh */
        $fresh = $item->fresh();

        return $fresh;
    }
}
