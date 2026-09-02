<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Tools;

use App\Services\PasswordGenerator;
use App\Services\PasswordStrengthChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AuthHelper;
use Tests\TestCase;

class PasswordToolTest extends TestCase
{
    use AuthHelper, RefreshDatabase;

    private function authHeaders(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_generate_default_password(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/tools/password/generate');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['password', 'options' => ['length', 'uppercase', 'lowercase', 'numbers', 'symbols']],
            ]);

        $password = $response->json('data.password');
        $this->assertSame(16, strlen($password));
    }

    public function test_generate_custom_length(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/tools/password/generate', [
                'length' => 32,
            ]);

        $response->assertStatus(200);
        $this->assertSame(32, strlen($response->json('data.password')));
    }

    public function test_generate_exclude_symbols(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/tools/password/generate', [
                'symbols' => false,
                'length' => 20,
            ]);

        $response->assertStatus(200);
        $password = $response->json('data.password');
        // No symbol characters should be present
        $this->assertSame(0, preg_match_all('/[^A-Za-z0-9]/', $password));
    }

    public function test_generate_exclude_similar(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/tools/password/generate', [
                'exclude_similar' => true,
                'length' => 50,
            ]);

        $response->assertStatus(200);
        $password = $response->json('data.password');
        // Should not contain 0, O, 1, l, I
        $this->assertSame(0, preg_match_all('/[0O1lI]/', $password));
    }

    public function test_generate_min_character_counts(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/tools/password/generate', [
                'length' => 20,
                'min_uppercase' => 3,
                'min_lowercase' => 3,
                'min_numbers' => 3,
                'min_symbols' => 3,
            ]);

        $response->assertStatus(200);
        $password = $response->json('data.password');

        $this->assertGreaterThanOrEqual(3, preg_match_all('/[A-Z]/', $password));
        $this->assertGreaterThanOrEqual(3, preg_match_all('/[a-z]/', $password));
        $this->assertGreaterThanOrEqual(3, preg_match_all('/[0-9]/', $password));
        $this->assertGreaterThanOrEqual(3, preg_match_all('/[^A-Za-z0-9]/', $password));
    }

    public function test_generate_requires_one_type(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/tools/password/generate', [
                'uppercase' => false,
                'lowercase' => false,
                'numbers' => false,
                'symbols' => false,
            ]);

        $response->assertStatus(422);
    }

    public function test_strength_weak_password(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/tools/password/strength', [
                'password' => 'password',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['score', 'strength', 'entropy', 'criteria', 'suggestions'],
            ]);

        $this->assertSame('very_weak', $response->json('data.strength'));
    }

    public function test_strength_strong_password(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $generator = app(PasswordGenerator::class);
        $strongPassword = $generator->generate(['length' => 24, 'min_symbols' => 2]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/tools/password/strength', [
                'password' => $strongPassword,
            ]);

        $response->assertStatus(200);
        $strength = $response->json('data.strength');
        $this->assertContains($strength, ['strong', 'very_strong']);
    }

    public function test_strength_returns_entropy(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/tools/password/strength', [
                'password' => 'MyStr0ng!Pass',
            ]);

        $response->assertStatus(200);
        $this->assertIsFloat($response->json('data.entropy'));
        $this->assertGreaterThan(0, $response->json('data.entropy'));
    }

    public function test_strength_returns_suggestions(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/tools/password/strength', [
                'password' => 'abc',
            ]);

        $response->assertStatus(200);
        $suggestions = $response->json('data.suggestions');
        $this->assertNotEmpty($suggestions);
    }

    public function test_strength_common_password_detected(): void
    {
        [$user, $token] = $this->createAndAuthUser();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/tools/password/strength', [
                'password' => '123456',
            ]);

        $response->assertStatus(200);
        $this->assertSame('very_weak', $response->json('data.strength'));
        $this->assertFalse($response->json('data.criteria.no_common_patterns'));
    }

    public function test_strength_sequential_penalty(): void
    {
        $checker = app(PasswordStrengthChecker::class);

        $sequentialResult = $checker->check('abcdefghij');
        $randomResult = $checker->check('a7b3c9d2e5');

        // Sequential password should score lower than a random one of similar length
        $this->assertLessThan($randomResult['score'], $sequentialResult['score']);
    }
}
