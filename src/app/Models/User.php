<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_recovery_codes' => 'array',
        ];
    }

    /**
     * Tenants (workspaces) this user belongs to.
     *
     * @return BelongsToMany<Tenant, $this, Pivot, 'pivot'>
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class)
            ->withPivot(['role', 'status', 'joined_at', 'suspended_at', 'left_at'])
            ->withTimestamps();
    }

    /**
     * Check if the user is an active member of the given tenant.
     */
    public function isMemberOf(Tenant $tenant): bool
    {
        return $this->tenants()
            ->where('tenants.id', $tenant->id)
            ->where('tenant_user.status', 'active')
            ->whereNull('tenant_user.left_at')
            ->exists();
    }

    /**
     * Get the user's role in the given tenant, or null if not an active member.
     */
    public function roleIn(Tenant $tenant): ?string
    {
        $membership = $this->tenants()
            ->where('tenants.id', $tenant->id)
            ->where('tenant_user.status', 'active')
            ->whereNull('tenant_user.left_at')
            ->first();

        if ($membership === null) {
            return null;
        }

        $pivot = $membership->getRelation('pivot');

        if (! $pivot instanceof Pivot) {
            return null;
        }

        $role = $pivot->getAttribute('role');

        return is_string($role) ? $role : null;
    }

    /**
     * Check if the user owns the given tenant.
     */
    public function ownsTenant(Tenant $tenant): bool
    {
        return $this->roleIn($tenant) === 'owner';
    }

    /**
     * Check if the user is an admin or owner of the given tenant.
     */
    public function isAdminOf(Tenant $tenant): bool
    {
        return in_array($this->roleIn($tenant), ['owner', 'admin'], true);
    }

    /**
     * Get the user's status in the given tenant, or null if not a member.
     */
    public function statusIn(Tenant $tenant): ?string
    {
        $membership = $this->tenants()
            ->where('tenants.id', $tenant->id)
            ->whereNull('tenant_user.left_at')
            ->first();

        if ($membership === null) {
            return null;
        }

        $pivot = $membership->getRelation('pivot');

        if (! $pivot instanceof Pivot) {
            return null;
        }

        $status = $pivot->getAttribute('status');

        return is_string($status) ? $status : null;
    }

    /**
     * Teams this user belongs to.
     *
     * @return BelongsToMany<Team, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Check if the user is a member of the given team.
     */
    public function isTeamMember(Team $team): bool
    {
        return $this->teams()->where('teams.id', $team->id)->exists();
    }

    /**
     * Get the user's role in the given team, or null if not a member.
     */
    public function teamRole(Team $team): ?string
    {
        $membership = $this->teams()->where('teams.id', $team->id)->first();

        if ($membership === null) {
            return null;
        }

        $pivot = $membership->getRelation('pivot');

        if (! $pivot instanceof Pivot) {
            return null;
        }

        $role = $pivot->getAttribute('role');

        return is_string($role) ? $role : null;
    }

    /**
     * Check if the user is a team lead of the given team.
     */
    public function isTeamLead(Team $team): bool
    {
        return $this->teamRole($team) === 'lead';
    }
}
