<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Teams;

use App\Models\Team;
use App\Models\Tenant;
use App\Models\VaultItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AuthHelper;
use Tests\Helpers\TenantHelper;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use AuthHelper, RefreshDatabase, TenantHelper;

    public function test_admin_can_create_team(): void
    {
        $admin = $this->createUser(['email' => 'admin@example.com']);
        $tenant = $this->createTenant();
        $this->attachUserToTenant($admin, $tenant, 'admin');
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->postJson('/api/v1/tenants/'.$tenant->id.'/teams', [
            'name' => 'Engineering',
            'description' => 'Eng team',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Engineering']);
    }

    public function test_member_cannot_create_team(): void
    {
        $member = $this->createUser(['email' => 'member@example.com']);
        $tenant = $this->createTenant();
        $this->attachUserToTenant($member, $tenant, 'member');
        $token = $member->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->postJson('/api/v1/tenants/'.$tenant->id.'/teams', [
            'name' => 'Engineering',
        ]);

        $response->assertStatus(403);
    }

    public function test_can_add_member_to_team(): void
    {
        $admin = $this->createUser(['email' => 'admin@example.com']);
        $member = $this->createUser(['email' => 'member@example.com']);
        $tenant = $this->createTenant();
        $this->attachUserToTenant($admin, $tenant, 'admin');
        $this->attachUserToTenant($member, $tenant, 'member');
        $token = $admin->createToken('test')->plainTextToken;

        $this->setupTenantContext($tenant);
        $team = Team::factory()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $admin->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->postJson('/api/v1/tenants/'.$tenant->id.'/teams/'.$team->id.'/members', [
            'user_id' => $member->id,
            'role' => 'member',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['id' => $member->id, 'role' => 'member']);
    }

    public function test_user_can_belong_to_multiple_teams(): void
    {
        $admin = $this->createUser(['email' => 'admin@example.com']);
        $member = $this->createUser(['email' => 'member@example.com']);
        $tenant = $this->createTenant();
        $this->attachUserToTenant($admin, $tenant, 'admin');
        $this->attachUserToTenant($member, $tenant, 'member');

        $this->setupTenantContext($tenant);
        $team1 = Team::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $admin->id]);
        $team2 = Team::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $admin->id]);

        $team1->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);
        $team2->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $this->assertTrue($member->isTeamMember($team1));
        $this->assertTrue($member->isTeamMember($team2));
    }

    public function test_team_member_can_create_vault_item(): void
    {
        $admin = $this->createUser(['email' => 'admin@example.com']);
        $tenant = $this->createTenant();
        $this->attachUserToTenant($admin, $tenant, 'admin');
        $token = $admin->createToken('test')->plainTextToken;

        $this->setupTenantContext($tenant);
        $team = Team::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $admin->id]);
        $team->members()->attach($admin->id, ['role' => 'lead', 'joined_at' => now()]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->postJson('/api/v1/tenants/'.$tenant->id.'/teams/'.$team->id.'/vault/items', [
            'name' => 'Production DB',
            'type' => 'database',
            'username' => 'root',
            'password' => 'secret123',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Production DB', 'type' => 'database']);
    }

    public function test_team_member_can_view_vault_item(): void
    {
        $admin = $this->createUser(['email' => 'admin@example.com']);
        $tenant = $this->createTenant();
        $this->attachUserToTenant($admin, $tenant, 'admin');
        $token = $admin->createToken('test')->plainTextToken;

        $this->setupTenantContext($tenant);
        $team = Team::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $admin->id]);
        $team->members()->attach($admin->id, ['role' => 'lead', 'joined_at' => now()]);

        $item = VaultItem::factory()->create([
            'tenant_id' => $tenant->id,
            'team_id' => $team->id,
            'user_id' => $admin->id,
            'name' => 'Shared Credential',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->getJson('/api/v1/tenants/'.$tenant->id.'/teams/'.$team->id.'/vault/items/'.$item->id);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Shared Credential']);
    }

    public function test_non_team_member_cannot_view_vault_item(): void
    {
        $admin = $this->createUser(['email' => 'admin@example.com']);
        $outsider = $this->createUser(['email' => 'out@example.com']);
        $tenant = $this->createTenant();
        $this->attachUserToTenant($admin, $tenant, 'admin');
        $this->attachUserToTenant($outsider, $tenant, 'member');
        $outsiderToken = $outsider->createToken('test')->plainTextToken;

        $this->setupTenantContext($tenant);
        $team = Team::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $admin->id]);
        // Outsider is NOT attached to the team

        $item = VaultItem::factory()->create([
            'tenant_id' => $tenant->id,
            'team_id' => $team->id,
            'user_id' => $admin->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$outsiderToken,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->getJson('/api/v1/tenants/'.$tenant->id.'/teams/'.$team->id.'/vault/items/'.$item->id);

        $response->assertStatus(403);
    }

    public function test_org_wide_items_accessible_to_all_members(): void
    {
        $admin = $this->createUser(['email' => 'admin@example.com']);
        $member = $this->createUser(['email' => 'member@example.com']);
        $tenant = $this->createTenant();
        $this->attachUserToTenant($admin, $tenant, 'admin');
        $this->attachUserToTenant($member, $tenant, 'member');
        $memberToken = $member->createToken('test')->plainTextToken;

        // Admin creates an org-wide item
        $this->setupTenantContext($tenant);
        VaultItem::factory()->create([
            'tenant_id' => $tenant->id,
            'team_id' => null,
            'user_id' => $admin->id,
            'name' => 'Org Secret',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$memberToken,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->getJson('/api/v1/tenants/'.$tenant->id.'/vault/items');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Org Secret']);
    }

    public function test_team_lead_can_add_members(): void
    {
        $lead = $this->createUser(['email' => 'lead@example.com']);
        $newMember = $this->createUser(['email' => 'new@example.com']);
        $tenant = $this->createTenant();
        $this->attachUserToTenant($lead, $tenant, 'member');
        $this->attachUserToTenant($newMember, $tenant, 'member');
        $leadToken = $lead->createToken('test')->plainTextToken;

        $this->setupTenantContext($tenant);
        $team = Team::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $lead->id]);
        $team->members()->attach($lead->id, ['role' => 'lead', 'joined_at' => now()]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$leadToken,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->postJson('/api/v1/tenants/'.$tenant->id.'/teams/'.$team->id.'/members', [
            'user_id' => $newMember->id,
            'role' => 'member',
        ]);

        $response->assertStatus(201);
    }

    public function test_team_lead_can_remove_members(): void
    {
        $lead = $this->createUser(['email' => 'lead@example.com']);
        $member = $this->createUser(['email' => 'member@example.com']);
        $tenant = $this->createTenant();
        $this->attachUserToTenant($lead, $tenant, 'member');
        $this->attachUserToTenant($member, $tenant, 'member');
        $leadToken = $lead->createToken('test')->plainTextToken;

        $this->setupTenantContext($tenant);
        $team = Team::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $lead->id]);
        $team->members()->attach($lead->id, ['role' => 'lead', 'joined_at' => now()]);
        $team->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$leadToken,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->deleteJson('/api/v1/tenants/'.$tenant->id.'/teams/'.$team->id.'/members/'.$member->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('team_user', ['team_id' => $team->id, 'user_id' => $member->id]);
    }

    public function test_admin_can_delete_team(): void
    {
        $admin = $this->createUser(['email' => 'admin@example.com']);
        $tenant = $this->createTenant();
        $this->attachUserToTenant($admin, $tenant, 'admin');
        $token = $admin->createToken('test')->plainTextToken;

        $this->setupTenantContext($tenant);
        $team = Team::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $admin->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->deleteJson('/api/v1/tenants/'.$tenant->id.'/teams/'.$team->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    }

    public function test_team_vault_items_are_encrypted(): void
    {
        $admin = $this->createUser(['email' => 'admin@example.com']);
        $tenant = $this->createTenant();
        $this->attachUserToTenant($admin, $tenant, 'admin');
        $token = $admin->createToken('test')->plainTextToken;

        $this->setupTenantContext($tenant);
        $team = Team::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $admin->id]);
        $team->members()->attach($admin->id, ['role' => 'lead', 'joined_at' => now()]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenant->id,
        ])->postJson('/api/v1/tenants/'.$tenant->id.'/teams/'.$team->id.'/vault/items', [
            'name' => 'Encrypted Test',
            'type' => 'password',
            'username' => 'myuser',
            'password' => 'mypassword',
        ])->assertStatus(201);

        // Verify DB has encrypted values
        $rawUsername = \DB::table('vault_items')->where('name', 'Encrypted Test')->value('username');
        $rawPassword = \DB::table('vault_items')->where('name', 'Encrypted Test')->value('password');
        $this->assertNotEquals('myuser', $rawUsername);
        $this->assertNotEquals('mypassword', $rawPassword);
    }

    public function test_tenant_scope_isolates_teams(): void
    {
        $adminA = $this->createUser(['email' => 'a@example.com']);
        $adminB = $this->createUser(['email' => 'b@example.com']);
        $tenantA = $this->createTenant(['name' => 'Company A', 'slug' => 'company-a']);
        $tenantB = $this->createTenant(['name' => 'Company B', 'slug' => 'company-b']);
        $this->attachUserToTenant($adminA, $tenantA, 'admin');
        $this->attachUserToTenant($adminB, $tenantB, 'admin');
        $tokenB = $adminB->createToken('test')->plainTextToken;

        // Create team in Tenant A
        $this->setupTenantContext($tenantA);
        Team::factory()->create(['tenant_id' => $tenantA->id, 'created_by' => $adminA->id, 'name' => 'Team A']);

        // Tenant B admin lists teams — should NOT see Tenant A's team
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$tokenB,
            'X-Tenant-ID' => (string) $tenantB->id,
        ])->getJson('/api/v1/tenants/'.$tenantB->id.'/teams');

        $response->assertStatus(200)
            ->assertJsonMissing(['name' => 'Team A']);
    }
}
