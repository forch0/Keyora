<?php

use App\Http\Controllers\Api\V1\AccessGrantController;
use App\Http\Controllers\Api\V1\AccessRequestController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EmergencyRevokeController;
use App\Http\Controllers\Api\V1\FileAccessController;
use App\Http\Controllers\Api\V1\FileFolderController;
use App\Http\Controllers\Api\V1\NoteAccessController;
use App\Http\Controllers\Api\V1\NoteFolderController;
use App\Http\Controllers\Api\V1\OrgVaultItemController;
use App\Http\Controllers\Api\V1\PasswordToolController;
use App\Http\Controllers\Api\V1\PersonalVaultFolderController;
use App\Http\Controllers\Api\V1\PersonalVaultItemController;
use App\Http\Controllers\Api\V1\PersonalVaultTagController;
use App\Http\Controllers\Api\V1\PublicLinkController;
use App\Http\Controllers\Api\V1\SecureFileController;
use App\Http\Controllers\Api\V1\SecureLinkController;
use App\Http\Controllers\Api\V1\SecureNoteController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\TeamMemberController;
use App\Http\Controllers\Api\V1\TeamVaultItemController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\TenantMemberController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/', fn () => response()->json(['status' => 'ok']));

    // Public secure link access (no auth required — for external users)
    Route::prefix('s/{uuid}')->name('api.v1.public-link.')->group(function (): void {
        Route::get('/', [PublicLinkController::class, 'show'])->name('show');
        Route::post('/verify', [PublicLinkController::class, 'verify']);
        Route::post('/email-verify', [PublicLinkController::class, 'sendEmailVerification']);
        Route::post('/email-confirm', [PublicLinkController::class, 'confirmEmailVerification']);
        Route::get('/resource', [PublicLinkController::class, 'resource'])->name('resource');
    });

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
            Route::post('tenants/{tenant}/members/{user}/revoke-all', [EmergencyRevokeController::class, 'revokeAllForUser']);
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

    // Access grants — view who has access to a vault item
    Route::middleware(['auth:sanctum', 'tenant.resolve'])->prefix('vault/items')->group(function (): void {
        Route::get('{item}/access', [AccessGrantController::class, 'index']);
        Route::get('{item}/access/summary', [AccessGrantController::class, 'summary']);
        Route::get('{item}/access/countdown', [AccessGrantController::class, 'countdown']);
        Route::post('{item}/access', [AccessGrantController::class, 'store']);
        Route::post('{item}/access/bulk', [AccessGrantController::class, 'bulkStore']);
        Route::put('{item}/access/{grant}', [AccessGrantController::class, 'update']);
        Route::delete('{item}/access/{grant}', [AccessGrantController::class, 'destroy']);

        // Bulk revocation (Module 16)
        Route::post('{item}/access/revoke-all', [EmergencyRevokeController::class, 'revokeAllForResource']);
        Route::post('{item}/access/revoke-team/{team}', [EmergencyRevokeController::class, 'revokeTeamAccess']);

        // Secure share links (Module 17)
        Route::get('{item}/share-links', [SecureLinkController::class, 'indexForVaultItem']);
        Route::post('{item}/share-links', [SecureLinkController::class, 'storeForVaultItem']);
    });

    // Password tools — generator and strength checker
    Route::middleware('auth:sanctum')->prefix('tools/password')->group(function (): void {
        Route::post('generate', [PasswordToolController::class, 'generate']);
        Route::post('strength', [PasswordToolController::class, 'checkStrength']);
    });

    // Secure files — upload, list, download, replace, archive, restore, delete
    Route::middleware(['auth:sanctum', 'tenant.resolve'])->prefix('files')->group(function (): void {
        Route::get('/', [SecureFileController::class, 'index']);
        Route::post('/', [SecureFileController::class, 'store']);
        Route::post('/bulk', [SecureFileController::class, 'bulkStore']);
        Route::get('/{file}', [SecureFileController::class, 'show']);
        Route::get('/{file}/download', [SecureFileController::class, 'download']);
        Route::put('/{file}', [SecureFileController::class, 'update']);
        Route::post('/{file}/replace', [SecureFileController::class, 'replace']);
        Route::delete('/{file}', [SecureFileController::class, 'destroy']);
        Route::post('/{file}/archive', [SecureFileController::class, 'archive']);
        Route::post('/{file}/restore', [SecureFileController::class, 'restore']);

        Route::get('/folders', [FileFolderController::class, 'index']);
        Route::post('/folders', [FileFolderController::class, 'store']);
        Route::put('/folders/{folder}', [FileFolderController::class, 'update']);
        Route::delete('/folders/{folder}', [FileFolderController::class, 'destroy']);

        // File access grants — sharing
        Route::get('/{file}/access', [FileAccessController::class, 'index']);
        Route::post('/{file}/access', [FileAccessController::class, 'store']);
        Route::put('/{file}/access/{grant}', [FileAccessController::class, 'update']);
        Route::delete('/{file}/access/{grant}', [FileAccessController::class, 'destroy']);
        Route::post('/{file}/access/revoke-all', [EmergencyRevokeController::class, 'revokeAllForFile']);

        // Secure share links (Module 17)
        Route::get('/{file}/share-links', [SecureLinkController::class, 'indexForFile']);
        Route::post('/{file}/share-links', [SecureLinkController::class, 'storeForFile']);
    });

    // Secure notes — encrypted notes with sharing, folders, tags, search
    Route::middleware(['auth:sanctum', 'tenant.resolve'])->prefix('notes')->group(function (): void {
        Route::get('/', [SecureNoteController::class, 'index']);
        Route::post('/', [SecureNoteController::class, 'store']);
        Route::get('/search', [SecureNoteController::class, 'search']);
        Route::get('/{note}', [SecureNoteController::class, 'show']);
        Route::put('/{note}', [SecureNoteController::class, 'update']);
        Route::delete('/{note}', [SecureNoteController::class, 'destroy']);
        Route::post('/{note}/pin', [SecureNoteController::class, 'togglePin']);

        Route::get('/{note}/access', [NoteAccessController::class, 'index']);
        Route::post('/{note}/access', [NoteAccessController::class, 'store']);
        Route::put('/{note}/access/{grant}', [NoteAccessController::class, 'update']);
        Route::delete('/{note}/access/{grant}', [NoteAccessController::class, 'destroy']);
        Route::post('/{note}/access/revoke-all', [EmergencyRevokeController::class, 'revokeAllForNote']);

        // Secure share links (Module 17)
        Route::get('/{note}/share-links', [SecureLinkController::class, 'indexForNote']);
        Route::post('/{note}/share-links', [SecureLinkController::class, 'storeForNote']);

        Route::get('/folders', [NoteFolderController::class, 'index']);
        Route::post('/folders', [NoteFolderController::class, 'store']);
        Route::put('/folders/{folder}', [NoteFolderController::class, 'update']);
        Route::delete('/folders/{folder}', [NoteFolderController::class, 'destroy']);
    });

    // Access requests — request workflow for resources users don't have access to
    Route::middleware(['auth:sanctum', 'tenant.resolve'])->prefix('access-requests')->group(function (): void {
        Route::get('/', [AccessRequestController::class, 'index']);
        Route::post('/', [AccessRequestController::class, 'store']);
        Route::get('/history', [AccessRequestController::class, 'history']);
        Route::get('/{accessRequest}', [AccessRequestController::class, 'show']);
        Route::put('/{accessRequest}/approve', [AccessRequestController::class, 'approve']);
        Route::put('/{accessRequest}/reject', [AccessRequestController::class, 'reject']);
        Route::delete('/{accessRequest}', [AccessRequestController::class, 'destroy']);
    });

    // Secure share links — revoke and activity (Module 17/18)
    Route::middleware(['auth:sanctum', 'tenant.resolve'])->prefix('share-links')->group(function (): void {
        Route::delete('/{link}', [SecureLinkController::class, 'destroy']);
        Route::get('/{link}/activity', [SecureLinkController::class, 'activity']);
    });
});
