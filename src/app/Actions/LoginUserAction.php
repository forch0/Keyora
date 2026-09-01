<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginUserAction
{
    /**
     * Attempt to authenticate a user with email and password.
     *
     * @return array{0: User, 1: string}|null Returns [user, token] on success, null on failure.
     */
    public function __invoke(string $email, string $password): ?array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return [$user, $token];
    }
}
