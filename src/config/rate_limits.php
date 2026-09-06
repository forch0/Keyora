<?php

declare(strict_types=1);

/**
 * Rate limit profiles for Keyora API endpoints.
 *
 * Used by the RateLimitByProfile middleware. Each profile defines
 * a limit (max requests) and window (in minutes).
 *
 * Per-tenant limits can override these based on plan tier — see
 * TenantRateLimit middleware.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Read Profile
    |--------------------------------------------------------------------------
    | Applied to all GET endpoints. Higher limit since reads are cheap.
    */
    'read' => [
        'limit' => 60,
        'window' => 1, // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Write Profile
    |--------------------------------------------------------------------------
    | Applied to POST/PUT/PATCH/DELETE endpoints on resources.
    */
    'write' => [
        'limit' => 30,
        'window' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Profile
    |--------------------------------------------------------------------------
    | Applied to highly sensitive actions: offboarding, revoke-all,
    | 2FA operations, password change, secure link creation, vault deletion.
    */
    'sensitive' => [
        'limit' => 10,
        'window' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auth Profile
    |--------------------------------------------------------------------------
    | Public auth endpoints — rate limited by IP, not user ID.
    */
    'auth' => [
        'login' => ['limit' => 5, 'window' => 1],
        'register' => ['limit' => 5, 'window' => 1],
        'forgot_password' => ['limit' => 3, 'window' => 1],
    ],

    /*
    |--------------------------------------------------------------------------
    | 2FA Profile
    |--------------------------------------------------------------------------
    | 2FA verification — rate limited by IP (pre-auth).
    */
    '2fa' => [
        'verify' => ['limit' => 5, 'window' => 1],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Alert Threshold
    |--------------------------------------------------------------------------
    | If a user hits the sensitive rate limit this many times within
    | the window, a security alert is created.
    */
    'alert_threshold' => 3,
    'alert_window' => 5, // minutes

];
