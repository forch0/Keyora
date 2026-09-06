<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PersonalVaultTag;
use App\Models\User;

class PersonalVaultTagPolicy
{
    public function view(User $user, PersonalVaultTag $tag): bool
    {
        return $user->id === $tag->user_id;
    }

    public function update(User $user, PersonalVaultTag $tag): bool
    {
        return $user->id === $tag->user_id;
    }

    public function delete(User $user, PersonalVaultTag $tag): bool
    {
        return $user->id === $tag->user_id;
    }
}
