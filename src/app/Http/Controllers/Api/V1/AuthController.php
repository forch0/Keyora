<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\ChangePasswordAction;
use App\Actions\LoginUserAction;
use App\Actions\RegisterUserAction;
use App\Actions\RequestPasswordResetAction;
use App\Actions\ResetPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function __construct(
        private readonly RegisterUserAction $registerUser,
        private readonly LoginUserAction $loginUser,
        private readonly ChangePasswordAction $changePassword,
        private readonly RequestPasswordResetAction $requestPasswordReset,
        private readonly ResetPasswordAction $resetPassword,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        [$user, $token] = ($this->registerUser)($request->validated());

        $this->activityLogger->log('auth.register', $user);

        return (new UserResource($user))
            ->additional(['token' => $token])
            ->response()
            ->setStatusCode(201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = ($this->loginUser)(
            $request->validated('email'),
            $request->validated('password'),
        );

        if ($result === null) {
            return response()->json([
                'error' => [
                    'code' => 'AUTH_INVALID_CREDENTIALS',
                    'message' => 'Invalid email or password.',
                ],
            ], 422);
        }

        [$user, $token] = $result;

        $this->activityLogger->log('auth.login', $user);

        return (new UserResource($user))
            ->additional(['token' => $token])
            ->response()
            ->setStatusCode(200);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $user->currentAccessToken()->delete();

        $this->activityLogger->log('auth.logout', $user);

        return response()->json(null, 204);
    }

    public function me(Request $request): JsonResponse
    {
        return (new UserResource($this->authenticatedUser($request)))->response();
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $user->update($request->validated());

        return (new UserResource($user->fresh()))->response();
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $changed = ($this->changePassword)(
            $user,
            $request->validated('current_password'),
            $request->validated('password'),
        );

        if (! $changed) {
            return response()->json([
                'error' => [
                    'code' => 'AUTH_INVALID_CURRENT_PASSWORD',
                    'message' => 'The current password is incorrect.',
                    'errors' => [
                        'current_password' => ['The current password is incorrect.'],
                    ],
                ],
            ], 422);
        }

        $this->activityLogger->log('auth.password_changed', $user);

        return (new UserResource($user->fresh()))->response();
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        ($this->requestPasswordReset)($request->validated('email'));

        return response()->json([
            'data' => [
                'message' => 'If the email exists, a password reset link has been sent.',
            ],
        ], 200);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = ($this->resetPassword)(
            $request->validated('email'),
            $request->validated('token'),
            $request->validated('password'),
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'data' => [
                    'message' => 'Password has been reset successfully.',
                ],
            ], 200);
        }

        return response()->json([
            'error' => [
                'code' => 'AUTH_PASSWORD_RESET_FAILED',
                'message' => 'The password reset failed.',
                'errors' => [
                    'token' => [__($status)],
                ],
            ],
        ], 422);
    }

    /**
     * Get the authenticated user or throw.
     *
     * The auth:sanctum middleware guarantees a user, but PHPStan needs help
     * understanding that $request->user() is non-null in protected routes.
     */
    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Unauthenticated.');
        }

        return $user;
    }
}
