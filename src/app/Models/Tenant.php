<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'plan', 'settings', 'trial_ends_at'])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'trial_ends_at' => 'datetime',
        ];
    }

    /**
     * Users that belong to this tenant (workspace members).
     *
     * @return BelongsToMany<User, $this, Pivot, 'pivot'>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'status', 'joined_at', 'suspended_at', 'left_at'])
            ->withTimestamps();
    }

    /**
     * Active (non-left, non-suspended) members of this tenant.
     *
     * @return BelongsToMany<User, $this, Pivot, 'pivot'>
     */
    public function activeMembers(): BelongsToMany
    {
        return $this->users()->wherePivot('status', 'active')->wherePivotNull('left_at');
    }

    /**
     * Pending invitations for this tenant.
     *
     * @return HasMany<TenantInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TenantInvitation::class);
    }

    /**
     * Teams within this tenant workspace.
     *
     * @return HasMany<Team, $this>
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * Org-wide vault items (team_id = null) for this tenant.
     *
     * @return HasMany<VaultItem, $this>
     */
    public function vaultItems(): HasMany
    {
        return $this->hasMany(VaultItem::class);
    }

    /**
     * Resolve child route binding without triggering the BelongsToTenant
     * global scope on the child model (which would throw during route
     * binding before the tenant context is set).
     *
     * @param  string  $childType
     * @param  mixed  $value
     * @param  string|null  $field
     * @return Model|null
     */
    public function resolveChildRouteBinding($childType, $value, $field = null)
    {
        $relation = $this->{$childType}();

        /** @var Model $related */
        $related = $relation->getRelated();

        // Bypass the tenant global scope on the child model
        if (method_exists($related, 'withoutTenant')) {
            return $related->newQuery()
                ->withoutGlobalScope('tenant')
                ->where($field ?? $related->getKeyName(), $value)
                ->first();
        }

        return parent::resolveChildRouteBinding($childType, $value, $field);
    }
}
