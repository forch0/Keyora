<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\TenantManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
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

        $data = $this->dashboardService->personalDashboard($user);

        return response()->json(['data' => $data]);
    }

    /**
     * Company dashboard — admin/owner only.
     */
    public function company(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $tenant = $this->resolveTenant();

        $this->authorizeAdmin($user, $tenant);

        $data = $this->dashboardService->companyDashboard($tenant);

        return response()->json(['data' => $data]);
    }

    /**
     * Usage dashboard — admin/owner only.
     */
    public function usage(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $tenant = $this->resolveTenant();

        $this->authorizeAdmin($user, $tenant);

        $data = $this->dashboardService->usageDashboard($tenant);

        return response()->json(['data' => $data]);
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Unauthenticated.');
        }

        return $user;
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
