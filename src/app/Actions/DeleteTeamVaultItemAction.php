<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\VaultItem;

class DeleteTeamVaultItemAction
{
    public function __invoke(VaultItem $item): void
    {
        $item->delete();
    }
}
