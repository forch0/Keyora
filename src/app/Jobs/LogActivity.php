<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ActivityLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Asynchronously writes an ActivityLog record to the audit queue.
 *
 * @param  array<string, mixed>|null  $properties
 */
class LogActivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>|null  $properties
     */
    public function __construct(
        public readonly string $action,
        public readonly ?int $tenantId,
        public readonly ?int $userId,
        public readonly ?string $subjectType,
        public readonly ?int $subjectId,
        public readonly ?array $properties,
        public readonly ?string $ipAddress,
        public readonly ?string $userAgent,
    ) {
        $this->onQueue('audit');
    }

    public function handle(): void
    {
        ActivityLog::create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'action' => $this->action,
            'subject_type' => $this->subjectType,
            'subject_id' => $this->subjectId,
            'properties' => $this->properties,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'created_at' => Carbon::now(),
        ]);
    }
}
