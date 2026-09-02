<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Permission;
use App\Events\AccessGranted;
use App\Models\AccessGrant;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AccessResolver;
use App\Services\TenantManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GrantAccessAction
{
    public function __construct(
        private readonly AccessResolver $accessResolver,
        private readonly TenantManager $tenantManager,
    ) {}

    /**
     * Grant access to a resource for a subject (User, Team, or Tenant).
     *
     * If an active grant already exists for the same subject + resource,
     * it is updated instead of creating a duplicate.
     *
     * @param  string  $subjectType  Fully qualified class name (User::class, Team::class, Tenant::class)
     */
    public function __invoke(
        User $grantedBy,
        Model $resource,
        string $subjectType,
        int $subjectId,
        Permission $permission,
        ?Carbon $expiresAt = null,
        ?int $maxViews = null,
        bool $startOnFirstView = false,
        ?Carbon $startsAt = null,
    ): AccessGrant {
        // Verify granter has share permission
        if (! $this->accessResolver->can($grantedBy, Permission::Share, $resource)) {
            abort(403, 'You do not have permission to share this resource.');
        }

        $tenantId = $this->tenantManager->currentTenantId();

        // Wrap the lookup + create/update in a transaction with a row lock
        // to prevent two concurrent grants for the same subject + resource
        // from creating duplicate AccessGrant rows.
        return DB::transaction(function () use ($grantedBy, $resource, $subjectType, $subjectId, $permission, $expiresAt, $maxViews, $startOnFirstView, $startsAt, $tenantId): AccessGrant {
            // Check for existing active grant with a pessimistic lock
            $existing = AccessGrant::withoutTenant()
                ->where('tenant_id', $tenantId)
                ->where('grantable_type', $resource::class)
                ->where('grantable_id', $resource->getKey())
                ->where('subject_type', $subjectType)
                ->where('subject_id', $subjectId)
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                // Update existing grant
                $existing->update([
                    'permission' => $permission,
                    'expires_at' => $expiresAt,
                    'max_views' => $maxViews,
                    'start_on_first_view' => $startOnFirstView,
                    'starts_at' => $startsAt,
                    'granted_by' => $grantedBy->id,
                ]);
                $existing->refresh();

                AccessGranted::dispatch($existing, $grantedBy);

                return $existing;
            }

            $grant = AccessGrant::create([
                'tenant_id' => $tenantId,
                'grantable_type' => $resource::class,
                'grantable_id' => $resource->getKey(),
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'permission' => $permission,
                'expires_at' => $expiresAt,
                'max_views' => $maxViews,
                'views_count' => 0,
                'starts_at' => $startsAt,
                'start_on_first_view' => $startOnFirstView,
                'first_viewed_at' => null,
                'granted_by' => $grantedBy->id,
                'revoked_at' => null,
                'revoked_by' => null,
                'revoke_reason' => null,
            ]);

            AccessGranted::dispatch($grant, $grantedBy);

            return $grant;
        });
    }
}
