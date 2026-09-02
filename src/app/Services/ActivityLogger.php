<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\LogActivity;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Http\Request;

class ActivityLogger
{
    public function __construct(
        private readonly TenantManager $tenantManager,
        private readonly ?Request $request = null,
    ) {}

    /**
     * Log an activity synchronously (for tests / immediate writes).
     *
     * @param  array<string, mixed>  $properties
     */
    public function log(
        string $action,
        ?User $user = null,
        ?EloquentModel $subject = null,
        array $properties = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ActivityLog {
        $tenantId = $this->tenantManager->currentTenantId();

        if ($ipAddress === null && $this->request !== null) {
            $ipAddress = $this->request->ip();
        }

        if ($userAgent === null && $this->request !== null) {
            $userAgent = $this->request->userAgent();
        }

        return ActivityLog::create([
            'tenant_id' => $tenantId,
            'user_id' => $user?->id,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }

    /**
     * Dispatch activity logging to the audit queue (async).
     *
     * @param  array<string, mixed>  $properties
     */
    public function dispatch(
        string $action,
        ?User $user = null,
        ?EloquentModel $subject = null,
        array $properties = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): void {
        $tenantId = $this->tenantManager->currentTenantId();

        if ($ipAddress === null && $this->request !== null) {
            $ipAddress = $this->request->ip();
        }

        if ($userAgent === null && $this->request !== null) {
            $userAgent = $this->request->userAgent();
        }

        LogActivity::dispatch(
            $action,
            $tenantId,
            $user?->id,
            $subject?->getMorphClass(),
            $subject?->getKey(),
            $properties ?: null,
            $ipAddress,
            $userAgent,
        );
    }
}
