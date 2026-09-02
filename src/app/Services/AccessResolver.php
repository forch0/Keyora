<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Permission;
use App\Models\AccessGrant;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Resolves access permissions for a user against a polymorphic resource.
 *
 * The resolver checks:
 *  1. Direct ownership (user_id column on the resource)
 *  2. Direct grants (subject = User)
 *  3. Team grants (subject = Team, user is a member)
 *  4. Tenant-wide grants (subject = Tenant, user is a member)
 *
 * Temporal and view-count constraints are enforced on each grant.
 */
class AccessResolver
{
    public function __construct(
        private readonly TenantManager $tenantManager,
    ) {}

    /**
     * Check if a user can perform an action on a resource.
     */
    public function can(User $user, Permission $required, Model $resource): bool
    {
        return $this->getPermission($user, $resource)?->satisfies($required) ?? false;
    }

    /**
     * Get the highest permission a user has on a resource.
     *
     * Returns null if the user has no access.
     */
    public function getPermission(User $user, Model $resource): ?Permission
    {
        // 1. Owner/creator has full access
        if ($this->isOwner($user, $resource)) {
            return Permission::Manage;
        }

        // 2. Gather all active grants for this resource
        $grants = $this->activeGrantsFor($resource);

        if ($grants->isEmpty()) {
            return null;
        }

        // 3. Filter to grants that apply to this user
        $applicableGrants = $grants->filter(fn (AccessGrant $grant) => $this->grantAppliesToUser($grant, $user));

        if ($applicableGrants->isEmpty()) {
            return null;
        }

        // 4. Filter out grants that fail temporal/view constraints
        $validGrants = $applicableGrants->filter(fn (AccessGrant $grant) => $this->grantIsCurrentlyValid($grant));

        if ($validGrants->isEmpty()) {
            return null;
        }

        // 5. Return the highest permission from valid grants
        /** @var Permission|null $highest */
        $highest = null;
        foreach ($validGrants as $grant) {
            /** @var Permission $permission */
            $permission = $grant->permission;
            if ($highest === null || $permission->rank() > $highest->rank()) {
                $highest = $permission;
            }
        }

        return $highest;
    }

    /**
     * Get all active access grants for a resource (who has access).
     *
     * @return Collection<int, AccessGrant>
     */
    public function whoHasAccess(Model $resource): Collection
    {
        return $this->activeGrantsFor($resource)
            ->filter(fn (AccessGrant $grant) => $this->grantIsCurrentlyValid($grant))
            ->values();
    }

    /**
     * Get all resources a user has access to (via direct, team, or tenant grants).
     *
     * @return Collection<int, AccessGrant>
     */
    public function whatDoesUserHaveAccessTo(User $user): Collection
    {
        $teamIds = $user->teams()->pluck('teams.id')->all();
        $tenantId = $this->tenantManager->currentTenantId();

        $subjectConditions = function (Builder $q) use ($user, $teamIds, $tenantId): void {
            $q->where(function (Builder $sub) use ($user): void {
                $sub->where('subject_type', User::class)
                    ->where('subject_id', $user->id);
            });

            if (! empty($teamIds)) {
                $q->orWhere(function (Builder $sub) use ($teamIds): void {
                    $sub->where('subject_type', Team::class)
                        ->whereIn('subject_id', $teamIds);
                });
            }

            if ($tenantId !== null) {
                $q->orWhere(function (Builder $sub) use ($tenantId): void {
                    $sub->where('subject_type', Tenant::class)
                        ->where('subject_id', $tenantId);
                });
            }
        };

        return AccessGrant::withoutTenant()
            ->where('tenant_id', $tenantId)
            ->where($subjectConditions)
            ->active()
            ->get()
            ->filter(fn (AccessGrant $grant) => $this->grantIsCurrentlyValid($grant))
            ->values();
    }

    /**
     * Check if the user is the owner/creator of the resource.
     */
    private function isOwner(User $user, Model $resource): bool
    {
        // Check user_id column (common to VaultItem, PersonalVaultItem, etc.)
        if (array_key_exists('user_id', $resource->getAttributes())) {
            return (int) $resource->getOriginal('user_id') === $user->id;
        }

        // Check created_by column (used by Team, Folder, etc.)
        if (array_key_exists('created_by', $resource->getAttributes())) {
            return (int) $resource->getOriginal('created_by') === $user->id;
        }

        return false;
    }

    /**
     * Get all active (non-revoked, non-expired) grants for a resource.
     *
     * Uses withoutTenant() because the resource may not have tenant_id
     * set in all contexts, and we want to query by grantable_type/id.
     *
     * @return Collection<int, AccessGrant>
     */
    private function activeGrantsFor(Model $resource): Collection
    {
        $tenantId = $this->tenantManager->currentTenantId();

        return AccessGrant::withoutTenant()
            ->where('tenant_id', $tenantId)
            ->where('grantable_type', $resource::class)
            ->where('grantable_id', $resource->getKey())
            ->active()
            ->get();
    }

    /**
     * Check if a grant's subject applies to the given user.
     */
    private function grantAppliesToUser(AccessGrant $grant, User $user): bool
    {
        // Direct user grant
        if ($grant->subject_type === User::class && (int) $grant->subject_id === $user->id) {
            return true;
        }

        // Team grant — user must be a member of the team
        if ($grant->subject_type === Team::class) {
            return $user->teams()
                ->where('teams.id', $grant->subject_id)
                ->exists();
        }

        // Tenant-wide grant — user must be a member of the tenant
        if ($grant->subject_type === Tenant::class) {
            return $user->tenants()
                ->where('tenants.id', $grant->subject_id)
                ->exists();
        }

        return false;
    }

    /**
     * Check if a grant is currently valid (temporal + view-count constraints).
     */
    private function grantIsCurrentlyValid(AccessGrant $grant): bool
    {
        // Revoked
        if ($grant->isRevoked()) {
            return false;
        }

        // Expired
        if ($grant->isExpired()) {
            return false;
        }

        // View limit reached
        if ($grant->viewLimitReached()) {
            return false;
        }

        // Start time hasn't arrived
        if (! $grant->hasStarted()) {
            return false;
        }

        // If start_on_first_view and started, check expiry from first_viewed_at
        if ($grant->start_on_first_view && $grant->first_viewed_at !== null && $grant->expires_at !== null) {
            // expires_at is relative to first_viewed_at — already checked above
        }

        return true;
    }
}
