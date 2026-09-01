<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\OrgVaultItemController;
use App\Http\Controllers\Api\V1\PersonalVaultFolderController;
use App\Http\Controllers\Api\V1\PersonalVaultItemController;
use App\Http\Controllers\Api\V1\PersonalVaultTagController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\TeamMemberController;
use App\Http\Controllers\Api\V1\TeamVaultItemController;
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

    // Personal vault — user's private encrypted storage.
    // NOT tenant-scoped; items belong to the user directly (user_id).
    Route::middleware('auth:sanctum')->prefix('vault')->group(function (): void {
        // Vault items — core CRUD (Module 05) + organization endpoints (Module 06)
        Route::get('items', [PersonalVaultItemController::class, 'index']);
        Route::post('items', [PersonalVaultItemController::class, 'store']);
        Route::get('items/recent', [PersonalVaultItemController::class, 'recent']);
        Route::get('items/favorites', [PersonalVaultItemController::class, 'favorites']);
        Route::get('items/archived', [PersonalVaultItemController::class, 'archived']);
        Route::get('items/{item}', [PersonalVaultItemController::class, 'show']);
        Route::put('items/{item}', [PersonalVaultItemController::class, 'update']);
        Route::delete('items/{item}', [PersonalVaultItemController::class, 'destroy']);
        Route::post('items/{item}/favorite', [PersonalVaultItemController::class, 'toggleFavorite']);
        Route::post('items/{item}/archive', [PersonalVaultItemController::class, 'archive']);
        Route::post('items/{item}/restore', [PersonalVaultItemController::class, 'restore']);

        // Search
        Route::get('search', [PersonalVaultItemController::class, 'search']);

        // Folders
        Route::apiResource('folders', PersonalVaultFolderController::class);

        // Tags
        Route::apiResource('tags', PersonalVaultTagController::class);
    });

    // Teams & team/org vaults — tenant-scoped, require tenant context.
    Route::middleware(['auth:sanctum', 'tenant.resolve'])->group(function (): void {
        // Teams CRUD
        Route::get('tenants/{tenant}/teams', [TeamController::class, 'index']);
        Route::post('tenants/{tenant}/teams', [TeamController::class, 'store']);
        Route::get('tenants/{tenant}/teams/{team}', [TeamController::class, 'show']);
        Route::put('tenants/{tenant}/teams/{team}', [TeamController::class, 'update']);
        Route::delete('tenants/{tenant}/teams/{team}', [TeamController::class, 'destroy']);

        // Team members
        Route::get('tenants/{tenant}/teams/{team}/members', [TeamMemberController::class, 'index']);
        Route::post('tenants/{tenant}/teams/{team}/members', [TeamMemberController::class, 'store']);
        Route::put('tenants/{tenant}/teams/{team}/members/{user}', [TeamMemberController::class, 'update']);
        Route::delete('tenants/{tenant}/teams/{team}/members/{user}', [TeamMemberController::class, 'destroy']);

        // Team vault items
        Route::get('tenants/{tenant}/teams/{team}/vault/items', [TeamVaultItemController::class, 'index']);
        Route::post('tenants/{tenant}/teams/{team}/vault/items', [TeamVaultItemController::class, 'store']);
        Route::get('tenants/{tenant}/teams/{team}/vault/items/{item}', [TeamVaultItemController::class, 'show']);
        Route::put('tenants/{tenant}/teams/{team}/vault/items/{item}', [TeamVaultItemController::class, 'update']);
        Route::delete('tenants/{tenant}/teams/{team}/vault/items/{item}', [TeamVaultItemController::class, 'destroy']);

        // Org-wide vault items (team_id = null)
        Route::get('tenants/{tenant}/vault/items', [OrgVaultItemController::class, 'index']);
        Route::post('tenants/{tenant}/vault/items', [OrgVaultItemController::class, 'store']);
        Route::get('tenants/{tenant}/vault/items/{item}', [OrgVaultItemController::class, 'show']);
        Route::put('tenants/{tenant}/vault/items/{item}', [OrgVaultItemController::class, 'update']);
        Route::delete('tenants/{tenant}/vault/items/{item}', [OrgVaultItemController::class, 'destroy']);
    });
});
