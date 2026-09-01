<?php

namespace Tests\Helpers;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;

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

    protected function attachUserToTenant(User $user, Tenant $tenant, string $role = 'owner'): void
    {
        $tenant->users()->attach($user, [
            'role' => $role,
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }

    protected function setupTenantContext(Tenant $tenant): void
    {
        app(TenantManager::class)->setCurrentTenant($tenant->id);
    }
}
