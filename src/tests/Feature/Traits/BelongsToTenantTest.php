<?php

declare(strict_types=1);

namespace Tests\Feature\Traits;

use App\Models\Tenant;
use App\Models\TenantScopedModel;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Helpers\TenantHelper;
use Tests\TestCase;

class BelongsToTenantTest extends TestCase
{
    use RefreshDatabase, TenantHelper;

    public function test_belongs_to_tenant_auto_sets_tenant_id(): void
    {
        $tenant = $this->createTenant();
        $this->setupTenantContext($tenant);

        $model = TenantScopedModel::create(['name' => 'Test Item']);

        $this->assertEquals($tenant->id, $model->tenant_id);
    }

    public function test_tenant_scope_filters_by_current_tenant(): void
    {
        $tenantA = $this->createTenant(['name' => 'Tenant A', 'slug' => 'tenant-a']);
        $tenantB = $this->createTenant(['name' => 'Tenant B', 'slug' => 'tenant-b']);

        // Create items for tenant A
        $this->setupTenantContext($tenantA);
        TenantScopedModel::create(['name' => 'A Item 1']);
        TenantScopedModel::create(['name' => 'A Item 2']);

        // Create items for tenant B
        $this->setupTenantContext($tenantB);
        TenantScopedModel::create(['name' => 'B Item 1']);

        // Querying as tenant A should only return A's items
        $this->setupTenantContext($tenantA);
        $items = TenantScopedModel::all();

        $this->assertCount(2, $items);
        $this->assertTrue($items->every(fn (TenantScopedModel $m) => $m->tenant_id === $tenantA->id));

        // Querying as tenant B should only return B's items
        $this->setupTenantContext($tenantB);
        $items = TenantScopedModel::all();

        $this->assertCount(1, $items);
        $this->assertEquals($tenantB->id, $items->first()->tenant_id);
    }

    public function test_querying_without_tenant_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('without a current tenant context');

        // Ensure no tenant context is set
        app(TenantManager::class)->forgetCurrentTenant();

        TenantScopedModel::all();
    }
}
