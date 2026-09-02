<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Notifications\TwoFactorDisabled;
use App\Services\ActivityLogger;
use App\Services\TwoFactorService;
use Illuminate\Support\Facades\Hash;

class DisableTwoFactorAction
{
    public function __construct(
        private readonly TwoFactorService $twoFactorService,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * Disable 2FA for the user after verifying the current password.
     *
     * @return bool True if disabled, false if password is incorrect.
     */
    public function __invoke(User $user, string $password): bool
    {
        if (! Hash::check($password, $user->password)) {
            return false;
        }

        $this->twoFactorService->disable($user);

        $this->activityLogger->log('auth.2fa_disabled', $user);
        $user->notify(new TwoFactorDisabled);

        return true;
    }
}
