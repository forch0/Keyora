<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PersonalVaultItem;

class DeleteVaultItemAction
{
    public function __invoke(PersonalVaultItem $item): void
    {
        $item->delete();
    }
}
