<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Tenants;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Tests\Helpers\AuthHelper;
use Tests\Helpers\TenantHelper;
use Tests\TestCase;

class TenantCrudTest extends TestCase
{
    use AuthHelper, RefreshDatabase, TenantHelper;

    public function test_user_can_create_tenant(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/tenants', [
                'name' => 'My Company',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'slug', 'plan', 'settings', 'trial_ends_at', 'created_at'],
                'role',
            ]);

        $tenant = Tenant::where('name', 'My Company')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('owner', $user->roleIn($tenant));
        $this->assertEquals('free', $tenant->plan);
    }

    public function test_user_can_list_their_tenants(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $tenantA = $this->createTenant(['name' => 'Company A', 'slug' => 'company-a']);
        $tenantB = $this->createTenant(['name' => 'Company B', 'slug' => 'company-b']);
        $this->attachUserToTenant($user, $tenantA, 'owner');
        $this->attachUserToTenant($user, $tenantB, 'member');

        // A tenant the user does NOT belong to
        $tenantC = $this->createTenant(['name' => 'Company C', 'slug' => 'company-c']);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/tenants');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['name' => 'Company A'])
            ->assertJsonFragment(['name' => 'Company B']);

        $this->assertFalse(
            collect($response->json('data'))->contains('name', 'Company C'),
            'Tenants the user does not belong to should not appear in the list.'
        );
    }

    public function test_user_can_view_tenant_as_member(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $tenant = $this->createTenant(['name' => 'Viewable Co', 'slug' => 'viewable-co']);
        $this->attachUserToTenant($user, $tenant, 'member');

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson("/api/v1/tenants/{$tenant->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Viewable Co', 'id' => $tenant->id]);
    }

    public function test_non_member_cannot_view_tenant(): void
    {
        [, $token] = $this->createAndAuthUser();

        $tenant = $this->createTenant(['name' => 'Private Co', 'slug' => 'private-co']);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson("/api/v1/tenants/{$tenant->id}");

        $response->assertStatus(403);
    }

    public function test_only_owner_can_delete_tenant(): void
    {
        // Member cannot delete
        [$member, $memberToken] = $this->createAndAuthUser();
        $tenantOwnedByOther = $this->createTenant(['name' => 'Other Co', 'slug' => 'other-co']);
        $this->attachUserToTenant($member, $tenantOwnedByOther, 'member');

        $response = $this->withHeaders($this->authHeaders($memberToken))
            ->deleteJson("/api/v1/tenants/{$tenantOwnedByOther->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('tenants', ['id' => $tenantOwnedByOther->id]);

        // Reset cached guard state between authenticated requests in the same test
        // (RequestGuard caches the user in memory — see Module 02 learnings)
        Auth::forgetGuards();

        // Owner can delete
        [$owner, $ownerToken] = $this->createAndAuthUser(['email' => 'owner@example.com']);
        $tenantOwned = $this->createTenant(['name' => 'My Co', 'slug' => 'my-co']);
        $this->attachUserToTenant($owner, $tenantOwned, 'owner');

        $response = $this->withHeaders($this->authHeaders($ownerToken))
            ->deleteJson("/api/v1/tenants/{$tenantOwned->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('tenants', ['id' => $tenantOwned->id]);
    }

    public function test_tenant_resolution_via_header(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant(['name' => 'Header Co', 'slug' => 'header-co']);
        $this->attachUserToTenant($user, $tenant, 'owner');

        // Register a test route that uses tenant.resolve and reports the
        // current tenant id from TenantManager, so we can verify the header
        // actually set the context.
        Route::get('/api/v1/_test/tenant-context', function (TenantManager $manager) {
            return response()->json(['tenant_id' => $manager->currentTenantId()]);
        })->middleware(['auth:sanctum', 'tenant.resolve']);

        $response = $this->withHeaders(array_merge($this->authHeaders($token), [
            'X-Tenant-ID' => (string) $tenant->id,
        ]))->getJson('/api/v1/_test/tenant-context');

        $response->assertStatus(200)
            ->assertJsonFragment(['tenant_id' => $tenant->id]);
    }

    public function test_invalid_tenant_id_returns_403(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $tenant = $this->createTenant(['name' => 'Not Yours', 'slug' => 'not-yours']);
        // User is NOT attached to this tenant

        Route::get('/api/v1/_test/tenant-context', fn () => response()->json(['ok' => true]))
            ->middleware(['auth:sanctum', 'tenant.resolve']);

        $response = $this->withHeaders(array_merge($this->authHeaders($token), [
            'X-Tenant-ID' => (string) $tenant->id,
        ]))->getJson('/api/v1/_test/tenant-context');

        $response->assertStatus(403);
    }
}
