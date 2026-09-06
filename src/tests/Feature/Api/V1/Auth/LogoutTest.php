<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Helpers\AuthHelper;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use AuthHelper, RefreshDatabase;

    public function test_user_can_logout(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(204);

        // Reset guard state — RequestGuard caches the user between requests
        Auth::forgetGuards();

        // Subsequent requests with the revoked token should return 401
        $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }
}
