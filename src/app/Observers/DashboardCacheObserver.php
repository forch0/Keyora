<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\PersonalVaultItem;
use App\Models\SecureFile;
use App\Models\SecureNote;
use App\Models\SecurityAlert;
use App\Models\Team;
use App\Services\DashboardCacheService;
use Illuminate\Database\Eloquent\Model;

/**
 * Observes model lifecycle events and invalidates dashboard caches.
 *
 * This observer is registered for models that affect dashboard data:
 * PersonalVaultItem, SecureFile, SecureNote, Team, SecurityAlert.
 */
class DashboardCacheObserver
{
    public function __construct(
        private readonly DashboardCacheService $cacheService,
    ) {}

    /**
     * Handle the "created" event.
     */
    public function created(Model $model): void
    {
        $this->invalidateForModel($model);
    }

    /**
     * Handle the "updated" event.
     */
    public function updated(Model $model): void
    {
        $this->invalidateForModel($model);
    }

    /**
     * Handle the "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->invalidateForModel($model);
    }

    /**
     * Invalidate the appropriate dashboard caches based on the model type.
     */
    private function invalidateForModel(Model $model): void
    {
        if ($model instanceof PersonalVaultItem) {
            $user = $model->user()->first();
            if ($user !== null) {
                $this->cacheService->invalidatePersonalDashboard($user);
            }

            return;
        }

        if ($model instanceof SecurityAlert) {
            $user = $model->user()->first();
            if ($user !== null) {
                $this->cacheService->invalidatePersonalDashboard($user);
            }
            $tenantId = $model->getAttribute('tenant_id');
            if ($tenantId !== null) {
                $this->cacheService->invalidateTenantDashboardsById((int) $tenantId);
            }

            return;
        }

        // Tenant-scoped models: SecureFile, SecureNote, Team
        $tenantId = $model->getAttribute('tenant_id');
        if ($tenantId !== null) {
            $this->cacheService->invalidateTenantDashboardsById((int) $tenantId);
        }
    }
}
