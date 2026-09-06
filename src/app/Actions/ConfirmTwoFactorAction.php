<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Notifications\TwoFactorEnabled;
use App\Services\ActivityLogger;
use App\Services\TwoFactorService;

class ConfirmTwoFactorAction
{
    public function __construct(
        private readonly TwoFactorService $twoFactorService,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * Confirm 2FA with a TOTP code and generate recovery codes.
     *
     * @return array{recovery_codes: array<int, string>}|null
     */
    public function __invoke(User $user, string $code): ?array
    {
        if (! $this->twoFactorService->verifyCode($user, $code)) {
            return null;
        }

        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();
        $this->twoFactorService->storeRecoveryCodes($user, $recoveryCodes);

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        $this->activityLogger->log('auth.2fa_enabled', $user);
        $user->notify(new TwoFactorEnabled);

        return ['recovery_codes' => $recoveryCodes];
    }
}
