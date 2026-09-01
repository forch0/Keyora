<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

class RegisterUserAction
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array{0: User, 1: string}
     */
    public function __invoke(array $attributes): array
    {
        $user = User::create([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'password' => $attributes['password'],
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return [$user, $token];
    }
}
