<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/', fn () => response()->json(['status' => 'ok']));

    Route::prefix('auth')->group(function (): void {
        // Rate-limited public auth endpoints
        Route::middleware(['throttle:5,1'])->group(function (): void {
            Route::post('register', [AuthController::class, 'register']);
            Route::post('login', [AuthController::class, 'login']);
        });

        Route::middleware(['throttle:3,1'])->group(function (): void {
            Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        });

        Route::post('reset-password', [AuthController::class, 'resetPassword']);

        // Authenticated endpoints
        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::put('me', [AuthController::class, 'updateProfile']);
            Route::post('password', [AuthController::class, 'changePassword']);
        });
    });
});
