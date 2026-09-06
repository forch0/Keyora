<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * Handles re-authentication for sensitive actions.
 *
 * Tracks the last re-authentication timestamp per user in cache.
 * Sensitive actions can check whether the user has recently re-authenticated.
 */
class ReauthenticationService
{
    private const REAUTH_TTL_SECONDS = 900; // 15 minutes

    private const CACHE_PREFIX = 'reauth:';

    /**
     * Check if the user has recently re-authenticated.
     */
    public function isAuthenticated(User $user): bool
    {
        $timestamp = Cache::get(self::CACHE_PREFIX.$user->id);

        if ($timestamp === null) {
            return false;
        }

        $timestampInt = (int) $timestamp;
        $nowTimestamp = (int) now()->timestamp;

        return $nowTimestamp - $timestampInt < self::REAUTH_TTL_SECONDS;
    }

    /**
     * Re-authenticate the user with their password.
     *
     * @return bool True if password is correct and re-auth timestamp is set.
     */
    public function reauthenticate(User $user, string $password): bool
    {
        if (! Hash::check($password, $user->password)) {
            return false;
        }

        $this->markAuthenticated($user);

        return true;
    }

    /**
     * Mark the user as recently re-authenticated.
     */
    public function markAuthenticated(User $user): void
    {
        Cache::put(self::CACHE_PREFIX.$user->id, now()->timestamp, now()->addSeconds(self::REAUTH_TTL_SECONDS));
    }
}
