<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AccessDuration;
use App\Enums\Permission;
use App\Events\AccessRequestApproved;
use App\Models\AccessRequest;
use App\Models\User;
use Illuminate\Support\Carbon;

class ApproveAccessRequestAction
{
    public function __construct(
        private readonly GrantAccessAction $grantAccess,
    ) {}

    /**
     * @param  array<string, mixed>  $overrides
     */
    public function __invoke(User $approver, AccessRequest $request, array $overrides = []): AccessRequest
    {
        // Resolve the resource
        $resource = $request->resource;

        if ($resource === null) {
            abort(404, 'Resource no longer exists.');
        }

        // Determine granted permission (override or requested)
        $grantedPermission = isset($overrides['granted_permission'])
            ? Permission::from($overrides['granted_permission'])
            : Permission::from($request->requested_permission);

        // Determine expiration (override or requested duration)
        $grantedExpiresAt = null;
        $grantedDuration = $overrides['granted_duration'] ?? $request->requested_duration;

        if ($grantedDuration !== null && $grantedDuration !== 'permanent') {
            // Check if it's a preset duration
            $presetDurations = ['15m', '30m', '1h', '24h'];
            if (in_array($grantedDuration, $presetDurations, true)) {
                $grantedExpiresAt = AccessDuration::from($grantedDuration)->toCarbon();
            } else {
                // Custom durations like 7d, 30d
                $grantedExpiresAt = match ($grantedDuration) {
                    '7d' => Carbon::now()->addDays(7),
                    '30d' => Carbon::now()->addDays(30),
                    default => null,
                };
            }
        }

        // Create the access grant
        $grant = ($this->grantAccess)(
            grantedBy: $approver,
            resource: $resource,
            subjectType: User::class,
            subjectId: $request->requester_id,
            permission: $grantedPermission,
            expiresAt: $grantedExpiresAt,
        );

        // Update the request
        $request->update([
            'status' => 'approved',
            'reviewed_by' => $approver->id,
            'reviewed_at' => Carbon::now(),
            'granted_permission' => $grantedPermission->value,
            'granted_expires_at' => $grantedExpiresAt,
            'review_note' => $overrides['review_note'] ?? null,
        ]);

        $request->refresh();

        AccessRequestApproved::dispatch($request, $grant);

        return $request;
    }
}
