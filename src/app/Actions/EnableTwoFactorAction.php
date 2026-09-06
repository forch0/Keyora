<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Services\TwoFactorService;

class EnableTwoFactorAction
{
    public function __construct(
        private readonly TwoFactorService $twoFactorService,
    ) {}

    /**
     * Generate 2FA secret and QR code for the user.
     *
     * @return array{secret: string, qr_code_uri: string}
     */
    public function __invoke(User $user): array
    {
        $secret = $this->twoFactorService->generateSecret();
        $this->twoFactorService->storeSecret($user, $secret);

        return [
            'secret' => $secret,
            'qr_code_uri' => $this->twoFactorService->getQrCodeUri($user, $secret),
        ];
    }
}
