<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Tenant;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Carbon;

class CompleteOnboardingAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * Mark onboarding as complete for a user in a tenant.
     */
    public function __invoke(User $user, Tenant $tenant): void
    {
        $user->tenants()->updateExistingPivot($tenant->id, [
            'onboarding_completed_at' => Carbon::now(),
        ]);

        $this->activityLogger->log('member.onboarded', $user, $tenant);
    }
}
