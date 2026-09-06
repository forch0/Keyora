<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Get the authenticated user or abort with 401.
     *
     * The auth:sanctum middleware guarantees a user on protected routes,
     * but this helper provides a non-nullable User return type for
     * PHPStan and consistent null-safety across controllers.
     */
    protected function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Unauthenticated.');
        }

        return $user;
    }
}
