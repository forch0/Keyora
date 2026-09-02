<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Permission;
use App\Traits\BelongsToTenant;
use Database\Factories\AccessGrantFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccessGrant extends Model
{
    /** @use HasFactory<AccessGrantFactory> */
    use BelongsToTenant, HasFactory;

    use SoftDeletes;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Ensure views_count has a default value when creating new instances
        $this->attributes['views_count'] ??= 0;
    }

    protected $fillable = [
        'tenant_id',
        'grantable_type',
        'grantable_id',
        'subject_type',
        'subject_id',
        'permission',
        'expires_at',
        'max_views',
        'views_count',
        'starts_at',
        'start_on_first_view',
        'first_viewed_at',
        'warning_sent_at',
        'granted_by',
        'revoked_at',
        'revoked_by',
        'revoke_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'starts_at' => 'datetime',
            'first_viewed_at' => 'datetime',
            'warning_sent_at' => 'datetime',
            'revoked_at' => 'datetime',
            'start_on_first_view' => 'boolean',
            'max_views' => 'integer',
            'views_count' => 'integer',
            'permission' => Permission::class,
        ];
    }

    /**
     * The resource this grant applies to (VaultItem, Folder, ...).
     *
     * @return MorphTo<Model, $this>
     */
    public function grantable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The subject who received the grant (User, Team, Tenant).
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The user who granted this access.
     *
     * @return BelongsTo<User, $this>
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /**
     * The user who revoked this grant (if revoked).
     *
     * @return BelongsTo<User, $this>
     */
    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Scope to active (non-revoked, non-expired) grants.
     *
     * @param  Builder<static>  $builder
     * @return Builder<static>
     */
    public function scopeActive(Builder $builder): Builder
    {
        return $builder->whereNull('revoked_at')
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to expired (past expires_at, not revoked) grants.
     *
     * @param  Builder<static>  $builder
     * @return Builder<static>
     */
    public function scopeExpired(Builder $builder): Builder
    {
        return $builder->whereNull('revoked_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Scope to revoked grants.
     *
     * @param  Builder<static>  $builder
     * @return Builder<static>
     */
    public function scopeRevoked(Builder $builder): Builder
    {
        return $builder->whereNotNull('revoked_at');
    }

    /**
     * Check if this grant has been revoked.
     */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * Check if this grant has expired (past expires_at).
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        // For start_on_first_view grants, the clock hasn't started until first_viewed_at
        if ($this->start_on_first_view && $this->first_viewed_at === null) {
            return false;
        }

        return $this->expires_at <= now();
    }

    /**
     * Check if this grant is currently active (not revoked, not expired).
     */
    public function isActive(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    /**
     * Check if the view limit has been reached.
     */
    public function viewLimitReached(): bool
    {
        return $this->max_views !== null && $this->views_count >= $this->max_views;
    }

    /**
     * Check if the start time has arrived.
     */
    public function hasStarted(): bool
    {
        if ($this->start_on_first_view) {
            return $this->first_viewed_at !== null;
        }

        return $this->starts_at === null || $this->starts_at <= now();
    }
}
