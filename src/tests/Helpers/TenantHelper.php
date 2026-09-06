<?php

namespace Tests\Helpers;

use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Support\Str;

trait TenantHelper
{
    protected function createTenant(array $attributes = []): Tenant
    {
        return Tenant::create(array_merge([
            'name' => 'Test Company',
            'slug' => 'test-company',
            'plan' => 'free',
        ], $attributes));
    }

    protected function attachUserToTenant(User $user, Tenant $tenant, string $role = 'owner', array $extra = []): void
    {
        $tenant->users()->attach($user, array_merge([
            'role' => $role,
            'status' => 'active',
            'joined_at' => now(),
        ], $extra));
    }

    protected function setupTenantContext(Tenant $tenant): void
    {
        app(TenantManager::class)->setCurrentTenant($tenant->id);
    }

    protected function createInvitation(Tenant $tenant, User $inviter, array $attributes = []): TenantInvitation
    {
        return TenantInvitation::create(array_merge([
            'tenant_id' => $tenant->id,
            'email' => 'invitee@example.com',
            'role' => 'member',
            'token' => Str::uuid()->toString(),
            'invited_by' => $inviter->id,
            'expires_at' => now()->addDays(7),
        ], $attributes));
    }
}
