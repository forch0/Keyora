<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Security;

use App\Jobs\CheckExpiredAccess;
use App\Jobs\CheckExpiringAccess;
use App\Models\AccessGrant;
use App\Models\ActivityLog;
use App\Models\SecurityAlert;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\VaultItem;
use App\Notifications\NewDeviceLogin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Helpers\AuthHelper;
use Tests\Helpers\TenantHelper;
use Tests\TestCase;

class SecurityAlertTest extends TestCase
{
    use AuthHelper, RefreshDatabase, TenantHelper;

    private function setupTenantAndUser(string $role = 'admin'): array
    {
        $tenant = $this->createTenant();
        $user = $this->createUser(['email' => 'user@example.com', 'name' => 'Test User', 'password' => bcrypt('password')]);
        $this->attachUserToTenant($user, $tenant, $role);
        $this->setupTenantContext($tenant);
        $token = $user->createToken('test')->plainTextToken;

        return [$tenant, $user, $token];
    }

    private function authHeaders(string $token, int $tenantId): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => (string) $tenantId,
        ];
    }

    public function test_new_device_login_creates_alert(): void
    {
        Notification::fake();

        $tenant = $this->createTenant();
        $user = $this->createUser(['email' => 'newdevice@example.com', 'password' => bcrypt('password')]);
        $this->attachUserToTenant($user, $tenant, 'admin');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'newdevice@example.com',
            'password' => 'password',
        ], ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('security_alerts', [
            'user_id' => $user->id,
            'type' => SecurityAlert::TYPE_NEW_DEVICE_LOGIN,
        ]);
        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'browser' => 'Chrome',
            'os' => 'Windows',
        ]);

        Notification::assertSentTo($user, NewDeviceLogin::class);
    }

    public function test_known_device_no_alert(): void
    {
        Notification::fake();

        $tenant = $this->createTenant();
        $user = $this->createUser(['email' => 'known@example.com', 'password' => bcrypt('password')]);
        $this->attachUserToTenant($user, $tenant, 'admin');

        // First login — creates alert
        $this->postJson('/api/v1/auth/login', [
            'email' => 'known@example.com',
            'password' => 'password',
        ], ['User-Agent' => 'Chrome on Windows']);

        // Second login from same device — no new alert
        SecurityAlert::where('user_id', $user->id)->delete();
        $this->postJson('/api/v1/auth/login', [
            'email' => 'known@example.com',
            'password' => 'password',
        ], ['User-Agent' => 'Chrome on Windows']);

        $this->assertDatabaseMissing('security_alerts', [
            'user_id' => $user->id,
            'type' => SecurityAlert::TYPE_NEW_DEVICE_LOGIN,
        ]);
    }

    public function test_failed_logins_create_alert(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser(['email' => 'failed@example.com', 'password' => bcrypt('password')]);
        $this->attachUserToTenant($user, $tenant, 'admin');

        // Create 5 failed login activity logs
        for ($i = 0; $i < 5; $i++) {
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'auth.login_failed',
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Chrome',
                'created_at' => now(),
            ]);
        }

        $this->artisan('security:detect-suspicious')->assertSuccessful();

        $this->assertDatabaseHas('security_alerts', [
            'user_id' => $user->id,
            'type' => SecurityAlert::TYPE_SUSPICIOUS_ACTIVITY,
        ]);
    }

    public function test_expiring_access_creates_alert(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();
        $item = VaultItem::create([
            'tenant_id' => $tenant->id, 'team_id' => null, 'user_id' => $user->id,
            'name' => 'Shared', 'type' => 'password', 'username' => 'u', 'password' => 'p',
        ]);

        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => VaultItem::class,
            'grantable_id' => $item->id,
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'permission' => 'view',
            'views_count' => 0,
            'granted_by' => $user->id,
            'granted_at' => now(),
            'expires_at' => now()->addHours(12),
        ]);

        (new CheckExpiringAccess)->handle();

        $this->assertDatabaseHas('security_alerts', [
            'user_id' => $user->id,
            'type' => SecurityAlert::TYPE_EXPIRING_ACCESS,
        ]);
    }

    public function test_expired_access_creates_alert(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();
        $item = VaultItem::create([
            'tenant_id' => $tenant->id, 'team_id' => null, 'user_id' => $user->id,
            'name' => 'Shared', 'type' => 'password', 'username' => 'u', 'password' => 'p',
        ]);

        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => VaultItem::class,
            'grantable_id' => $item->id,
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'permission' => 'view',
            'views_count' => 0,
            'granted_by' => $user->id,
            'granted_at' => now()->subHours(2),
            'expires_at' => now()->subMinutes(30),
        ]);

        (new CheckExpiredAccess)->handle();

        $this->assertDatabaseHas('security_alerts', [
            'user_id' => $user->id,
            'type' => SecurityAlert::TYPE_ACCESS_EXPIRED,
        ]);
    }

    public function test_user_can_list_alerts(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        SecurityAlert::create([
            'user_id' => $user->id,
            'type' => SecurityAlert::TYPE_NEW_DEVICE_LOGIN,
            'severity' => SecurityAlert::SEVERITY_INFO,
            'title' => 'Test Alert',
            'message' => 'Test message',
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson('/api/v1/security-alerts');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_user_can_get_unread_count(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        SecurityAlert::create([
            'user_id' => $user->id,
            'type' => SecurityAlert::TYPE_NEW_DEVICE_LOGIN,
            'severity' => SecurityAlert::SEVERITY_INFO,
            'title' => 'Unread',
            'message' => 'Unread message',
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson('/api/v1/security-alerts/unread-count');

        $response->assertStatus(200)
            ->assertJson(['count' => 1]);
    }

    public function test_user_can_mark_read(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $alert = SecurityAlert::create([
            'user_id' => $user->id,
            'type' => SecurityAlert::TYPE_NEW_DEVICE_LOGIN,
            'severity' => SecurityAlert::SEVERITY_INFO,
            'title' => 'Test',
            'message' => 'Test',
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/security-alerts/{$alert->id}/read");

        $response->assertStatus(204);
        $alert->refresh();
        $this->assertNotNull($alert->read_at);
    }

    public function test_user_can_mark_all_read(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        SecurityAlert::create([
            'user_id' => $user->id, 'type' => SecurityAlert::TYPE_NEW_DEVICE_LOGIN,
            'severity' => SecurityAlert::SEVERITY_INFO, 'title' => 'A', 'message' => 'A',
        ]);
        SecurityAlert::create([
            'user_id' => $user->id, 'type' => SecurityAlert::TYPE_SUSPICIOUS_ACTIVITY,
            'severity' => SecurityAlert::SEVERITY_WARNING, 'title' => 'B', 'message' => 'B',
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson('/api/v1/security-alerts/read-all');

        $response->assertStatus(204);
        $this->assertSame(0, SecurityAlert::where('user_id', $user->id)->whereNull('read_at')->count());
    }

    public function test_user_can_dismiss_alert(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $alert = SecurityAlert::create([
            'user_id' => $user->id, 'type' => SecurityAlert::TYPE_NEW_DEVICE_LOGIN,
            'severity' => SecurityAlert::SEVERITY_INFO, 'title' => 'Test', 'message' => 'Test',
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->postJson("/api/v1/security-alerts/{$alert->id}/dismiss");

        $response->assertStatus(204);
        $alert->refresh();
        $this->assertNotNull($alert->dismissed_at);
    }

    public function test_user_can_list_devices(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        UserDevice::create([
            'user_id' => $user->id,
            'device_fingerprint' => 'abc123',
            'browser' => 'Chrome',
            'os' => 'Windows',
            'device_type' => 'desktop',
            'ip_address' => '192.168.1.1',
            'last_seen_at' => now(),
            'first_seen_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson('/api/v1/devices');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_user_can_revoke_device(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        $device = UserDevice::create([
            'user_id' => $user->id,
            'device_fingerprint' => 'xyz789',
            'browser' => 'Firefox',
            'os' => 'Linux',
            'device_type' => 'desktop',
            'ip_address' => '10.0.0.1',
            'last_seen_at' => now(),
            'first_seen_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->deleteJson("/api/v1/devices/{$device->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('user_devices', ['id' => $device->id]);
    }

    public function test_alerts_sorted_unread_first(): void
    {
        [$tenant, $user, $token] = $this->setupTenantAndUser();

        // Read alert (created first)
        $readAlert = SecurityAlert::create([
            'user_id' => $user->id, 'type' => SecurityAlert::TYPE_NEW_DEVICE_LOGIN,
            'severity' => SecurityAlert::SEVERITY_INFO, 'title' => 'Read', 'message' => 'Read',
            'read_at' => now(),
        ]);

        // Unread alert (created second)
        $unreadAlert = SecurityAlert::create([
            'user_id' => $user->id, 'type' => SecurityAlert::TYPE_SUSPICIOUS_ACTIVITY,
            'severity' => SecurityAlert::SEVERITY_WARNING, 'title' => 'Unread', 'message' => 'Unread',
        ]);

        $response = $this->withHeaders($this->authHeaders($token, $tenant->id))
            ->getJson('/api/v1/security-alerts');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertSame('Unread', $data[0]['title']);
    }
}
