<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Tenants;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Helpers\AuthHelper;
use Tests\Helpers\TenantHelper;
use Tests\TestCase;

class MemberManagementTest extends TestCase
{
    use AuthHelper, RefreshDatabase, TenantHelper;

    public function test_members_can_list_members(): void
    {
        [$owner, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant(['name' => 'List Co', 'slug' => 'list-co']);
        $this->attachUserToTenant($owner, $tenant, 'owner');

        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson("/api/v1/tenants/{$tenant->id}/members");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['email' => 'member@example.com'])
            ->assertJsonFragment(['email' => 'test@example.com']);
    }

    public function test_can_view_member_profile(): void
    {
        [$owner, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant(['name' => 'Profile Co', 'slug' => 'profile-co']);
        $this->attachUserToTenant($owner, $tenant, 'owner');

        $member = $this->createUser(['email' => 'viewable@example.com', 'name' => 'Viewable User']);
        $this->attachUserToTenant($member, $tenant, 'member');

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $member->id,
                'name' => 'Viewable User',
                'email' => 'viewable@example.com',
                'role' => 'member',
            ]);
    }

    public function test_admin_can_change_role(): void
    {
        [$admin, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant(['name' => 'Role Co', 'slug' => 'role-co']);
        $this->attachUserToTenant($admin, $tenant, 'admin');

        $member = $this->createUser(['email' => 'promote@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');

        $response = $this->withHeaders($this->authHeaders($token))
            ->putJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}", [
                'role' => 'admin',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['role' => 'admin']);

        $this->assertEquals('admin', $member->fresh()->roleIn($tenant));
    }

    public function test_cannot_change_owner_role(): void
    {
        [$owner, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant(['name' => 'Owner Co', 'slug' => 'owner-co']);
        $this->attachUserToTenant($owner, $tenant, 'owner');

        $response = $this->withHeaders($this->authHeaders($token))
            ->putJson("/api/v1/tenants/{$tenant->id}/members/{$owner->id}", [
                'role' => 'member',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_suspend_member(): void
    {
        [$admin, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant(['name' => 'Suspend Co', 'slug' => 'suspend-co']);
        $this->attachUserToTenant($admin, $tenant, 'admin');

        $member = $this->createUser(['email' => 'suspend@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}/suspend");

        $response->assertStatus(204);

        $membership = $tenant->users()->wherePivot('user_id', $member->id)->first();
        $pivot = $membership?->getRelation('pivot');
        $this->assertEquals('suspended', $pivot?->getAttribute('status'));
        $this->assertNotNull($pivot?->getAttribute('suspended_at'));
    }

    public function test_suspended_member_cannot_access_tenant(): void
    {
        [$admin, $adminToken] = $this->createAndAuthUser();
        $tenant = $this->createTenant(['name' => 'Blocked Co', 'slug' => 'blocked-co']);
        $this->attachUserToTenant($admin, $tenant, 'admin');

        [$member, $memberToken] = $this->createAndAuthUser(['email' => 'blocked@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');

        // Suspend the member
        $this->withHeaders($this->authHeaders($adminToken))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}/suspend")
            ->assertStatus(204);

        Auth::forgetGuards();

        // Suspended member tries to access tenant-scoped endpoint
        $response = $this->withHeaders(array_merge($this->authHeaders($memberToken), [
            'X-Tenant-ID' => (string) $tenant->id,
        ]))->getJson("/api/v1/tenants/{$tenant->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_restore_member(): void
    {
        [$admin, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant(['name' => 'Restore Co', 'slug' => 'restore-co']);
        $this->attachUserToTenant($admin, $tenant, 'admin');

        $member = $this->createUser(['email' => 'restore@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member', [
            'status' => 'suspended',
            'suspended_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}/restore");

        $response->assertStatus(204);

        $membership = $tenant->users()->wherePivot('user_id', $member->id)->first();
        $pivot = $membership?->getRelation('pivot');
        $this->assertEquals('active', $pivot?->getAttribute('status'));
        $this->assertNull($pivot?->getAttribute('suspended_at'));
    }

    public function test_admin_can_remove_member(): void
    {
        [$admin, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant(['name' => 'Remove Co', 'slug' => 'remove-co']);
        $this->attachUserToTenant($admin, $tenant, 'admin');

        $member = $this->createUser(['email' => 'remove@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');

        $response = $this->withHeaders($this->authHeaders($token))
            ->deleteJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}");

        $response->assertStatus(204);

        $membership = $tenant->users()->wherePivot('user_id', $member->id)->first();
        $pivot = $membership?->getRelation('pivot');
        $this->assertEquals('left', $pivot?->getAttribute('status'));
        $this->assertNotNull($pivot?->getAttribute('left_at'));
    }

    public function test_cannot_remove_owner(): void
    {
        [$admin, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant(['name' => 'NoRemove Co', 'slug' => 'no-remove-co']);
        $this->attachUserToTenant($admin, $tenant, 'admin');

        $owner = $this->createUser(['email' => 'owner@example.com']);
        $this->attachUserToTenant($owner, $tenant, 'owner');

        $response = $this->withHeaders($this->authHeaders($token))
            ->deleteJson("/api/v1/tenants/{$tenant->id}/members/{$owner->id}");

        $response->assertStatus(403);
    }
}
