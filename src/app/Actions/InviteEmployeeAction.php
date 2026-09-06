<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\SendInviteEmail;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use Illuminate\Support\Str;

class InviteEmployeeAction
{
    /**
     * Create an invitation for a user to join a tenant.
     *
     * @param  array<string, mixed>  $attributes  {email, role, team_ids?, initial_access?}
     */
    public function __invoke(Tenant $tenant, User $inviter, array $attributes): TenantInvitation
    {
        $invitation = TenantInvitation::create([
            'tenant_id' => $tenant->id,
            'email' => $attributes['email'],
            'role' => $attributes['role'],
            'team_ids' => $attributes['team_ids'] ?? null,
            'initial_access' => $attributes['initial_access'] ?? null,
            'token' => Str::uuid()->toString(),
            'invited_by' => $inviter->id,
            'expires_at' => now()->addDays(7),
        ]);

        SendInviteEmail::dispatch($invitation);

        return $invitation;
    }
}
