<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\SecureLink;
use App\Services\LinkAccessTokenFactory;
use Illuminate\Support\Facades\Hash;

class ConfirmLinkEmailVerificationAction
{
    public function __construct(
        private readonly LinkAccessTokenFactory $tokenFactory,
    ) {}

    /**
     * Confirm the email verification code and return an access token.
     *
     * @return array{token: string, link: SecureLink}
     */
    public function __invoke(SecureLink $link, string $code): array
    {
        if ($link->otp_code_hash === null) {
            abort(422, 'No verification code was sent.');
        }

        if (! Hash::check($code, $link->otp_code_hash)) {
            abort(422, 'Invalid verification code.');
        }

        // Mark email as verified
        $link->update(['email_verified_at' => now()]);

        // Clear the OTP code (single-use)
        $link->update(['otp_code_hash' => null]);

        $token = ($this->tokenFactory)($link);

        return ['token' => $token, 'link' => $link];
    }
}
