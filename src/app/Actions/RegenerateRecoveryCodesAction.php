<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Notifications\RecoveryCodesRegenerated;
use App\Services\ActivityLogger;
use App\Services\TwoFactorService;

class RegenerateRecoveryCodesAction
{
    public function __construct(
        private readonly TwoFactorService $twoFactorService,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * Generate new recovery codes for the user.
     *
     * @return array<int, string>
     */
    public function __invoke(User $user): array
    {
        $codes = $this->twoFactorService->generateRecoveryCodes();
        $this->twoFactorService->storeRecoveryCodes($user, $codes);

        $this->activityLogger->log('auth.recovery_codes_regenerated', $user);
        $user->notify(new RecoveryCodesRegenerated);

        return $codes;
    }
}
