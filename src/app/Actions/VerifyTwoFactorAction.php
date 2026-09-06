<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ReauthenticationService;
use App\Services\TwoFactorService;

class VerifyTwoFactorAction
{
    public function __construct(
        private readonly TwoFactorService $twoFactorService,
        private readonly ActivityLogger $activityLogger,
        private readonly ReauthenticationService $reauthenticationService,
    ) {}

    /**
     * Verify a 2FA code or recovery code during login.
     *
     * @return array{user: User, token: string}|null
     */
    public function __invoke(string $tempToken, ?string $code, ?string $recoveryCode): ?array
    {
        $userId = $this->twoFactorService->validateTempToken($tempToken);

        if ($userId === null) {
            return null;
        }

        $user = User::find($userId);

        if ($user === null) {
            return null;
        }

        if ($code !== null) {
            if (! $this->twoFactorService->verifyCode($user, $code)) {
                return null;
            }
        } elseif ($recoveryCode !== null) {
            if (! $this->twoFactorService->verifyRecoveryCode($user, $recoveryCode)) {
                return null;
            }
        } else {
            return null;
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        // Mark re-authentication as satisfied (2FA login counts as recent auth)
        $this->reauthenticationService->markAuthenticated($user);

        $this->activityLogger->log('auth.login', $user);

        return ['user' => $user, 'token' => $token];
    }
}
