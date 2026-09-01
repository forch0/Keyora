<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordAction
{
    /**
     * Reset a user's password using a reset token.
     *
     * @return string The password broker status constant.
     */
    public function __invoke(string $email, string $token, string $password): string
    {
        return Password::reset(
            [
                'email' => $email,
                'token' => $token,
                'password' => $password,
                'password_confirmation' => $password,
            ],
            function (User $user, string $newPassword): void {
                $user->forceFill([
                    'password' => Hash::make($newPassword),
                ])->setRememberToken(Str::random(60));

                $user->save();
            }
        );
    }
}
