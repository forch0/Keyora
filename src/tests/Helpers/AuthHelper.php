<?php

namespace Tests\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

trait AuthHelper
{
    protected function createUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ], $attributes));
    }

    protected function createAndAuthUser(array $attributes = []): array
    {
        $user = $this->createUser($attributes);
        $token = $user->createToken('test-token')->plainTextToken;

        return [$user, $token];
    }

    protected function authHeaders(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }
}
