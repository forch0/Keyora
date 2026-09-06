# Keyora — Build Log

> Track implementation progress across all modules. Update after each module is completed.

---

## Project Info

| Field | Value |
|---|---|
| **Framework** | Laravel 13.29.0 |
| **PHP** | 8.4 (local) / 8.4 (Docker) |
| **Database** | PostgreSQL 16 |
| **Queue/Cache** | Redis 7 |
| **Mail (dev)** | Mailpit |
| **API Client (dev)** | sunchayn/nimbus v0.7.0-alpha |
| **Debug (dev)** | laravel/telescope v5.22.1 |
| **Visualization (dev)** | laramint/laravel-brain v2.6.0 |
| **Translations (dev)** | laravel-lang/lang v15.34.6 |
| **Auth** | Laravel Sanctum v4.3.3 |
| **Static Analysis** | Larastan v3.10.0 (level 8) |
| **Formatter** | Laravel Pint v1.30.5 |
| **Testing** | PHPUnit 12.5.34 |

---

## Module Progress

| # | Module | Status | Completed | Notes |
|---|---|---|---|---|
| 01 | Project Setup & Foundation | ✅ Complete | 2026-09-01 | Laravel project in `src/`, Docker setup, packages installed (Sanctum, Nimbus, Telescope, Laravel-Brain, Laravel-Lang), API-only mode, code quality tools, test helpers |
| 02 | Authentication | ✅ Complete | 2026-09-01 | Sanctum token auth, register/login/logout, profile update, password change, password reset, rate limiting, 31 tests passing |
| 03 | Multi-Tenancy | ✅ Complete | 2026-09-01 | Tenant model, tenant_user pivot, BelongsToTenant trait (fail-closed), TenantManager singleton, ResolveTenant middleware, Tenant CRUD API, TenantPolicy, 10 new tests (41 total) |
| 04 | Company Workspace | ✅ Complete | 2026-09-01 | Tenant invitations, member CRUD, role management, suspend/restore, ResolveTenant blocks suspended, 16 new tests (57 total) |
| 05 | Personal Vault — Part 1 | ✅ Complete | 2026-09-01 | Encryptable trait, personal_vault_items CRUD, AES-256 encryption, 15 new tests (72 total) |
| 06 | Personal Vault — Part 2 | ✅ Complete | 2026-09-01 | Folders (nested), tags, favorite toggle, archive/restore, search, recent items, 19 new tests (91 total) |
| 07 | Teams & Team Vaults | ✅ Complete | 2026-09-01 | Teams (tenant-scoped), team_user pivot, team/org vault items (BelongsToTenant + Encryptable + SoftDeletes), 10 Actions, 4 controllers, 19 new routes, 13 new tests (104 total) |
| 08 | Permission System — Part 1 | ✅ Complete | 2026-09-02 | AccessGrant model (polymorphic, BelongsToTenant), Permission enum (hierarchy with parallel download/edit), AccessResolver service, VaultItemPolicy updated, 2 new routes, 15 new tests (119 total) |
| 09 | Permission System — Part 2 | ✅ Complete | 2026-09-02 | Grant/Update/Revoke Actions, AccessGranted/Updated/Revoked events, AccessGrantedNotification, AccessGrantPolicy, 4 new routes (POST/PUT/DELETE/bulk), 13 new tests (132 total) |
| 10 | Password Tools | ✅ Complete | 2026-09-02 | PasswordGenerator (random_int, min counts, exclude options), PasswordStrengthChecker (entropy, common passwords, penalties), 2 new routes, 12 new tests (144 total) |
| 11 | Secure Files — Part 1 | ✅ Complete | 2026-09-02 | SecureFile + FileFolder models, UploadFileAction, 14 new routes (files CRUD + folders CRUD), SecureFilePolicy, 18 new tests (162 total) |
| 12 | Secure Files — Part 2 | ✅ Complete | 2026-09-02 | FileAccessController (share/revoke/update/list), file expiration (410 Gone + scheduled command), FileExpiredNotification, 12 new tests (174 total) |
| 13 | Secure Notes | ✅ Complete | 2026-09-02 | SecureNote (Encryptable content), NoteFolder, NoteTag models, CreateNoteAction/UpdateNoteAction, SecureNotePolicy, SecureNoteController + NoteAccessController + NoteFolderController, 16 new routes (notes CRUD + access + folders + search), 16 new tests (190 total) |
| 14 | Temporary Access | ✅ Complete | 2026-09-02 | AccessDuration enum, ViewTracker service, CheckExpiredAccess command, SendExpirationWarning job, AccessExpiringSoon/AccessExpiredNotification notifications, ResourceViewed/AccessExpired events, countdown endpoint, 15 new tests (205 total) |
| 15 | Access Requests | ✅ Complete | 2026-09-02 | AccessRequest model, CreateAccessRequestAction/ApproveAccessRequestAction/RejectAccessRequestAction, AccessRequestPolicy, AccessRequestController, 3 events + 3 notifications, 7 new routes, 17 new tests (222 total) |
| 16 | Access Revocation | ✅ Complete | 2026-09-02 | EmergencyRevokeAction, RevokeAllAccessAction, RevokeTeamAccessAction, OffboardEmployeeAction, EmergencyRevokeController, 2 events + 1 notification, 6 new routes, 12 new tests (234 total) |
| 17 | Secure External Sharing — Part 1 | ✅ Complete | 2026-09-02 | SecureLink model, CreateSecureLinkAction, SecureLinkController, SecureLinkPolicy, SecureLinkResource, 1 event + 2 notifications, 7 new routes, 15 new tests (249 total) |
| 18 | Secure External Sharing — Part 2 | ✅ Complete | 2026-09-02 | SecureLinkAccess model, 4 new Actions (Verify/Access/SendEmail/ConfirmEmail), PublicLinkController, LinkAccessTokenFactory, 1 notification, 6 public + 1 activity routes, 18 new tests (267 total) |
| 19 | Search & Organization | ✅ Complete | 2026-09-02 | ResourceView model, GlobalSearch service, SearchController, ViewTracker updated, 4 new routes, tag/folder/shared/sort filters on vault items, 15 new tests (282 total) |
| 20 | Activity & Audit Logging | ✅ Complete | 2026-09-02 | ActivityLog model (append-only), ActivityLogger service, LogActivity job, 12 event listeners, ActivityLogController, TenantPolicy, CleanupActivityLogs command, 5 new routes, 18 new tests (300 total) |
| 21 | Security Alerts | ✅ Complete | 2026-09-02 | SecurityAlert + UserDevice models, DeviceDetector service, SecurityAlertController + DeviceController, 3 notifications, 2 jobs (CheckExpiringAccess, CheckExpiredAccess), DetectSuspiciousActivity command, 7 new routes, 3 scheduled tasks, 13 new tests (313 total) |
| 22 | Employee Lifecycle | ✅ Complete | 2026-09-02 | Extended invitations (team_ids + initial_access), AcceptInvitationAction auto-assigns teams + creates grants, CompleteOnboardingAction, AssignTeamAction + RemoveFromTeamAction, rewritten OffboardEmployeeAction (revoke access, remove teams, set status=left, revoke tokens, revoke secure links, dispatch event, notify), EmployeeOffboarded event + notification, 5 new controller methods, 5 new routes, 16 new tests (329 total) |
| 23 | Account Security | ✅ Complete | 2026-09-02 | TOTP 2FA (native, no package), recovery codes (bcrypt+encrypted), 2FA login flow, re-authentication middleware (15min window), logout-all, 6 actions, 3 notifications, 5 2FA endpoints + 3 auth endpoints, 15 new tests (344 total) |
| 24 | Dashboards | ✅ Complete | 2026-09-02 | DashboardService (personal/company/usage aggregation), DashboardController, config/plans.php (free/team/business/enterprise limits), 3 endpoints, Team files/notes relationships, 17 new tests (361 total) |
| 25 | SaaS & Billing — Part 1 | ⏭️ Skipped | — | Open-source project — no billing |
| 26 | SaaS & Billing — Part 2 | ⏭️ Skipped | — | Open-source project — no billing |
| 27 | Rate Limiting & API Throttling | ✅ Complete | 2026-09-02 | config/rate_limits.php (read/write/sensitive/auth/2fa profiles), RateLimitByProfile middleware (config-driven, user-keyed), TenantRateLimit middleware (plan-tier multiplier), 429 with X-RateLimit-* headers, security alerts on repeated sensitive violations, all routes throttled, 11 new tests (372 total) |
| 28 | API Documentation (OpenAPI/Scramble) | ✅ Complete | 2026-09-02 | dedoc/scramble ^0.13.42, OpenAPI 3.1 spec at /docs/api.json, HTML docs at /docs/api, 28 #[Group] attributes on controllers, bearer security scheme auto-documented from auth:sanctum middleware, viewApiDocs gate for non-local access, 7 new tests (379 total) |
| 29 | Soft Deletes Consistency | ✅ Complete | 2026-09-02 | SoftDeletes added to 7 models (PersonalVaultItem, Team, AccessGrant, AccessRequest, SecureLink, SecurityAlert, UserDevice), 4 Actions (RestoreModel, ForceDeleteModel, ListTrash, EmptyTrash), trash/restore/force-delete/empty-trash endpoints for vault/items/files/notes/teams, force-delete cleanup removes physical files + access grants + access requests, reauth required for force-delete & empty-trash, 12 new tests (391 total) |
| 30 | Bulk Operations | ✅ Complete | 2026-09-02 | BulkOperationService (bulkDelete, bulkMove, bulkArchive, bulkRestore, bulkTag, bulkShare, bulkCreate), thin BulkOperationController, 6 Form Requests, 7 endpoints under /personal-vault/items/bulk/*, per-item authorization (failed items counted not errored), all operations in DB transactions, reauth on bulk share, max 100 for operations max 50 for create, 16 new tests (407 total) |
| 31 | Dashboard Caching & Performance | ✅ Complete | 2026-09-02 | Cache::remember (60s TTL) on personal/company/usage dashboards, DashboardCacheService for invalidation, DashboardCacheObserver on 5 models (PersonalVaultItem, SecureFile, SecureNote, Team, SecurityAlert), InvalidateDashboardCache event subscriber for access grant/request events, X-Cache-Status and X-Cache-TTL headers, cache warmup scheduled job (every 5 min), 10 new tests (417 total) |
| KEY-32 | Production Readiness (Priority 1) | ✅ Complete | 2026-09-03 | Encryptable fail-closed decryption, GrantAccessAction race condition fix (transaction + lockForUpdate), SubjectType enum + subject_type whitelist on all access-grant endpoints, db:backup command (MySQL/PostgreSQL/SQLite) with daily scheduled rotation, health check endpoint (GET /api/v1/health), all 11 non-queued notifications now implement ShouldQueue, deployment runbook (docs/DEPLOYMENT.md), 11 new tests (428 total) |
| KEY-33 | Priority 2 Fixes (2.1-2.8) | ✅ Complete | 2026-09-03 | Bulk share dispatches AccessGranted events, offboarding invalidates dashboard caches, access_grants composite indexes, BulkOperationService uses AccessResolver for tenant-scoped permission checks, route paths standardized to /vault/items/bulk/*, AccessResolver::isOwner() re-evaluates tenant membership for created_by, AccessResolver N+1 fixed (subject filtering pushed into query), AccessGrantController cleanup (policy extraction, BulkGrantAccessRequest, countdown via resolver), 2 new tests (430 total) |
| KEY-34 | Priority 3 Quality of Life (3.1-3.10) | ✅ Complete | 2026-09-06 | GitHub Actions CI (test + phpstan + pint + composer audit), Composer deps pinned to exact versions, structured JSON logging channel, trash endpoints support per_page param (capped at 100), authenticatedUser() extracted to base Controller (removed 27 duplicates), PHPStan ignores reduced from 3 to 1 (onlyTrashed resolved via @var intersection types), .env.example updated to PostgreSQL, README encryption claims clarified (server-side, not zero-knowledge), API versioning strategy documented, 6 new tests (436 total) |

--- 