<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Services\ActivityLogger;

class LogoutAllAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * Revoke all API tokens for the user (logout from all devices).
     */
    public function __invoke(User $user): void
    {
        $user->tokens()->delete();

        $this->activityLogger->log('auth.logout_all', $user);
    }
}
