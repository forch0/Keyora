<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PersonalVaultItem;
use App\Models\User;

class PersonalVaultItemPolicy
{
    /**
     * Owner-only access. Returns false (not 403) so the controller
     * can abort(404) — we don't leak the existence of other users' items.
     */
    public function view(User $user, PersonalVaultItem $item): bool
    {
        return $user->id === $item->user_id;
    }

    public function update(User $user, PersonalVaultItem $item): bool
    {
        return $user->id === $item->user_id;
    }

    public function delete(User $user, PersonalVaultItem $item): bool
    {
        return $user->id === $item->user_id;
    }
}
