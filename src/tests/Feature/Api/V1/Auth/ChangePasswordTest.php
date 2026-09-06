<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AuthHelper;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use AuthHelper, RefreshDatabase;

    public function test_user_can_change_password(): void
    {
        [$user, $token] = $this->createAndAuthUser([
            'password' => 'old-password',
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/auth/password', [
                'current_password' => 'old-password',
                'password' => 'new-password123',
                'password_confirmation' => 'new-password123',
            ]);

        $response->assertStatus(200);

        $user->refresh();

        $this->assertTrue(password_verify('new-password123', $user->password));
        $this->assertFalse(password_verify('old-password', $user->password));
    }

    public function test_user_cannot_change_password_with_wrong_current(): void
    {
        [$user, $token] = $this->createAndAuthUser([
            'password' => 'old-password',
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/auth/password', [
                'current_password' => 'wrong-current',
                'password' => 'new-password123',
                'password_confirmation' => 'new-password123',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'AUTH_INVALID_CURRENT_PASSWORD');
    }

    public function test_change_password_requires_confirmation(): void
    {
        [$user, $token] = $this->createAndAuthUser([
            'password' => 'old-password',
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/auth/password', [
                'current_password' => 'old-password',
                'password' => 'new-password123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_change_password_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/auth/password', [
            'current_password' => 'old-password',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(401);
    }
}
