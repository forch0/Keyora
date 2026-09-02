<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SecurityAlertFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property int $user_id
 * @property string $type
 * @property string $severity
 * @property string $title
 * @property string $message
 * @property array<string, mixed>|null $properties
 * @property Carbon|null $read_at
 * @property Carbon|null $dismissed_at
 */
class SecurityAlert extends Model
{
    /** @use HasFactory<SecurityAlertFactory> */
    use HasFactory;

    public const TYPE_NEW_DEVICE_LOGIN = 'new_device_login';

    public const TYPE_SUSPICIOUS_ACTIVITY = 'suspicious_activity';

    public const TYPE_EXPIRING_ACCESS = 'expiring_access';

    public const TYPE_ACCESS_EXPIRED = 'access_expired';

    public const SEVERITY_INFO = 'info';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_CRITICAL = 'critical';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'severity',
        'title',
        'message',
        'properties',
        'read_at',
        'dismissed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'read_at' => 'datetime',
            'dismissed_at' => 'datetime',
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
