<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\ReauthenticationService;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class RequireReauthentication
{
    public function __construct(
        private readonly ReauthenticationService $reauthenticationService,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401, 'Unauthenticated.');
        }

        // If the current token was created within 15 minutes, consider the
        // user recently authenticated (login itself is a form of re-auth).
        /** @var PersonalAccessToken|null $token */
        $token = $user->currentAccessToken();
        if ($token !== null && $token->created_at !== null) {
            $tokenAge = (int) now()->timestamp - (int) $token->created_at->timestamp;
            if ($tokenAge < 900) {
                return $next($request);
            }
        }

        if (! $this->reauthenticationService->isAuthenticated($user)) {
            return response()->json([
                'error' => [
                    'code' => 'REAUTH_REQUIRED',
                    'message' => 'Re-authentication required for this action.',
                ],
            ], 423);
        }

        return $next($request);
    }
}
