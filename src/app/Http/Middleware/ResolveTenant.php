<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\TenantManager;
use Closure;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(private readonly TenantManager $tenantManager) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            // Priority 1: Token's associated tenant (if token has tenant_id)
            /** @var PersonalAccessToken|null $token */
            $token = $user->currentAccessToken();
            if ($token !== null && $token->tenant_id !== null) {
                $tenantId = (int) $token->tenant_id;
                $this->ensureActiveMember($user, $tenantId);
                $this->tenantManager->setCurrentTenant($tenantId);

                return $next($request);
            }

            // Priority 2: X-Tenant-ID header (verify user belongs to tenant)
            $tenantId = $request->header('X-Tenant-ID');
            if ($tenantId !== null && $tenantId !== '') {
                $tenantId = (int) $tenantId;

                $this->ensureActiveMember($user, $tenantId);
                $this->tenantManager->setCurrentTenant($tenantId);
            }
        }

        // If no tenant resolved, continue without — some endpoints are tenant-agnostic
        return $next($request);
    }

    /**
     * Verify the user is an active, non-suspended member of the tenant.
     * Left members get 403; suspended members get 403 with a specific message.
     */
    private function ensureActiveMember(User $user, int $tenantId): void
    {
        $membership = $user->tenants()
            ->where('tenants.id', $tenantId)
            ->whereNull('tenant_user.left_at')
            ->first();

        if ($membership === null) {
            abort(403, 'You do not belong to this workspace.');
        }

        $pivot = $membership->getRelation('pivot');
        $status = $pivot instanceof Pivot
            ? $pivot->getAttribute('status')
            : null;

        if ($status === 'suspended') {
            abort(403, 'Your access to this workspace has been suspended.');
        }
    }
}
