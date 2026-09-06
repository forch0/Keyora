<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Services\ReauthenticationService;

class ReauthenticateAction
{
    public function __construct(
        private readonly ReauthenticationService $reauthenticationService,
    ) {}

    /**
     * Re-authenticate the user with their password.
     *
     * @return bool True if password is correct.
     */
    public function __invoke(User $user, string $password): bool
    {
        return $this->reauthenticationService->reauthenticate($user, $password);
    }
}
