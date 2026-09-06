<?php

declare(strict_types=1);

namespace App\Actions;

use App\Events\EmployeeOffboarded;
use App\Models\SecureLink;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\EmployeeOffboardedNotification;
use App\Services\ActivityLogger;
use App\Services\DashboardCacheService;
use Illuminate\Support\Carbon;

class OffboardEmployeeAction
{
    public function __construct(
        private readonly EmergencyRevokeAction $emergencyRevoke,
        private readonly ActivityLogger $activityLogger,
        private readonly DashboardCacheService $dashboardCacheService,
    ) {}

    /**
     * Offboard an employee — revoke all access, remove from teams,
     * deactivate tenant membership, revoke tokens and secure links.
     *
     * @return int Count of revoked access grants
     */
    public function __invoke(User $offboardedBy, User $targetUser, Tenant $tenant, ?string $reason = null): int
    {
        $now = Carbon::now();

        // 1. Revoke all access grants
        $count = ($this->emergencyRevoke)($offboardedBy, $targetUser, 'offboarding');

        // 2. Remove from all teams in this tenant
        $teamIds = $targetUser->teams()
            ->where('teams.tenant_id', $tenant->id)
            ->pluck('teams.id');
        if ($teamIds->isNotEmpty()) {
            $targetUser->teams()->detach($teamIds->toArray());
        }

        // 3. Set tenant_user status to 'left', set left_at
        $targetUser->tenants()->updateExistingPivot($tenant->id, [
            'status' => 'left',
            'left_at' => $now,
        ]);

        // 4. Revoke all API tokens for this tenant
        $targetUser->tokens()
            ->where('tenant_id', $tenant->id)
            ->delete();

        // 5. Revoke all secure links created by user in this tenant
        SecureLink::where('created_by', $targetUser->id)
            ->where('tenant_id', $tenant->id)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => $now,
                'revoke_reason' => 'offboarding',
            ]);

        // 6. Dispatch event
        EmployeeOffboarded::dispatch($targetUser, $offboardedBy, $tenant, $reason);

        // 7. Invalidate dashboard caches — offboarding updates the tenant_user
        // pivot, which doesn't trigger model observers.
        $this->dashboardCacheService->invalidateCompanyDashboard($tenant);
        $this->dashboardCacheService->invalidateUsageDashboard($tenant);
        $this->dashboardCacheService->invalidatePersonalDashboard($targetUser);

        // 8. Notify the offboarded user
        $targetUser->notify(new EmployeeOffboardedNotification($tenant, $reason));

        // 9. Log activity
        $this->activityLogger->log('member.offboarded', $offboardedBy, $tenant, [
            'offboarded_user_id' => $targetUser->id,
            'offboarded_user_name' => $targetUser->name,
            'reason' => $reason,
        ]);

        return $count;
    }
}
