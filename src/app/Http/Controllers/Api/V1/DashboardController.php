<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\TenantManager;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

#[Group('Dashboards')]
class DashboardController extends Controller
{
    private const CACHE_TTL = 60;

    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly TenantManager $tenantManager,
    ) {}

    /**
     * Personal dashboard — available to any authenticated user.
     */
    public function personal(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $cacheKey = "dashboard:personal:{$user->id}";
        $cacheStatus = Cache::has($cacheKey) ? 'HIT' : 'MISS';

        $data = $this->dashboardService->personalDashboard($user);

        return response()->json(['data' => $data])
            ->header('X-Cache-Status', $cacheStatus)
            ->header('X-Cache-TTL', (string) self::CACHE_TTL);
    }

    /**
     * Company dashboard — admin/owner only.
     */
    public function company(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $tenant = $this->resolveTenant();

        $this->authorizeAdmin($user, $tenant);

        $cacheKey = "dashboard:company:{$tenant->id}";
        $cacheStatus = Cache::has($cacheKey) ? 'HIT' : 'MISS';

        $data = $this->dashboardService->companyDashboard($tenant);

        return response()->json(['data' => $data])
            ->header('X-Cache-Status', $cacheStatus)
            ->header('X-Cache-TTL', (string) self::CACHE_TTL);
    }

    /**
     * Usage dashboard — admin/owner only.
     */
    public function usage(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $tenant = $this->resolveTenant();

        $this->authorizeAdmin($user, $tenant);

        $cacheKey = "dashboard:usage:{$tenant->id}";
        $cacheStatus = Cache::has($cacheKey) ? 'HIT' : 'MISS';

        $data = $this->dashboardService->usageDashboard($tenant);

        return response()->json(['data' => $data])
            ->header('X-Cache-Status', $cacheStatus)
            ->header('X-Cache-TTL', (string) self::CACHE_TTL);
    }

    private function resolveTenant(): Tenant
    {
        $tenantId = $this->tenantManager->currentTenantId();

        if ($tenantId === null) {
            abort(400, 'No tenant context resolved. Provide X-Tenant-ID header.');
        }

        $tenant = Tenant::find($tenantId);

        if ($tenant === null) {
            abort(404, 'Tenant not found.');
        }

        return $tenant;
    }

    private function authorizeAdmin(User $user, Tenant $tenant): void
    {
        if (! $user->isAdminOf($tenant)) {
            abort(403, 'Admin or owner access required.');
        }
    }
}
