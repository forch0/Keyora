<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Tenant;
use App\Services\TenantManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Trait for models that belong to a tenant.
 *
 * Adds a global scope filtering by the current tenant, auto-sets
 * tenant_id on creation, and throws if a query is attempted without
 * a tenant context (fail-closed to prevent cross-tenant data leaks).
 *
 * Use {@see withoutTenant()} to explicitly bypass the scope for
 * admin/cross-tenant operations.
 */
trait BelongsToTenant
{
    /**
     * Boot the trait's global scope and creating hook.
     */
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $manager = app(TenantManager::class);

            if (! $manager->hasCurrentTenant()) {
                throw new RuntimeException(
                    'Querying a tenant-scoped model without a current tenant context is not allowed. '.
                    'Call TenantManager::setCurrentTenant() or use withoutTenant() to bypass explicitly.'
                );
            }

            $builder->where($builder->getModel()->getTable().'.tenant_id', $manager->currentTenantId());
        });

        static::creating(function (Model $model): void {
            if (! $model->getAttribute('tenant_id')) {
                $manager = app(TenantManager::class);

                if ($manager->hasCurrentTenant()) {
                    $model->setAttribute('tenant_id', $manager->currentTenantId());
                }
            }
        });
    }

    /**
     * The tenant this model belongs to.
     *
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Explicitly bypass the tenant scope for this query.
     *
     * Use sparingly — only for admin or cross-tenant operations.
     *
     * @param  Builder<static>  $builder
     * @return Builder<static>
     */
    public function scopeWithoutTenant(Builder $builder): Builder
    {
        return $builder->withoutGlobalScope('tenant');
    }

    /**
     * Resolve route binding without the tenant global scope.
     * The tenant check is enforced in the controller/policy, not during binding.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return static|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return static::query()->withoutGlobalScope('tenant')->where($field ?? $this->getKeyName(), $value)->first();
    }
}
