<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\ConfirmTwoFactorAction;
use App\Actions\DisableTwoFactorAction;
use App\Actions\EnableTwoFactorAction;
use App\Actions\RegenerateRecoveryCodesAction;
use App\Actions\VerifyTwoFactorAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmTwoFactorRequest;
use App\Http\Requests\Auth\DisableTwoFactorRequest;
use App\Http\Requests\Auth\VerifyTwoFactorRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Services\ReauthenticationService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Two-Factor Authentication')]
class TwoFactorController extends Controller
{
    public function __construct(
        private readonly EnableTwoFactorAction $enableTwoFactor,
        private readonly ConfirmTwoFactorAction $confirmTwoFactor,
        private readonly DisableTwoFactorAction $disableTwoFactor,
        private readonly RegenerateRecoveryCodesAction $regenerateRecoveryCodes,
        private readonly VerifyTwoFactorAction $verifyTwoFactor,
        private readonly ReauthenticationService $reauthenticationService,
    ) {}

    /**
     * Generate 2FA secret and QR code.
     */
    public function enable(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $result = ($this->enableTwoFactor)($user);

        return response()->json(['data' => $result]);
    }

    /**
     * Confirm 2FA with first TOTP code and generate recovery codes.
     */
    public function confirm(ConfirmTwoFactorRequest $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $result = ($this->confirmTwoFactor)($user, $request->validated('code'));

        if ($result === null) {
            return response()->json([
                'error' => [
                    'code' => 'TWO_FACTOR_INVALID_CODE',
                    'message' => 'Invalid verification code.',
                ],
            ], 422);
        }

        return response()->json([
            'data' => [
                'recovery_codes' => $result['recovery_codes'],
                'message' => 'Two-factor authentication enabled. Save your recovery codes — they will only be shown once.',
            ],
        ]);
    }

    /**
     * Disable 2FA (requires current password).
     */
    public function disable(DisableTwoFactorRequest $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $disabled = ($this->disableTwoFactor)($user, $request->validated('password'));

        if (! $disabled) {
            return response()->json([
                'error' => [
                    'code' => 'AUTH_INVALID_PASSWORD',
                    'message' => 'Current password is incorrect.',
                ],
            ], 422);
        }

        return response()->json(null, 204);
    }

    /**
     * Get new recovery codes (requires re-authentication).
     */
    public function recoveryCodes(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if (! $this->reauthenticationService->isAuthenticated($user)) {
            return response()->json([
                'error' => [
                    'code' => 'REAUTH_REQUIRED',
                    'message' => 'Re-authentication required to regenerate recovery codes.',
                ],
            ], 423);
        }

        $codes = ($this->regenerateRecoveryCodes)($user);

        return response()->json([
            'data' => [
                'recovery_codes' => $codes,
                'message' => 'New recovery codes generated. Save them — they will only be shown once.',
            ],
        ]);
    }

    /**
     * Verify 2FA code during login.
     */
    public function verify(VerifyTwoFactorRequest $request): JsonResponse
    {
        $result = ($this->verifyTwoFactor)(
            $request->validated('2fa_token'),
            $request->validated('code'),
            $request->validated('recovery_code'),
        );

        if ($result === null) {
            return response()->json([
                'error' => [
                    'code' => 'TWO_FACTOR_INVALID',
                    'message' => 'Invalid 2FA token, code, or recovery code.',
                ],
            ], 422);
        }

        return (new UserResource($result['user']))
            ->additional(['token' => $result['token']])
            ->response()
            ->setStatusCode(200);
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Unauthenticated.');
        }

        return $user;
    }
}
