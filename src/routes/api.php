<?php

use App\Http\Controllers\Api\V1\AccessGrantController;
use App\Http\Controllers\Api\V1\AccessRequestController;
use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BulkOperationController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\EmergencyRevokeController;
use App\Http\Controllers\Api\V1\FileAccessController;
use App\Http\Controllers\Api\V1\FileFolderController;
use App\Http\Controllers\Api\V1\HealthCheckController;
use App\Http\Controllers\Api\V1\NoteAccessController;
use App\Http\Controllers\Api\V1\NoteFolderController;
use App\Http\Controllers\Api\V1\OrgVaultItemController;
use App\Http\Controllers\Api\V1\PasswordToolController;
use App\Http\Controllers\Api\V1\PersonalVaultFolderController;
use App\Http\Controllers\Api\V1\PersonalVaultItemController;
use App\Http\Controllers\Api\V1\PersonalVaultTagController;
use App\Http\Controllers\Api\V1\PublicLinkController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SecureFileController;
use App\Http\Controllers\Api\V1\SecureLinkController;
use App\Http\Controllers\Api\V1\SecureNoteController;
use App\Http\Controllers\Api\V1\SecurityAlertController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\TeamMemberController;
use App\Http\Controllers\Api\V1\TeamVaultItemController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\TenantMemberController;
use App\Http\Controllers\Api\V1\TwoFactorController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/', fn () => response()->json(['status' => 'ok']));

    // Health check — no auth required (for load balancers / monitoring)
    Route::get('health', HealthCheckController::class);

    // Public secure link access (no auth required — for external users)
    Route::prefix('s/{uuid}')->name('api.v1.public-link.')->group(function (): void {
        Route::get('/', [PublicLinkController::class, 'show'])->name('show');
        Route::post('/verify', [PublicLinkController::class, 'verify']);
        Route::post('/email-verify', [PublicLinkController::class, 'sendEmailVerification']);
        Route::post('/email-confirm', [PublicLinkController::class, 'confirmEmailVerification']);
        Route::get('/resource', [PublicLinkController::class, 'resource'])->name('resource');
    });

    Route::prefix('auth')->group(function (): void {
        // Rate-limited public auth endpoints (IP-based)
        Route::post('register', [AuthController::class, 'register'])
            ->middleware('rate.limit:auth.register');
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('rate.limit:auth.login');

        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
            ->middleware('rate.limit:auth.forgot_password');

        Route::post('reset-password', [AuthController::class, 'resetPassword'])
            ->middleware('rate.limit:auth.forgot_password');

        // Authenticated endpoints
        Route::middleware(['auth:sanctum', 'rate.limit:read'])->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout'])
                ->middleware('rate.limit:write');
            Route::post('logout-all', [AuthController::class, 'logoutAll'])
                ->middleware('rate.limit:sensitive');
            Route::get('me', [AuthController::class, 'me']);
            Route::put('me', [AuthController::class, 'updateProfile'])
                ->middleware('rate.limit:write');
            Route::post('password', [AuthController::class, 'changePassword'])
                ->middleware('rate.limit:sensitive');
            Route::post('reauthenticate', [AuthController::class, 'reauthenticate'])
                ->middleware('rate.limit:sensitive');
            Route::get('reauthenticate/status', [AuthController::class, 'reauthStatus']);

            // Two-factor authentication (Module 23)
            Route::prefix('2fa')->group(function (): void {
                Route::post('enable', [TwoFactorController::class, 'enable'])
                    ->middleware('rate.limit:sensitive');
                Route::post('confirm', [TwoFactorController::class, 'confirm'])
                    ->middleware('rate.limit:sensitive');
                Route::post('disable', [TwoFactorController::class, 'disable'])
                    ->middleware('rate.limit:sensitive');
                Route::get('recovery-codes', [TwoFactorController::class, 'recoveryCodes'])
                    ->middleware('rate.limit:sensitive');
            });
        });

        // 2FA verify is during login (no auth required, uses temp token)
        Route::post('2fa/verify', [TwoFactorController::class, 'verify'])
            ->middleware('rate.limit:2fa.verify');
    });

    // Tenant (workspace) endpoints — all require authentication.
    // index/store are tenant-agnostic; show/update/destroy resolve tenant
    // from the route param and enforce membership via TenantPolicy.
    Route::middleware(['auth:sanctum', 'rate.limit:read'])->group(function (): void {
        // Dashboard endpoints (Module 24)
        Route::get('dashboard/personal', [DashboardController::class, 'personal']);
        Route::middleware('tenant.resolve')->group(function (): void {
            Route::get('dashboard/company', [DashboardController::class, 'company']);
            Route::get('dashboard/usage', [DashboardController::class, 'usage']);
        });

        Route::get('tenants', [TenantController::class, 'index']);
        Route::post('tenants', [TenantController::class, 'store'])
            ->middleware('rate.limit:write');
        Route::middleware('tenant.resolve')->group(function (): void {
            Route::get('tenants/{tenant}', [TenantController::class, 'show'])
                ->middleware('can:view,tenant');
            Route::put('tenants/{tenant}', [TenantController::class, 'update'])
                ->middleware('can:update,tenant', 'rate.limit:write');
            Route::delete('tenants/{tenant}', [TenantController::class, 'destroy'])
                ->middleware('can:delete,tenant', 'rate.limit:sensitive');

            // Tenant member management — nested under tenants/{tenant}.
            // Policy enforcement is done inside the controller via
            // $this->authorize() calls, because the policy methods need
            // both the Tenant and the target User, and the can: middleware
            // would resolve to TenantPolicy (not TenantMemberPolicy).
            Route::get('tenants/{tenant}/members', [TenantMemberController::class, 'index']);
            Route::post('tenants/{tenant}/members/invite', [TenantMemberController::class, 'invite'])
                ->middleware('rate.limit:write');
            Route::post('tenants/{tenant}/members/accept', [TenantMemberController::class, 'accept'])
                ->middleware('rate.limit:write');
            Route::post('tenants/{tenant}/members/onboarding-complete', [TenantMemberController::class, 'completeOnboarding'])
                ->middleware('rate.limit:write');
            Route::get('tenants/{tenant}/members/{user}', [TenantMemberController::class, 'show']);
            Route::put('tenants/{tenant}/members/{user}', [TenantMemberController::class, 'update'])
                ->middleware('rate.limit:write');
            Route::put('tenants/{tenant}/members/{user}/role', [TenantMemberController::class, 'changeRole'])
                ->middleware('rate.limit:write');
            Route::post('tenants/{tenant}/members/{user}/teams', [TenantMemberController::class, 'assignTeams'])
                ->middleware('rate.limit:write');
            Route::delete('tenants/{tenant}/members/{user}/teams/{team}', [TenantMemberController::class, 'removeFromTeam'])
                ->middleware('rate.limit:write');
            Route::post('tenants/{tenant}/members/{user}/suspend', [TenantMemberController::class, 'suspend'])
                ->middleware('rate.limit:sensitive');
            Route::post('tenants/{tenant}/members/{user}/restore', [TenantMemberController::class, 'restore'])
                ->middleware('rate.limit:write');
            Route::post('tenants/{tenant}/members/{user}/offboard', [TenantMemberController::class, 'offboard'])
                ->middleware('reauth', 'rate.limit:sensitive');
            Route::delete('tenants/{tenant}/members/{user}', [TenantMemberController::class, 'destroy'])
                ->middleware('rate.limit:sensitive');
            Route::post('tenants/{tenant}/members/{user}/revoke-all', [EmergencyRevokeController::class, 'revokeAllForUser'])
                ->middleware('reauth', 'rate.limit:sensitive');
            Route::get('tenants/{tenant}/invitations', [TenantMemberController::class, 'invitations']);
            Route::delete('tenants/{tenant}/invitations/{invitation}', [TenantMemberController::class, 'cancelInvitation'])
                ->middleware('rate.limit:write');
        });
    });

    // Personal vault — user's private encrypted storage.
    // NOT tenant-scoped; items belong to the user directly (user_id).
    Route::middleware(['auth:sanctum', 'rate.limit:read'])->prefix('vault')->group(function (): void {
        // Vault items — core CRUD (Module 05) + organization endpoints (Module 06)
        Route::get('items', [PersonalVaultItemController::class, 'index']);
        Route::post('items', [PersonalVaultItemController::class, 'store'])
            ->middleware('rate.limit:write');
        Route::get('items/recent', [PersonalVaultItemController::class, 'recent']);
        Route::get('items/favorites', [PersonalVaultItemController::class, 'favorites']);
        Route::get('items/archived', [PersonalVaultItemController::class, 'archived']);
        Route::get('items/{item}', [PersonalVaultItemController::class, 'show']);
        Route::put('items/{item}', [PersonalVaultItemController::class, 'update'])
            ->middleware('rate.limit:write');
        Route::delete('items/{item}', [PersonalVaultItemController::class, 'destroy'])
            ->middleware('reauth', 'rate.limit:sensitive');
        Route::post('items/{item}/favorite', [PersonalVaultItemController::class, 'toggleFavorite'])
            ->middleware('rate.limit:write');
        Route::post('items/{item}/archive', [PersonalVaultItemController::class, 'archive'])
            ->middleware('rate.limit:write');
        Route::post('items/{item}/restore', [PersonalVaultItemController::class, 'restore'])
            ->middleware('rate.limit:write');

        // Soft-delete management (Module 29)
        Route::get('trash', [PersonalVaultItemController::class, 'trash']);
        Route::post('trash/{item}/restore', [PersonalVaultItemController::class, 'restoreFromTrash'])
            ->middleware('rate.limit:write');
        Route::delete('trash/{item}/force', [PersonalVaultItemController::class, 'forceDelete'])
            ->middleware('reauth', 'rate.limit:sensitive');
        Route::delete('trash', [PersonalVaultItemController::class, 'emptyTrash'])
            ->middleware('reauth', 'rate.limit:sensitive');

        // Search
        Route::get('search', [PersonalVaultItemController::class, 'search']);

        // Folders
        Route::get('folders', [PersonalVaultFolderController::class, 'index']);
        Route::get('folders/{folder}', [PersonalVaultFolderController::class, 'show']);
        Route::post('folders', [PersonalVaultFolderController::class, 'store'])
            ->middleware('rate.limit:write');
        Route::put('folders/{folder}', [PersonalVaultFolderController::class, 'update'])
            ->middleware('rate.limit:write');
        Route::delete('folders/{folder}', [PersonalVaultFolderController::class, 'destroy'])
            ->middleware('rate.limit:write');

        // Tags
        Route::get('tags', [PersonalVaultTagController::class, 'index']);
        Route::get('tags/{tag}', [PersonalVaultTagController::class, 'show']);
        Route::post('tags', [PersonalVaultTagController::class, 'store'])
            ->middleware('rate.limit:write');
        Route::put('tags/{tag}', [PersonalVaultTagController::class, 'update'])
            ->middleware('rate.limit:write');
        Route::delete('tags/{tag}', [PersonalVaultTagController::class, 'destroy'])
            ->middleware('rate.limit:write');
    });

    // Bulk operations (Module 30)
    Route::middleware(['auth:sanctum', 'rate.limit:write'])->prefix('personal-vault/items/bulk')->group(function (): void {
        Route::post('delete', [BulkOperationController::class, 'bulkDeletePersonalVaultItems']);
        Route::post('move', [BulkOperationController::class, 'bulkMovePersonalVaultItems']);
        Route::post('archive', [BulkOperationController::class, 'bulkArchivePersonalVaultItems']);
        Route::post('restore', [BulkOperationController::class, 'bulkRestorePersonalVaultItems']);
        Route::post('tag', [BulkOperationController::class, 'bulkTagPersonalVaultItems']);
        Route::post('create', [BulkOperationController::class, 'bulkCreatePersonalVaultItems']);
        Route::post('share', [BulkOperationController::class, 'bulkSharePersonalVaultItems'])
            ->middleware('reauth', 'rate.limit:sensitive');
    });

    // Teams & team/org vaults — tenant-scoped, require tenant context.
    Route::middleware(['auth:sanctum', 'tenant.resolve', 'rate.limit:read'])->group(function (): void {
        // Teams CRUD
        Route::get('tenants/{tenant}/teams', [TeamController::class, 'index']);
        Route::post('tenants/{tenant}/teams', [TeamController::class, 'store'])
            ->middleware('rate.limit:write');
        Route::get('tenants/{tenant}/teams/{team}', [TeamController::class, 'show']);
        Route::put('tenants/{tenant}/teams/{team}', [TeamController::class, 'update'])
            ->middleware('rate.limit:write');
        Route::delete('tenants/{tenant}/teams/{team}', [TeamController::class, 'destroy'])
            ->middleware('rate.limit:sensitive');

        // Team soft-delete management (Module 29)
        Route::get('tenants/{tenant}/teams/trash', [TeamController::class, 'trash']);
        Route::post('tenants/{tenant}/teams/{team}/restore', [TeamController::class, 'restore'])
            ->middleware('rate.limit:write');
        Route::delete('tenants/{tenant}/teams/{team}/force', [TeamController::class, 'forceDelete'])
            ->middleware('reauth', 'rate.limit:sensitive');

        // Team members
        Route::get('tenants/{tenant}/teams/{team}/members', [TeamMemberController::class, 'index']);
        Route::post('tenants/{tenant}/teams/{team}/members', [TeamMemberController::class, 'store'])
            ->middleware('rate.limit:write');
        Route::put('tenants/{tenant}/teams/{team}/members/{user}', [TeamMemberController::class, 'update'])
            ->middleware('rate.limit:write');
        Route::delete('tenants/{tenant}/teams/{team}/members/{user}', [TeamMemberController::class, 'destroy'])
            ->middleware('rate.limit:write');

        // Team vault items
        Route::get('tenants/{tenant}/teams/{team}/vault/items', [TeamVaultItemController::class, 'index']);
        Route::post('tenants/{tenant}/teams/{team}/vault/items', [TeamVaultItemController::class, 'store'])
            ->middleware('rate.limit:write');
        Route::get('tenants/{tenant}/teams/{team}/vault/items/{item}', [TeamVaultItemController::class, 'show']);
        Route::put('tenants/{tenant}/teams/{team}/vault/items/{item}', [TeamVaultItemController::class, 'update'])
            ->middleware('rate.limit:write');
        Route::delete('tenants/{tenant}/teams/{team}/vault/items/{item}', [TeamVaultItemController::class, 'destroy'])
            ->middleware('rate.limit:sensitive');

        // Org-wide vault items (team_id = null)
        Route::get('tenants/{tenant}/vault/items', [OrgVaultItemController::class, 'index']);
        Route::post('tenants/{tenant}/vault/items', [OrgVaultItemController::class, 'store'])
            ->middleware('rate.limit:write');
        Route::get('tenants/{tenant}/vault/items/{item}', [OrgVaultItemController::class, 'show']);
        Route::put('tenants/{tenant}/vault/items/{item}', [OrgVaultItemController::class, 'update'])
            ->middleware('rate.limit:write');
        Route::delete('tenants/{tenant}/vault/items/{item}', [OrgVaultItemController::class, 'destroy'])
            ->middleware('rate.limit:sensitive');
    });

    // Access grants — view who has access to a vault item
    Route::middleware(['auth:sanctum', 'tenant.resolve', 'rate.limit:read'])->prefix('vault/items')->group(function (): void {
        Route::get('{item}/access', [AccessGrantController::class, 'index']);
        Route::get('{item}/access/summary', [AccessGrantController::class, 'summary']);
        Route::get('{item}/access/countdown', [AccessGrantController::class, 'countdown']);
        Route::post('{item}/access', [AccessGrantController::class, 'store'])
            ->middleware('rate.limit:write');
        Route::post('{item}/access/bulk', [AccessGrantController::class, 'bulkStore'])
            ->middleware('rate.limit:write');
        Route::put('{item}/access/{grant}', [AccessGrantController::class, 'update'])
            ->middleware('rate.limit:write');
        Route::delete('{item}/access/{grant}', [AccessGrantController::class, 'destroy'])
            ->middleware('rate.limit:write');

        // Bulk revocation (Module 16)
        Route::post('{item}/access/revoke-all', [EmergencyRevokeController::class, 'revokeAllForResource'])
            ->middleware('rate.limit:sensitive');
        Route::post('{item}/access/revoke-team/{team}', [EmergencyRevokeController::class, 'revokeTeamAccess'])
            ->middleware('rate.limit:sensitive');

        // Secure share links (Module 17)
        Route::get('{item}/share-links', [SecureLinkController::class, 'indexForVaultItem']);
        Route::post('{item}/share-links', [SecureLinkController::class, 'storeForVaultItem'])
            ->middleware('reauth', 'rate.limit:sensitive');
    });

    // Password tools — generator and strength checker
    Route::middleware(['auth:sanctum', 'rate.limit:write'])->prefix('tools/password')->group(function (): void {
        Route::post('generate', [PasswordToolController::class, 'generate']);
        Route::post('strength', [PasswordToolController::class, 'checkStrength']);
    });

    // Secure files — upload, list, download, replace, archive, restore, delete
    Route::middleware(['auth:sanctum', 'tenant.resolve', 'rate.limit:read'])->prefix('files')->group(function (): void {
        Route::get('/', [SecureFileController::class, 'index']);
        Route::post('/', [SecureFileController::class, 'store'])
            ->middleware('rate.limit:write');
        Route::post('/bulk', [SecureFileController::class, 'bulkStore'])
            ->middleware('rate.limit:write');
        Route::get('/{file}', [SecureFileController::class, 'show']);
        Route::get('/{file}/download', [SecureFileController::class, 'download']);
        Route::put('/{file}', [SecureFileController::class, 'update'])
            ->middleware('rate.limit:write');
        Route::post('/{file}/replace', [SecureFileController::class, 'replace'])
            ->middleware('rate.limit:write');
        Route::delete('/{file}', [SecureFileController::class, 'destroy'])
            ->middleware('rate.limit:sensitive');
        Route::post('/{file}/archive', [SecureFileController::class, 'archive'])
            ->middleware('rate.limit:write');
        Route::post('/{file}/restore', [SecureFileController::class, 'restore'])
            ->middleware('rate.limit:write');

        // File soft-delete management (Module 29)
        Route::get('/trash', [SecureFileController::class, 'trash']);
        Route::post('/trash/{file}/restore', [SecureFileController::class, 'restoreFromTrash'])
            ->middleware('rate.limit:write');
        Route::delete('/trash/{file}/force', [SecureFileController::class, 'forceDelete'])
            ->middleware('reauth', 'rate.limit:sensitive');
        Route::delete('/trash', [SecureFileController::class, 'emptyTrash'])
            ->middleware('reauth', 'rate.limit:sensitive');

        Route::get('/folders', [FileFolderController::class, 'index']);
        Route::post('/folders', [FileFolderController::class, 'store'])
            ->middleware('rate.limit:write');
        Route::put('/folders/{folder}', [FileFolderController::class, 'update'])
            ->middleware('rate.limit:write');
        Route::delete('/folders/{folder}', [FileFolderController::class, 'destroy'])
            ->middleware('rate.limit:write');

        // File access grants — sharing
        Route::get('/{file}/access', [FileAccessController::class, 'index']);
        Route::post('/{file}/access', [FileAccessController::class, 'store'])
            ->middleware('rate.limit:write');
        Route::put('/{file}/access/{grant}', [FileAccessController::class, 'update'])
            ->middleware('rate.limit:write');
        Route::delete('/{file}/access/{grant}', [FileAccessController::class, 'destroy'])
            ->middleware('rate.limit:write');
        Route::post('/{file}/access/revoke-all', [EmergencyRevokeController::class, 'revokeAllForFile'])
            ->middleware('rate.limit:sensitive');

        // Secure share links (Module 17)
        Route::get('/{file}/share-links', [SecureLinkController::class, 'indexForFile']);
        Route::post('/{file}/share-links', [SecureLinkController::class, 'storeForFile'])
            ->middleware('rate.limit:sensitive');
    });

    // Secure notes — encrypted notes with sharing, folders, tags, search
    Route::middleware(['auth:sanctum', 'tenant.resolve', 'rate.limit:read'])->prefix('notes')->group(function (): void {
        Route::get('/', [SecureNoteController::class, 'index']);
        Route::post('/', [SecureNoteController::class, 'store'])
            ->middleware('rate.limit:write');
        Route::get('/search', [SecureNoteController::class, 'search']);
        Route::get('/{note}', [SecureNoteController::class, 'show']);
        Route::put('/{note}', [SecureNoteController::class, 'update'])
            ->middleware('rate.limit:write');
        Route::delete('/{note}', [SecureNoteController::class, 'destroy'])
            ->middleware('rate.limit:sensitive');
        Route::post('/{note}/pin', [SecureNoteController::class, 'togglePin'])
            ->middleware('rate.limit:write');

        // Note soft-delete management (Module 29)
        Route::get('/trash', [SecureNoteController::class, 'trash']);
        Route::post('/trash/{note}/restore', [SecureNoteController::class, 'restoreFromTrash'])
            ->middleware('rate.limit:write');
        Route::delete('/trash/{note}/force', [SecureNoteController::class, 'forceDelete'])
            ->middleware('reauth', 'rate.limit:sensitive');
        Route::delete('/trash', [SecureNoteController::class, 'emptyTrash'])
            ->middleware('reauth', 'rate.limit:sensitive');

        Route::get('/{note}/access', [NoteAccessController::class, 'index']);
        Route::post('/{note}/access', [NoteAccessController::class, 'store'])
            ->middleware('rate.limit:write');
        Route::put('/{note}/access/{grant}', [NoteAccessController::class, 'update'])
            ->middleware('rate.limit:write');
        Route::delete('/{note}/access/{grant}', [NoteAccessController::class, 'destroy'])
            ->middleware('rate.limit:write');
        Route::post('/{note}/access/revoke-all', [EmergencyRevokeController::class, 'revokeAllForNote'])
            ->middleware('rate.limit:sensitive');

        // Secure share links (Module 17)
        Route::get('/{note}/share-links', [SecureLinkController::class, 'indexForNote']);
        Route::post('/{note}/share-links', [SecureLinkController::class, 'storeForNote'])
            ->middleware('rate.limit:sensitive');

        Route::get('/folders', [NoteFolderController::class, 'index']);
        Route::post('/folders', [NoteFolderController::class, 'store'])
            ->middleware('rate.limit:write');
        Route::put('/folders/{folder}', [NoteFolderController::class, 'update'])
            ->middleware('rate.limit:write');
        Route::delete('/folders/{folder}', [NoteFolderController::class, 'destroy'])
            ->middleware('rate.limit:write');
    });

    // Access requests — request workflow for resources users don't have access to
    Route::middleware(['auth:sanctum', 'tenant.resolve', 'rate.limit:read'])->prefix('access-requests')->group(function (): void {
        Route::get('/', [AccessRequestController::class, 'index']);
        Route::post('/', [AccessRequestController::class, 'store'])
            ->middleware('rate.limit:write');
        Route::get('/history', [AccessRequestController::class, 'history']);
        Route::get('/{accessRequest}', [AccessRequestController::class, 'show']);
        Route::put('/{accessRequest}/approve', [AccessRequestController::class, 'approve'])
            ->middleware('rate.limit:write');
        Route::put('/{accessRequest}/reject', [AccessRequestController::class, 'reject'])
            ->middleware('rate.limit:write');
        Route::delete('/{accessRequest}', [AccessRequestController::class, 'destroy'])
            ->middleware('rate.limit:write');
    });

    // Secure share links — revoke and activity (Module 17/18)
    Route::middleware(['auth:sanctum', 'tenant.resolve', 'rate.limit:read'])->prefix('share-links')->group(function (): void {
        Route::delete('/{link}', [SecureLinkController::class, 'destroy'])
            ->middleware('rate.limit:sensitive');
        Route::get('/{link}/activity', [SecureLinkController::class, 'activity']);
    });

    // Global search & organization (Module 19)
    Route::middleware(['auth:sanctum', 'tenant.resolve', 'rate.limit:read'])->group(function (): void {
        Route::get('search', [SearchController::class, 'search']);
        Route::get('recent', [SearchController::class, 'recent']);
        Route::get('recent/created', [SearchController::class, 'recentCreated']);
        Route::get('expiring', [SearchController::class, 'expiring']);
    });

    // Activity & audit logging (Module 20)
    Route::middleware(['auth:sanctum', 'tenant.resolve', 'rate.limit:read'])->group(function (): void {
        Route::get('activity-logs', [ActivityLogController::class, 'personalHistory']);
        Route::get('tenants/{tenant}/activity-logs', [ActivityLogController::class, 'companyFeed']);
        Route::get('tenants/{tenant}/members/{user}/activity-logs', [ActivityLogController::class, 'employeeOverview']);
        Route::get('vault/items/{item}/activity-logs', [ActivityLogController::class, 'resourceHistory']);
        Route::get('files/{file}/activity-logs', [ActivityLogController::class, 'resourceHistory']);
    });

    // Security alerts & device management (Module 21)
    Route::middleware(['auth:sanctum', 'tenant.resolve', 'rate.limit:read'])->group(function (): void {
        Route::get('security-alerts', [SecurityAlertController::class, 'index']);
        Route::get('security-alerts/unread-count', [SecurityAlertController::class, 'unreadCount']);
        Route::post('security-alerts/read-all', [SecurityAlertController::class, 'markAllRead'])
            ->middleware('rate.limit:write');
        Route::post('security-alerts/{alert}/read', [SecurityAlertController::class, 'markRead'])
            ->middleware('rate.limit:write');
        Route::post('security-alerts/{alert}/dismiss', [SecurityAlertController::class, 'dismiss'])
            ->middleware('rate.limit:write');

        Route::get('devices', [DeviceController::class, 'index']);
        Route::delete('devices/{device}', [DeviceController::class, 'destroy'])
            ->middleware('rate.limit:sensitive');
    });
});
