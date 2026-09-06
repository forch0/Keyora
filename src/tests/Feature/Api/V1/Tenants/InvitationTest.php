<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Tenants;

use App\Jobs\SendInviteEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Helpers\AuthHelper;
use Tests\Helpers\TenantHelper;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use AuthHelper, RefreshDatabase, TenantHelper;

    public function test_admin_can_invite_member(): void
    {
        Queue::fake();

        [$admin, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant(['name' => 'Invite Co', 'slug' => 'invite-co']);
        $this->attachUserToTenant($admin, $tenant, 'admin');

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/invite", [
                'email' => 'newuser@example.com',
                'role' => 'member',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'email', 'role', 'expires_at', 'created_at'],
            ]);

        $this->assertDatabaseHas('tenant_invitations', [
            'tenant_id' => $tenant->id,
            'email' => 'newuser@example.com',
            'role' => 'member',
        ]);

        Queue::assertPushed(SendInviteEmail::class);
    }

    public function test_member_cannot_invite(): void
    {
        [, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant(['name' => 'Member Co', 'slug' => 'member-co']);
        $this->attachUserToTenant($this->createUser(['email' => 'member@example.com']), $tenant, 'member');

        // Use the member's token — need to create a member user with token
        [$member, $memberToken] = $this->createAndAuthUser(['email' => 'member2@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');

        $response = $this->withHeaders($this->authHeaders($memberToken))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/invite", [
                'email' => 'newuser@example.com',
                'role' => 'member',
            ]);

        $response->assertStatus(403);
    }

    public function test_user_can_accept_invitation(): void
    {
        [$inviter, $inviterToken] = $this->createAndAuthUser();
        $tenant = $this->createTenant(['name' => 'Accept Co', 'slug' => 'accept-co']);
        $this->attachUserToTenant($inviter, $tenant, 'owner');

        $invitation = $this->createInvitation($tenant, $inviter, [
            'email' => 'invitee@example.com',
        ]);

        [$invitee, $inviteeToken] = $this->createAndAuthUser(['email' => 'invitee@example.com']);

        $response = $this->withHeaders($this->authHeaders($inviteeToken))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/accept", [
                'token' => $invitation->token,
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['tenant_id' => $tenant->id]);

        $this->assertTrue($invitee->isMemberOf($tenant));
        $this->assertEquals('member', $invitee->roleIn($tenant));

        $this->assertDatabaseHas('tenant_invitations', [
            'id' => $invitation->id,
            'accepted_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_cannot_accept_expired_invitation(): void
    {
        [$inviter, $inviterToken] = $this->createAndAuthUser();
        $tenant = $this->createTenant(['name' => 'Expire Co', 'slug' => 'expire-co']);
        $this->attachUserToTenant($inviter, $tenant, 'owner');

        $invitation = $this->createInvitation($tenant, $inviter, [
            'expires_at' => now()->subDay(),
        ]);

        [$invitee, $inviteeToken] = $this->createAndAuthUser(['email' => 'invitee@example.com']);

        $response = $this->withHeaders($this->authHeaders($inviteeToken))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/accept", [
                'token' => $invitation->token,
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['code' => 'INVITATION_INVALID']);
    }

    public function test_cannot_accept_already_accepted(): void
    {
        [$inviter, $inviterToken] = $this->createAndAuthUser();
        $tenant = $this->createTenant(['name' => 'Double Co', 'slug' => 'double-co']);
        $this->attachUserToTenant($inviter, $tenant, 'owner');

        $invitation = $this->createInvitation($tenant, $inviter);
        $invitation->update(['accepted_at' => now()]);

        [$invitee, $inviteeToken] = $this->createAndAuthUser(['email' => 'invitee@example.com']);

        $response = $this->withHeaders($this->authHeaders($inviteeToken))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/accept", [
                'token' => $invitation->token,
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['code' => 'INVITATION_INVALID']);
    }

    public function test_can_list_pending_invitations(): void
    {
        [$owner, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant(['name' => 'List Inv Co', 'slug' => 'list-inv-co']);
        $this->attachUserToTenant($owner, $tenant, 'owner');

        $this->createInvitation($tenant, $owner, ['email' => 'a@example.com']);
        $this->createInvitation($tenant, $owner, ['email' => 'b@example.com']);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson("/api/v1/tenants/{$tenant->id}/invitations");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['email' => 'a@example.com'])
            ->assertJsonFragment(['email' => 'b@example.com']);
    }

    public function test_can_cancel_invitation(): void
    {
        [$owner, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant(['name' => 'Cancel Co', 'slug' => 'cancel-co']);
        $this->attachUserToTenant($owner, $tenant, 'owner');

        $invitation = $this->createInvitation($tenant, $owner);

        $response = $this->withHeaders($this->authHeaders($token))
            ->deleteJson("/api/v1/tenants/{$tenant->id}/invitations/{$invitation->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('tenant_invitations', ['id' => $invitation->id]);
    }
}
