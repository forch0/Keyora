<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SecureLinkFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $uuid
 * @property string $resource_type
 * @property int $resource_id
 * @property int $created_by
 * @property string|null $recipient_email
 * @property string|null $password_hash
 * @property string|null $otp_code_hash
 * @property Carbon|null $otp_sent_at
 * @property Carbon|null $email_verified_at
 * @property string $permission
 * @property bool $download_enabled
 * @property Carbon|null $expires_at
 * @property int|null $first_view_expires_hours
 * @property int|null $max_views
 * @property int $views_count
 * @property Carbon|null $first_viewed_at
 * @property bool $is_one_time
 * @property Carbon|null $revoked_at
 * @property int|null $revoked_by
 * @property string|null $revoke_reason
 */
class SecureLink extends Model
{
    /** @use HasFactory<SecureLinkFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'uuid',
        'resource_type',
        'resource_id',
        'created_by',
        'recipient_email',
        'password_hash',
        'otp_code_hash',
        'otp_sent_at',
        'email_verified_at',
        'permission',
        'download_enabled',
        'expires_at',
        'first_view_expires_hours',
        'max_views',
        'views_count',
        'first_viewed_at',
        'is_one_time',
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
            'first_viewed_at' => 'datetime',
            'otp_sent_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'revoked_at' => 'datetime',
            'download_enabled' => 'boolean',
            'is_one_time' => 'boolean',
            'views_count' => 'integer',
            'max_views' => 'integer',
            'first_view_expires_hours' => 'integer',
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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
    public function scopeActive(Builder $builder): Builder
    {
        return $builder->whereNull('revoked_at')
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function isActive(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        if ($this->expires_at !== null && $this->expires_at <= now()) {
            return true;
        }

        // First-view relative expiration
        if ($this->first_view_expires_hours !== null && $this->first_viewed_at !== null) {
            return $this->first_viewed_at->addHours($this->first_view_expires_hours) <= now();
        }

        return false;
    }

    public function hasPassword(): bool
    {
        return $this->password_hash !== null;
    }

    public function requiresOtp(): bool
    {
        return $this->otp_code_hash !== null;
    }

    public function requiresEmailVerification(): bool
    {
        return $this->email_verified_at === null && $this->recipient_email !== null;
    }

    public function url(): string
    {
        return url('/share/'.$this->uuid);
    }
}
