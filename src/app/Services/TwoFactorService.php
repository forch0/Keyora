<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

/**
 * Two-factor authentication service implementing TOTP (RFC 6238) and recovery codes.
 *
 * TOTP is implemented natively without external packages.
 */
class TwoFactorService
{
    private const TOTP_PERIOD = 30;

    private const TOTP_DIGITS = 6;

    private const RECOVERY_CODE_COUNT = 8;

    /**
     * Generate a new TOTP secret (base32 encoded).
     */
    public function generateSecret(): string
    {
        $bytes = random_bytes(20);

        return $this->base32Encode($bytes);
    }

    /**
     * Get the QR code URI for authenticator apps.
     */
    public function getQrCodeUri(User $user, string $secret): string
    {
        $issuer = rawurlencode(config('app.name', 'Zekura'));
        $label = rawurlencode($user->email);

        return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&digits=".self::TOTP_DIGITS.'&period='.self::TOTP_PERIOD;
    }

    /**
     * Verify a TOTP code against the user's secret.
     */
    public function verifyCode(User $user, string $code): bool
    {
        $secret = $this->getDecryptedSecret($user);

        if ($secret === null) {
            return false;
        }

        // Check current and adjacent time windows to allow for clock drift
        $timestamp = time();
        for ($offset = -1; $offset <= 1; $offset++) {
            $window = intdiv($timestamp, self::TOTP_PERIOD) + $offset;
            $expectedCode = $this->generateTotp($secret, $window);

            if (hash_equals($expectedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate 8 one-time recovery codes (format: XXXXX-XXXXX).
     *
     * @return array<int, string>
     */
    public function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            $codes[] = $this->generateRecoveryCode();
        }

        return $codes;
    }

    /**
     * Verify and consume a recovery code.
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $codes = $this->getDecryptedRecoveryCodes($user);

        if ($codes === null) {
            return false;
        }

        foreach ($codes as $index => $hashedCode) {
            if (Hash::check($code, $hashedCode)) {
                // Remove the used code
                unset($codes[$index]);
                $this->storeRecoveryCodes($user, array_values($codes));

                return true;
            }
        }

        return false;
    }

    /**
     * Store the 2FA secret (encrypted) on the user.
     */
    public function storeSecret(User $user, string $secret): void
    {
        $user->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
        ])->save();
    }

    /**
     * Store recovery codes (each hashed with bcrypt, then encrypted) on the user.
     *
     * @param  array<int, string>  $codes
     */
    public function storeRecoveryCodes(User $user, array $codes): void
    {
        $hashed = array_map(fn (string $code): string => Hash::make($code), $codes);
        $json = json_encode($hashed);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode recovery codes.');
        }

        $user->forceFill([
            'two_factor_recovery_codes' => Crypt::encryptString($json),
        ])->save();
    }

    /**
     * Get the decrypted 2FA secret.
     */
    public function getDecryptedSecret(User $user): ?string
    {
        if ($user->two_factor_secret === null) {
            return null;
        }

        try {
            return Crypt::decryptString($user->two_factor_secret);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Get the decrypted recovery codes (array of bcrypt hashes).
     *
     * @return array<int, string>|null
     */
    public function getDecryptedRecoveryCodes(User $user): ?array
    {
        if ($user->two_factor_recovery_codes === null) {
            return null;
        }

        try {
            $decrypted = Crypt::decryptString($user->two_factor_recovery_codes);

            /** @var array<int, string>|null $codes */
            $codes = json_decode($decrypted, true);

            return is_array($codes) ? $codes : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Check if 2FA is enabled and confirmed for the user.
     */
    public function isEnabled(User $user): bool
    {
        return $user->two_factor_secret !== null
            && $user->two_factor_confirmed_at !== null;
    }

    /**
     * Disable 2FA for the user.
     */
    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    /**
     * Store a temporary 2FA login token (for the login flow).
     */
    public function storeTempToken(User $user): string
    {
        $token = bin2hex(random_bytes(32));
        Cache::put("2fa:{$token}", $user->id, now()->addMinutes(10));

        return $token;
    }

    /**
     * Validate a temporary 2FA login token and return the user ID.
     */
    public function validateTempToken(string $token): ?int
    {
        $userId = Cache::get("2fa:{$token}");

        if ($userId !== null) {
            Cache::forget("2fa:{$token}");
        }

        return $userId !== null ? (int) $userId : null;
    }

    /**
     * Generate a TOTP code for the given secret and time window.
     */
    private function generateTotp(string $secret, int $window): string
    {
        $key = $this->base32Decode($secret);
        $time = pack('N', 0).pack('N', $window);
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** self::TOTP_DIGITS);

        return str_pad((string) $code, self::TOTP_DIGITS, '0', STR_PAD_LEFT);
    }

    private function generateRecoveryCode(): string
    {
        $part1 = strtoupper(bin2hex(random_bytes(3)));
        $part2 = strtoupper(bin2hex(random_bytes(3)));

        return "{$part1}-{$part2}";
    }

    /**
     * Base32 encode (RFC 4648).
     */
    private function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $result = '';
        $bits = 0;
        $value = 0;

        for ($i = 0, $len = strlen($data); $i < $len; $i++) {
            $value = ($value << 8) | ord($data[$i]);
            $bits += 8;

            while ($bits >= 5) {
                $result .= $alphabet[($value >> ($bits - 5)) & 0x1F];
                $bits -= 5;
            }
        }

        if ($bits > 0) {
            $result .= $alphabet[($value << (5 - $bits)) & 0x1F];
        }

        return $result;
    }

    /**
     * Base32 decode (RFC 4648).
     */
    private function base32Decode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $result = '';
        $bits = 0;
        $value = 0;

        for ($i = 0, $len = strlen($data); $i < $len; $i++) {
            $char = strtoupper($data[$i]);
            if ($char === '=') {
                break;
            }

            $pos = strpos($alphabet, $char);
            if ($pos === false) {
                continue;
            }

            $value = ($value << 5) | $pos;
            $bits += 5;

            if ($bits >= 8) {
                $result .= chr(($value >> ($bits - 8)) & 0xFF);
                $bits -= 8;
            }
        }

        return $result;
    }
}
