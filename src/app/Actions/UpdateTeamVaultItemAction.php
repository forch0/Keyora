<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\VaultItem;

class UpdateTeamVaultItemAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(VaultItem $item, array $attributes): VaultItem
    {
        $item->update($attributes);

        return $item->fresh() ?? $item;
    }
}
