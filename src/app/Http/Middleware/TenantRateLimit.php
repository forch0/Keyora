<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per-tenant rate limiting. Uses tenant ID + user ID as the key.
 *
 * Higher plan tiers get higher limits via a multiplier from config/plans.php.
 * Falls back to the base profile limit if no tenant context is set.
 *
 * Usage: ->middleware('tenant.rate:read')
 */
class TenantRateLimit
{
    public function __construct(
        private readonly TenantManager $tenantManager,
    ) {}

    /**
     * @param  string  $profile  The rate limit profile (read, write, sensitive)
     */
    public function handle(Request $request, Closure $next, string $profile): Response
    {
        $config = config("rate_limits.{$profile}");

        if (! is_array($config) || ! isset($config['limit'])) {
            return $next($request);
        }

        $baseLimit = (int) $config['limit'];
        $window = (int) $config['window'];
        $multiplier = $this->planMultiplier();
        $limit = (int) ceil($baseLimit * $multiplier);

        $key = $this->resolveKey($request);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $retryAfter = (int) RateLimiter::availableIn($key);

            return $this->rateLimitResponse($limit, $retryAfter);
        }

        RateLimiter::hit($key, $window * 60);

        $response = $next($request);

        $remaining = (int) ($limit - RateLimiter::attempts($key));

        return $this->addRateLimitHeaders($response, $limit, max(0, $remaining));
    }

    /**
     * Get the rate limit multiplier based on the current tenant's plan.
     *
     * Higher tiers get higher limits:
     * - free: 1x (base)
     * - team: 2x
     * - business: 3x
     * - enterprise: 5x
     */
    private function planMultiplier(): float
    {
        $tenantId = $this->tenantManager->currentTenantId();

        if ($tenantId === null) {
            return 1.0;
        }

        $tenant = Tenant::find($tenantId);

        if ($tenant === null) {
            return 1.0;
        }

        return match ($tenant->plan) {
            'team' => 2.0,
            'business' => 3.0,
            'enterprise' => 5.0,
            default => 1.0,
        };
    }

    /**
     * Build the rate limit key using tenant ID + user ID.
     */
    private function resolveKey(Request $request): string
    {
        $tenantId = $this->tenantManager->currentTenantId();
        $user = $request->user();
        $userId = $user !== null ? $user->id : $request->ip();

        if ($tenantId !== null) {
            return "trl:{$tenantId}:{$userId}";
        }

        return "trl:global:{$userId}";
    }

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
