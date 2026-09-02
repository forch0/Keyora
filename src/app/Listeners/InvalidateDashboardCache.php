<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AccessGranted;
use App\Events\AccessRequestApproved;
use App\Events\AccessRequested;
use App\Events\AccessRequestRejected;
use App\Events\AccessRevoked;
use App\Services\DashboardCacheService;
use Illuminate\Events\Dispatcher;

class InvalidateDashboardCache
{
    public function __construct(
        private readonly DashboardCacheService $cacheService,
    ) {}

    public function handleAccessGranted(AccessGranted $event): void
    {
        $grant = $event->grant;
        $this->cacheService->invalidateTenantDashboardsById((int) $grant->tenant_id);

        if ($grant->subject_type === 'App\\Models\\User') {
            $this->cacheService->invalidatePersonalDashboardById((int) $grant->subject_id);
        }
    }

    public function handleAccessRevoked(AccessRevoked $event): void
    {
        $grant = $event->grant;
        $this->cacheService->invalidateTenantDashboardsById((int) $grant->tenant_id);

        if ($grant->subject_type === 'App\\Models\\User') {
            $this->cacheService->invalidatePersonalDashboardById((int) $grant->subject_id);
        }
    }

    public function handleAccessRequested(AccessRequested $event): void
    {
        $request = $event->request;
        $this->cacheService->invalidateTenantDashboardsById((int) $request->tenant_id);
        $this->cacheService->invalidatePersonalDashboardById((int) $request->requester_id);
    }

    public function handleAccessRequestApproved(AccessRequestApproved $event): void
    {
        $request = $event->request;
        $this->cacheService->invalidateTenantDashboardsById((int) $request->tenant_id);
        $this->cacheService->invalidatePersonalDashboardById((int) $request->requester_id);
    }

    public function handleAccessRequestRejected(AccessRequestRejected $event): void
    {
        $request = $event->request;
        $this->cacheService->invalidateTenantDashboardsById((int) $request->tenant_id);
        $this->cacheService->invalidatePersonalDashboardById((int) $request->requester_id);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            AccessGranted::class => 'handleAccessGranted',
            AccessRevoked::class => 'handleAccessRevoked',
            AccessRequested::class => 'handleAccessRequested',
            AccessRequestApproved::class => 'handleAccessRequestApproved',
            AccessRequestRejected::class => 'handleAccessRequestRejected',
        ];
    }
}
