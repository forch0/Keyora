<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AccessRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $requester_id
 * @property string $resource_type
 * @property int $resource_id
 * @property int $resource_owner_id
 * @property string $requested_permission
 * @property string|null $granted_permission
 * @property string|null $requested_duration
 * @property Carbon|null $granted_expires_at
 * @property string $reason
 * @property string $status
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $review_note
 */
class AccessRequest extends Model
{
    /** @use HasFactory<AccessRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'requester_id',
        'resource_type',
        'resource_id',
        'resource_owner_id',
        'requested_permission',
        'granted_permission',
        'requested_duration',
        'granted_expires_at',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'granted_expires_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resourceOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resource_owner_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function resource(): MorphTo
    {
        return $this->morphTo(null, 'resource_type', 'resource_id');
    }

    /**
     * @param  Builder<static>  $builder
     * @return Builder<static>
     */
    public function scopePending(Builder $builder): Builder
    {
        return $builder->where('status', 'pending');
    }

    /**
     * Requests where the user is the requester or the resource owner.
     *
     * @param  Builder<static>  $builder
     * @return Builder<static>
     */
    public function scopeForUser(Builder $builder, User $user): Builder
    {
        return $builder->where(function (Builder $q) use ($user): void {
            $q->where('requester_id', $user->id)
                ->orWhere('resource_owner_id', $user->id);
        });
    }
}
