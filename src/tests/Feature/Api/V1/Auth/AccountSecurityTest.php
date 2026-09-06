<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use App\Notifications\RecoveryCodesRegenerated;
use App\Notifications\TwoFactorDisabled;
use App\Notifications\TwoFactorEnabled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\Helpers\AuthHelper;
use Tests\Helpers\TenantHelper;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use AuthHelper, RefreshDatabase, TenantHelper;

    /**
     * Generate a valid TOTP code for the given secret.
     */
    private function generateTotpCode(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $key = '';
        $bits = 0;
        $value = 0;
        for ($i = 0, $len = strlen($secret); $i < $len; $i++) {
            $char = strtoupper($secret[$i]);
            $pos = strpos($alphabet, $char);
            if ($pos === false) {
                continue;
            }
            $value = ($value << 5) | $pos;
            $bits += 5;
            if ($bits >= 8) {
                $key .= chr(($value >> ($bits - 8)) & 0xFF);
                $bits -= 8;
            }
        }

        $window = intdiv(time(), 30);
        $time = pack('N', 0).pack('N', $window);
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF)
        ) % 1000000;

        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Enable and confirm 2FA for a user, returning recovery codes.
     *
     * @return array{user: User, token: string, secret: string, recovery_codes: array<int, string>}
     */
    private function enable2FA(User $user): array
    {
        $token = $user->createToken('test-token')->plainTextToken;

        // Enable
        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/auth/2fa/enable');
        $secret = $response->json('data.secret');

        // Confirm with valid code
        $code = $this->generateTotpCode($secret);
        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/auth/2fa/confirm', ['code' => $code]);
        $recoveryCodes = $response->json('data.recovery_codes');

        return ['user' => $user, 'token' => $token, 'secret' => $secret, 'recovery_codes' => $recoveryCodes];
    }

    public function test_user_can_enable_2fa(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/auth/2fa/enable');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['secret', 'qr_code_uri']]);
    }

    public function test_user_can_confirm_2fa(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/auth/2fa/enable');
        $secret = $response->json('data.secret');

        $code = $this->generateTotpCode($secret);
        Notification::fake();
        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/auth/2fa/confirm', ['code' => $code]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['recovery_codes']]);

        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
        Notification::assertSentTo($user, TwoFactorEnabled::class);
    }

    public function test_login_requires_2fa_code(): void
    {
        $password = 'password123';
        $user = $this->createUser(['password' => Hash::make($password)]);
        $result = $this->enable2FA($user);
        $user = $result['user'];

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.requires_2fa', true)
            ->assertJsonStructure(['data' => ['requires_2fa', '2fa_token']]);
    }

    public function test_2fa_code_verification_works(): void
    {
        $password = 'password123';
        $user = $this->createUser(['password' => Hash::make($password)]);
        $result = $this->enable2FA($user);
        $secret = $result['secret'];

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);
        $tempToken = $loginResponse->json('data.2fa_token');

        $code = $this->generateTotpCode($secret);
        $response = $this->postJson('/api/v1/auth/2fa/verify', [
            '2fa_token' => $tempToken,
            'code' => $code,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'email'], 'token']);
    }

    public function test_wrong_2fa_code_rejected(): void
    {
        $password = 'password123';
        $user = $this->createUser(['password' => Hash::make($password)]);
        $this->enable2FA($user);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);
        $tempToken = $loginResponse->json('data.2fa_token');

        $response = $this->postJson('/api/v1/auth/2fa/verify', [
            '2fa_token' => $tempToken,
            'code' => '000000',
        ]);

        $response->assertStatus(422);
    }

    public function test_recovery_code_works_once(): void
    {
        $password = 'password123';
        $user = $this->createUser(['password' => Hash::make($password)]);
        $result = $this->enable2FA($user);
        $recoveryCodes = $result['recovery_codes'];

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);
        $tempToken = $loginResponse->json('data.2fa_token');

        $response = $this->postJson('/api/v1/auth/2fa/verify', [
            '2fa_token' => $tempToken,
            'recovery_code' => $recoveryCodes[0],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'email'], 'token']);

        // Verify the code is consumed — try using it again
        $loginResponse2 = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);
        $tempToken2 = $loginResponse2->json('data.2fa_token');

        $response2 = $this->postJson('/api/v1/auth/2fa/verify', [
            '2fa_token' => $tempToken2,
            'recovery_code' => $recoveryCodes[0],
        ]);

        $response2->assertStatus(422);
    }

    public function test_user_can_disable_2fa(): void
    {
        Notification::fake();
        $password = 'password123';
        $user = $this->createUser(['password' => Hash::make($password)]);
        $result = $this->enable2FA($user);
        $token = $result['token'];

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/auth/2fa/disable', ['password' => $password]);

        $response->assertStatus(204);
        $this->assertNull($user->fresh()->two_factor_secret);
        $this->assertNull($user->fresh()->two_factor_confirmed_at);
        Notification::assertSentTo($user, TwoFactorDisabled::class);
    }

    public function test_cannot_disable_2fa_without_password(): void
    {
        $password = 'password123';
        $user = $this->createUser(['password' => Hash::make($password)]);
        $result = $this->enable2FA($user);
        $token = $result['token'];

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/auth/2fa/disable', ['password' => 'wrong-password']);

        $response->assertStatus(422);
        $this->assertNotNull($user->fresh()->two_factor_secret);
    }

    public function test_recovery_codes_can_be_regenerated(): void
    {
        Notification::fake();
        $password = 'password123';
        $user = $this->createUser(['password' => Hash::make($password)]);
        $result = $this->enable2FA($user);
        $token = $result['token'];
        $oldCodes = $result['recovery_codes'];

        // Re-authenticate first
        $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/auth/reauthenticate', ['password' => $password]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/auth/2fa/recovery-codes');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['recovery_codes']]);

        $newCodes = $response->json('data.recovery_codes');
        $this->assertNotEquals($oldCodes, $newCodes);
        Notification::assertSentTo($user, RecoveryCodesRegenerated::class);
    }

    public function test_sensitive_action_requires_reauth(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant();
        $this->attachUserToTenant($user, $tenant, 'admin');
        $this->setupTenantContext($tenant);

        $member = $this->createUser(['email' => 'member@example.com']);
        $this->attachUserToTenant($member, $tenant, 'member');

        // Make the token old enough to require re-auth (>15 min)
        $user->tokens()->update(['created_at' => now()->subMinutes(20)]);

        // Without re-auth, offboarding should return 423
        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}/offboard");

        $response->assertStatus(423);
    }

    public function test_reauth_resets_timer(): void
    {
        Notification::fake();
        [$user, $token] = $this->createAndAuthUser();
        $tenant = $this->createTenant();
        $this->attachUserToTenant($user, $tenant, 'admin');
        $this->setupTenantContext($tenant);

        $member = $this->createUser(['email' => 'member@example.com', 'password' => Hash::make('password123')]);
        $this->attachUserToTenant($member, $tenant, 'member');

        // Make the token old enough to require re-auth
        $user->tokens()->update(['created_at' => now()->subMinutes(20)]);

        // Re-authenticate
        $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/auth/reauthenticate', ['password' => 'password'])
            ->assertStatus(200);

        // Now offboarding should work
        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/tenants/{$tenant->id}/members/{$member->id}/offboard");

        $response->assertStatus(204);
    }

    public function test_reauth_wrong_password(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/auth/reauthenticate', ['password' => 'wrong-password']);

        $response->assertStatus(422);
    }

    public function test_logout_all_revokes_tokens(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $user->createToken('second-device');
        $user->createToken('third-device');

        $this->assertDatabaseCount('personal_access_tokens', 3);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/auth/logout-all');

        $response->assertStatus(204);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_2fa_secret_encrypted(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/auth/2fa/enable');
        $secret = $response->json('data.secret');

        $rawValue = $user->fresh()->getAttributes()['two_factor_secret'];

        // The raw DB value should be encrypted (not equal to the plain secret)
        $this->assertNotEquals($secret, $rawValue);

        // Decrypting should give back the original secret
        $this->assertSame($secret, Crypt::decryptString($rawValue));
    }

    public function test_recovery_codes_encrypted(): void
    {
        $password = 'password123';
        $user = $this->createUser(['password' => Hash::make($password)]);
        $result = $this->enable2FA($user);
        $recoveryCodes = $result['recovery_codes'];

        $rawValue = $user->fresh()->getAttributes()['two_factor_recovery_codes'];

        // The raw DB value should be encrypted
        $decrypted = Crypt::decryptString($rawValue);
        $storedHashes = json_decode($decrypted, true);

        // Each stored code should be a bcrypt hash, not the plain code
        foreach ($storedHashes as $index => $hash) {
            if ($index < count($recoveryCodes)) {
                $this->assertTrue(Hash::check($recoveryCodes[$index], $hash));
            }
        }
    }
}
