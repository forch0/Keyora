<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AuthHelper;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use AuthHelper, RefreshDatabase;

    public function test_authenticated_user_can_get_profile(): void
    {
        [$user, $token] = $this->createAndAuthUser([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', 'John Doe')
            ->assertJsonPath('data.email', 'john@example.com');
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_user_can_update_profile(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->putJson('/api/v1/auth/me', [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.email', 'updated@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_user_cannot_update_email_to_one_already_in_use(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        [$user, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->putJson('/api/v1/auth/me', [
                'name' => $user->name,
                'email' => 'taken@example.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_profile_response_never_exposes_password(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/auth/me');

        $response->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.two_factor_secret')
            ->assertJsonMissingPath('data.remember_token');
    }
}
