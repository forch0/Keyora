<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Notifications\PasswordResetNotification;
use Illuminate\Support\Facades\Password;

class RequestPasswordResetAction
{
    /**
     * Create a password reset token and send the reset notification.
     *
     * Always returns true (even if the user doesn't exist) to avoid
     * leaking which emails are registered.
     */
    public function __invoke(string $email): bool
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            return true;
        }

        $token = Password::getRepository()->create($user);

        $user->notify(new PasswordResetNotification($token));

        return true;
    }
}
