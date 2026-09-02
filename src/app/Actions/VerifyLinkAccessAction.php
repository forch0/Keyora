<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\SecureLink;
use App\Services\LinkAccessTokenFactory;
use Illuminate\Support\Facades\Hash;

class VerifyLinkAccessAction
{
    public function __construct(
        private readonly LinkAccessTokenFactory $tokenFactory,
    ) {}

    /**
     * Verify password and/or OTP for a secure link.
     *
     * @param  array<string, mixed>  $data
     * @return array{token: string, link: SecureLink}
     */
    public function __invoke(SecureLink $link, array $data): array
    {
        if ($link->hasPassword()) {
            $password = $data['password'] ?? null;
            $hash = $link->password_hash;
            if (! is_string($password) || ! is_string($hash) || ! Hash::check($password, $hash)) {
                abort(422, 'Invalid password.');
            }
        }

        if ($link->requiresOtp()) {
            $otp = $data['otp_code'] ?? null;
            $hash = $link->otp_code_hash;
            if (! is_string($otp) || ! is_string($hash) || ! Hash::check($otp, $hash)) {
                abort(422, 'Invalid OTP code.');
            }
        }

        $token = ($this->tokenFactory)($link);

        return ['token' => $token, 'link' => $link];
    }
}
