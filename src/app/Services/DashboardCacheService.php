<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class DashboardCacheService
{
    /**
     * Invalidate the personal dashboard cache for a user.
     */
    public function invalidatePersonalDashboard(User $user): void
    {
        Cache::forget("dashboard:personal:{$user->id}");
    }

    /**
     * Invalidate the company dashboard cache for a tenant.
     */
    public function invalidateCompanyDashboard(Tenant $tenant): void
    {
        Cache::forget("dashboard:company:{$tenant->id}");
    }

    /**
     * Invalidate the usage dashboard cache for a tenant.
     */
    public function invalidateUsageDashboard(Tenant $tenant): void
    {
        Cache::forget("dashboard:usage:{$tenant->id}");
    }

    /**
     * Invalidate all dashboard caches for a tenant.
     */
    public function invalidateAllForTenant(Tenant $tenant): void
    {
        $this->invalidateCompanyDashboard($tenant);
        $this->invalidateUsageDashboard($tenant);

        // Invalidate personal dashboards for all tenant members
        $tenant->users()->each(fn (User $user) => $this->invalidatePersonalDashboard($user));
    }

    /**
     * Invalidate personal dashboard by user ID (without loading the model).
     */
    public function invalidatePersonalDashboardById(int $userId): void
    {
        Cache::forget("dashboard:personal:{$userId}");
    }

    /**
     * Invalidate company and usage dashboards by tenant ID (without loading the model).
     */
    public function invalidateTenantDashboardsById(int $tenantId): void
    {
        Cache::forget("dashboard:company:{$tenantId}");
        Cache::forget("dashboard:usage:{$tenantId}");
    }

    /**
     * Warm dashboard caches for tenants with activity in the last hour.
     */
    public function warmActiveDashboards(): void
    {
        $dashboardService = app(DashboardService::class);

        $activeTenants = Tenant::whereHas('users', function ($query): void {
            $query->where('tenant_user.joined_at', '>=', now()->subHour());
        })->get();

        foreach ($activeTenants as $tenant) {
            $dashboardService->companyDashboard($tenant);
            $dashboardService->usageDashboard($tenant);
        }
    }
}
