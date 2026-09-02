<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $email
 * @property string $role
 * @property array<int, int>|null $team_ids
 * @property array<int, array{resource_type: string, resource_id: int, permission: string}>|null $initial_access
 * @property string $token
 * @property int $invited_by
 * @property Carbon|null $accepted_at
 * @property Carbon $expires_at
 * @property-read Tenant|null $tenant
 * @property-read User|null $inviter
 */
#[Fillable(['tenant_id', 'email', 'role', 'team_ids', 'initial_access', 'token', 'invited_by', 'accepted_at', 'expires_at'])]
class TenantInvitation extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'expires_at' => 'datetime',
            'team_ids' => 'array',
            'initial_access' => 'array',
        ];
    }

    /**
     * The tenant this invitation belongs to.
     *
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * The user who sent the invitation.
     *
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Check if the invitation has been accepted.
     */
    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    /**
     * Check if the invitation has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the invitation is still pending (not accepted and not expired).
     */
    public function isPending(): bool
    {
        return ! $this->isAccepted() && ! $this->isExpired();
    }
}
