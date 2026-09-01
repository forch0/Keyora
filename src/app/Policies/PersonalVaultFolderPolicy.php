<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PersonalVaultFolder;
use App\Models\User;

class PersonalVaultFolderPolicy
{
    public function view(User $user, PersonalVaultFolder $folder): bool
    {
        return $user->id === $folder->user_id;
    }

    public function update(User $user, PersonalVaultFolder $folder): bool
    {
        return $user->id === $folder->user_id;
    }

    public function delete(User $user, PersonalVaultFolder $folder): bool
    {
        return $user->id === $folder->user_id;
    }
}
