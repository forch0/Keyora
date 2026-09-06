<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\SecurityAlert;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate limit middleware that reads limits from config/rate_limits.php.
 *
 * Usage in routes: ->middleware('rate.limit:read')
 * Profiles: read, write, sensitive, auth.login, auth.register,
 *           auth.forgot_password, 2fa.verify
 *
 * Auth and 2FA profiles are keyed by IP address (pre-authentication).
 * Other profiles are keyed by user ID (authenticated).
 */
class RateLimitByProfile
{
    /**
     * @param  string  $profile  The rate limit profile (e.g., "read", "write", "sensitive", "auth.login")
     */
    public function handle(Request $request, Closure $next, string $profile): Response
    {
        $config = $this->resolveProfileConfig($profile);

        if ($config === null) {
            return $next($request);
        }

        $limit = (int) $config['limit'];
        $window = (int) $config['window'];
        $key = $this->resolveKey($request, $profile);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $retryAfter = (int) RateLimiter::availableIn($key);

            $this->handleRepeatedViolations($request, $profile);

            return $this->rateLimitResponse($limit, $retryAfter);
        }

        RateLimiter::hit($key, $window * 60);

        $response = $next($request);

        $remaining = (int) ($limit - RateLimiter::attempts($key));

        return $this->addRateLimitHeaders($response, $limit, max(0, $remaining));
    }

    /**
     * Resolve the rate limit configuration for a given profile.
     *
     * @return array<string, int>|null
     */
    private function resolveProfileConfig(string $profile): ?array
    {
        // Direct profiles: read, write, sensitive
        $direct = config("rate_limits.{$profile}");

        if (is_array($direct) && isset($direct['limit'])) {
            return $direct;
        }

        // Nested profiles: auth.login, auth.register, auth.forgot_password, 2fa.verify
        $parts = explode('.', $profile);

        if (count($parts) === 2) {
            $nested = config("rate_limits.{$parts[0]}.{$parts[1]}");

            if (is_array($nested) && isset($nested['limit'])) {
                return $nested;
            }
        }

        return null;
    }

    /**
     * Build the rate limit key. Auth/2FA profiles use IP; others use user ID.
     */
    private function resolveKey(Request $request, string $profile): string
    {
        $isIpBased = str_starts_with($profile, 'auth.') || str_starts_with($profile, '2fa.');

        if ($isIpBased) {
            return "rl:{$profile}:{$request->ip()}";
        }

        $user = $request->user();
        $userId = $user !== null ? $user->id : $request->ip();

        return "rl:{$profile}:{$userId}";
    }

    /**
     * Check for repeated violations on sensitive endpoints and create
     * a security alert if the threshold is exceeded.
     */
    private function handleRepeatedViolations(Request $request, string $profile): void
    {
        if ($profile !== 'sensitive') {
            return;
        }

        $userId = $request->user()?->id;

        if ($userId === null) {
            return;
        }

        $threshold = (int) config('rate_limits.alert_threshold', 3);
        $window = (int) config('rate_limits.alert_window', 5);
        $violationKey = "rl:violations:{$userId}";

        RateLimiter::hit($violationKey, $window * 60);

        if (RateLimiter::attempts($violationKey) >= $threshold) {
            SecurityAlert::create([
                'user_id' => $userId,
                'type' => SecurityAlert::TYPE_SUSPICIOUS_ACTIVITY,
                'severity' => SecurityAlert::SEVERITY_WARNING,
                'title' => 'Repeated rate limit violations',
                'message' => 'User repeatedly exceeded the sensitive action rate limit within 5 minutes.',
                'properties' => [
                    'profile' => $profile,
                    'ip' => $request->ip(),
                    'violations' => RateLimiter::attempts($violationKey),
                ],
            ]);

            RateLimiter::clear($violationKey);
        }
    }

    /**
     * Build the 429 Too Many Requests response.
     */
    private function rateLimitResponse(int $limit, int $retryAfter): Response
    {
        $response = response()->json([
            'error' => [
                'code' => 'RATE_LIMIT_EXCEEDED',
                'message' => "Too many requests. Please retry after {$retryAfter} seconds.",
                'retry_after' => $retryAfter,
            ],
        ], 429);

        return $this->addRateLimitHeaders($response, $limit, 0, $retryAfter);
    }

    /**
     * Add rate limit headers to a response.
     */
    private function addRateLimitHeaders(Response $response, int $limit, int $remaining, ?int $retryAfter = null): Response
    {
        $response->headers->set('X-RateLimit-Limit', (string) $limit);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining);

        if ($retryAfter !== null && $retryAfter > 0) {
            $response->headers->set('Retry-After', (string) $retryAfter);
        }

        return $response;
    }
}
