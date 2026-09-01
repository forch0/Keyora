<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\TenantMemberController;
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

    // Tenant (workspace) endpoints — all require authentication.
    // index/store are tenant-agnostic; show/update/destroy resolve tenant
    // from the route param and enforce membership via TenantPolicy.
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('tenants', [TenantController::class, 'index']);
        Route::post('tenants', [TenantController::class, 'store']);
        Route::middleware('tenant.resolve')->group(function (): void {
            Route::get('tenants/{tenant}', [TenantController::class, 'show'])
                ->middleware('can:view,tenant');
            Route::put('tenants/{tenant}', [TenantController::class, 'update'])
                ->middleware('can:update,tenant');
            Route::delete('tenants/{tenant}', [TenantController::class, 'destroy'])
                ->middleware('can:delete,tenant');

            // Tenant member management — nested under tenants/{tenant}.
            // Policy enforcement is done inside the controller via
            // $this->authorize() calls, because the policy methods need
            // both the Tenant and the target User, and the can: middleware
            // would resolve to TenantPolicy (not TenantMemberPolicy).
            Route::get('tenants/{tenant}/members', [TenantMemberController::class, 'index']);
            Route::post('tenants/{tenant}/members/invite', [TenantMemberController::class, 'invite']);
            Route::post('tenants/{tenant}/members/accept', [TenantMemberController::class, 'accept']);
            Route::get('tenants/{tenant}/members/{user}', [TenantMemberController::class, 'show']);
            Route::put('tenants/{tenant}/members/{user}', [TenantMemberController::class, 'update']);
            Route::post('tenants/{tenant}/members/{user}/suspend', [TenantMemberController::class, 'suspend']);
            Route::post('tenants/{tenant}/members/{user}/restore', [TenantMemberController::class, 'restore']);
            Route::delete('tenants/{tenant}/members/{user}', [TenantMemberController::class, 'destroy']);
            Route::get('tenants/{tenant}/invitations', [TenantMemberController::class, 'invitations']);
            Route::delete('tenants/{tenant}/invitations/{invitation}', [TenantMemberController::class, 'cancelInvitation']);
        });
    });
});
