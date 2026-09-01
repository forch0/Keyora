# Keyora — Build Log

> Track implementation progress across all modules. Update after each module is completed.

---

## Project Info

| Field | Value |
|---|---|
| **Framework** | Laravel 13.29.0 |
| **PHP** | 8.5.6 (local) / 8.4 (Docker) |
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
| 06 | Personal Vault — Part 2 | ⬜ Not Started | — | — |
| 07 | Teams & Team Vaults | ⬜ Not Started | — | — |
| 08 | Permission System — Part 1 | ⬜ Not Started | — | — |
| 09 | Permission System — Part 2 | ⬜ Not Started | — | — |
| 10 | Password Tools | ⬜ Not Started | — | — |
| 11 | Secure Files — Part 1 | ⬜ Not Started | — | — |
| 12 | Secure Files — Part 2 | ⬜ Not Started | — | — |
| 13 | Secure Notes | ⬜ Not Started | — | — |
| 14 | Temporary Access | ⬜ Not Started | — | — |
| 15 | Access Requests | ⬜ Not Started | — | — |
| 16 | Access Revocation | ⬜ Not Started | — | — |
| 17 | Secure External Sharing — Part 1 | ⬜ Not Started | — | — |
| 18 | Secure External Sharing — Part 2 | ⬜ Not Started | — | — |
| 19 | Search & Organization | ⬜ Not Started | — | — |
| 20 | Activity & Audit Logging | ⬜ Not Started | — | — |
| 21 | Security Alerts | ⬜ Not Started | — | — |
| 22 | Employee Lifecycle | ⬜ Not Started | — | — |
| 23 | Account Security | ⬜ Not Started | — | — |
| 24 | Dashboards | ⬜ Not Started | — | — |
| 25 | SaaS & Billing — Part 1 | ⬜ Not Started | — | — |
| 26 | SaaS & Billing — Part 2 | ⬜ Not Started | — | — |

---

## Module 01 — Detailed Log

### Completed Steps

| Step | Description | Verification |
|---|---|---|
| 1.1 | Created Laravel project in `src/` | `php artisan --version` → Laravel 13.29.0 |
| 1.2.1 | Created `docker/Dockerfile` (PHP 8.4-FPM Alpine, Redis ext via pecl) | — |
| 1.2.2 | Created `docker/nginx/default.conf` (12M body limit) | — |
| 1.2.3 | Created `docker/php/local.ini` (256M memory, 12M upload) | — |
| 1.2.4 | Created `docker-compose.yml` (app, nginx, postgres, redis, mailpit) | — |
| 1.2.5 | Created `.dockerignore` | — |
| 1.3 | Installed packages: Sanctum, Nimbus, Pint, Larastan, Telescope, Laravel-Brain, Laravel-Lang | `composer show` confirms all installed |
| 1.4 | Configured `.env` and `.env.example` for Docker (MySQL, Redis, Mailpit) | `php artisan key:generate` succeeded |
| 1.5 | Created directory structure (Actions, Events, Jobs, Listeners, Notifications, Policies, Rules, Services, Traits, Enums, Http/Controllers/Api/V1, Http/Middleware, Http/Requests, Http/Resources/V1) | Directories exist |
| 1.6 | Configured API-only mode in `bootstrap/app.php` (API routing, Sanctum middleware, JSON exceptions) | `php artisan route:list --path=api` shows `GET /api/v1/` |
| 1.7 | Created `pint.json` (Laravel preset) and `phpstan.neon` (level 8, Larastan v3) | `vendor/bin/pint --test` → 29 files, 0 issues; `vendor/bin/phpstan analyse` → No errors |
| 1.8 | Added `private` disk to `config/filesystems.php`, added `sanctum` guard to `config/auth.php`, published & configured `config/nimbus.php` (versioned routes, sanctum guard, impersonation, prefix → `api-client`) | — |
| 1.9 | JSON exception handling via `bootstrap/app.php` (`shouldRenderJsonWhen`) | — |
| 1.10 | Base test setup: `RefreshDatabase` on `TestCase`, `AuthHelper` and `TenantHelper` traits | `php artisan test` → 2 passed, 0 failures |

### Packages Installed

| Package | Version | Type |
|---|---|---|
| laravel/sanctum | v4.3.3 | require |
| sunchayn/nimbus | v0.7.0-alpha | require-dev |
| laravel/telescope | v5.22.1 | require-dev |
| laramint/laravel-brain | v2.6.0 | require-dev |
| laravel-lang/lang | v15.34.6 | require-dev |
| laravel/pint | v1.30.5 | require-dev (pre-installed) |
| larastan/larastan | v3.10.0 | require-dev |

### Files Created/Modified

| File | Action |
|---|---|
| `src/` (entire Laravel project) | Created |
| `docker/Dockerfile` | Created (PHP 8.4-FPM Alpine, autoconf for pecl) |
| `docker/nginx/default.conf` | Created |
| `docker/php/local.ini` | Created |
| `docker-compose.yml` | Created (MySQL on port 3307 to avoid local conflict) |
| `.dockerignore` | Created |
| `.gitignore` (root) | Created |
| `src/.env.example` | Modified (Docker defaults) |
| `src/.env` | Created (copied from .env.example, key generated) |
| `src/bootstrap/app.php` | Modified (API routing, Sanctum, JSON exceptions, middleware aliases) |
| `src/config/auth.php` | Modified (added `sanctum` guard) |
| `docker-compose.yml` | Modified (switched `mysql` → `postgres:16-alpine` on port 5432; native PHP in WSL replaces `app`/`nginx` containers) |
| `src/config/nimbus.php` | Created (published from package, configured for Keyora) |
| `src/config/telescope.php` | Created (published from package) |
| `src/routes/api.php` | Created (`GET /api/v1/` → `{"status":"ok"}`) |
| `src/pint.json` | Created |
| `src/phpstan.neon` | Created |
| `src/tests/TestCase.php` | Modified (added `RefreshDatabase`) |
| `src/tests/Helpers/AuthHelper.php` | Created |
| `src/tests/Helpers/TenantHelper.php` | Created |
| `src/app/Actions/` through `src/app/Http/Resources/V1/` | Created (empty directories) |
| `src/app/Enums/` | Created (empty directory) |

### Known Issues / Notes

- Laravel 13 does not include `config/cors.php` — CORS is handled via middleware in `bootstrap/app.php`
- Laravel 13 does not include `app/Exceptions/Handler.php` — exception handling is in `bootstrap/app.php`
- Nimbus is alpha (v0.7.0-alpha) — requires `@alpha` stability flag in composer
- Nimbus downgraded Guzzle from 8.x to 7.x (compatible)
- PHPStan requires `--memory-limit=512M` CLI flag when running locally (128M default is insufficient)
- Docker volumes mount `./src` (not project root) to keep container clean
- Middleware aliases registered for future modules: `tenant.resolve`, `reauth`
- Dockerfile uses PHP 8.4 (not 8.2) because Laravel 13 requires PHP 8.3+
- Docker Postgres exposed on port 5432 (switched from MySQL on 3307 for RLS/UUID/JSONB support needed by multi-tenancy + audit modules)
- Redis PHP extension available locally (php8.4-redis) — Redis-dependent commands work natively
- Telescope config published to `src/config/telescope.php`
- Docker build verified: all 5 containers running, migrations pass, tests pass, API returns JSON

---

## Docker Status

| Container | Image | Port | Purpose |
|---|---|---|---|
| keyora-app | keyora-app (PHP 8.4-FPM) | 9000 (internal) | Laravel application (not used — PHP runs natively in WSL) |
| keyora-nginx | nginx:1.25-alpine | **8080** | Web server / reverse proxy (not used — `php artisan serve` on :8000) |
| keyora-postgres | postgres:16-alpine | **5432** | Database |
| keyora-redis | redis:7-alpine | **6379** | Queue / Cache |
| keyora-mailpit | axllent/mailpit | **8025** (UI), **1025** (SMTP) | Local mail testing |

### Accessible URLs

- **API** (native PHP): `http://localhost:8000/api/v1/`
- **API Client** (Nimbus): `http://localhost:8000/api-client`
- **Telescope** (debugger): `http://localhost:8000/telescope`
- **Mailpit** (mail UI): `http://localhost:8025`

---

## Verification Commands

Run from `src/` directory (PHP runs natively in WSL; only stateful services in Docker):

```bash
# Route check
php artisan route:list --path=api

# Code formatting
vendor/bin/pint --test

# Static analysis
vendor/bin/phpstan analyse --no-progress --memory-limit=512M

# Tests
php artisan test

# Serve (native PHP)
php artisan serve --host=0.0.0.0 --port=8000

# Docker stateful services (from project root)
docker compose up -d postgres redis mailpit
docker compose ps
```

---

## Next Module

**Module 06 — Personal Vault Part 2**
- Folders, tags, favorites toggle, search
- Archive/restore functionality
- Password history
- Dependencies: Module 05 ✅

---

## Module 05 — Detailed Log

### Completed Steps

| Step | Description | Verification |
|---|---|---|
| 5.1 | Created `Encryptable` trait — overrides `getAttribute()`/`setAttribute()` for transparent AES-256 encryption via `Crypt::encryptString()`/`decryptString()`, handles nulls gracefully | Trait loads, encryption verified in tests |
| 5.2 | Created `personal_vault_items` migration — id, user_id FK, name, type enum, encrypted text columns (username/password/notes/custom_fields), plaintext columns (url/metadata), favorite, archived_at, 4 composite indexes | `php artisan migrate:fresh` → all tables created |
| 5.3 | Created `PersonalVaultItem` model — uses Encryptable trait, fillable, casts (metadata→array, favorite→boolean, archived_at→datetime), user() relationship, scopes (ofType, favorite, active), custom getAttribute/setAttribute for encrypted custom_fields JSON | Model loads, PHPStan clean |
| 5.4 | Created `PersonalVaultItemFactory` — default states for all 4 types, configurable fields | Factory works in tests |
| 5.5 | Created `CreateItemRequest` — validates name, type enum, nullable encrypted fields, metadata sub-fields, custom_fields array validation | Validation works in tests |
| 5.6 | Created `UpdateItemRequest` — same rules with `sometimes` for partial updates | Validation works in tests |
| 5.7 | Created `PersonalVaultItemResource` — serializes all fields including decrypted sensitive fields | Resource transforms correctly |
| 5.8 | Created `PersonalVaultItemPolicy` — view/update/delete check user_id ownership, returns false (not 403) so controller can abort(404) to avoid leaking existence | Policy enforced correctly |
| 5.9 | Created 3 Actions: `CreateVaultItemAction`, `UpdateVaultItemAction`, `DeleteVaultItemAction` | Actions work in tests |
| 5.10 | Created `PersonalVaultItemController` — 5 RESTful methods (index with filters, store, show, update, destroy), owner checks return 404 for non-owners | All endpoints respond correctly |
| 5.11 | Registered vault routes via `apiResource` under `/api/v1/vault` | `php artisan route:list` shows 5 vault routes |
| 5.12 | Wrote 15 feature tests covering all acceptance criteria | `php artisan test` → 72 passed, 231 assertions |
| 5.13 | Ran verification: Pint (clean), PHPStan level 8 (0 errors), tests (72 passed) | All three pass clean |

### API Endpoints Implemented

| Method | Endpoint | Auth | Status | Description |
|---|---|---|---|---|
| GET | `/api/v1/vault/items` | Yes | 200 | List user's vault items (paginated, filterable by type/favorite/archived) |
| POST | `/api/v1/vault/items` | Yes | 201 | Create vault item (encrypts sensitive fields) |
| GET | `/api/v1/vault/items/{item}` | Yes | 200 | Get single item with decrypted sensitive fields |
| PUT | `/api/v1/vault/items/{item}` | Yes | 200 | Update vault item |
| DELETE | `/api/v1/vault/items/{item}` | Yes | 204 | Delete vault item |

### Architecture Decisions

| Decision | Rationale |
|---|---|
| Hard delete instead of soft delete | The spec said "soft delete or hard delete — decide". Soft deletes add complexity (deleted_at column, global scope). Archive/restore is handled separately via `archived_at` (Module 06). Hard delete is simpler and appropriate for MVP. |
| `custom_fields` encrypted as JSON string | The spec says custom_fields is ENCRYPTED JSON. The Encryptable trait handles string encryption, so custom_fields is JSON-encoded then encrypted on set, and decrypted then JSON-decoded on get. This requires custom getAttribute/setAttribute overrides in the model. |
| Model overrides getAttribute/setAttribute directly | The Encryptable trait's overrides get shadowed when the model also overrides for custom_fields. PHP trait method resolution: the class's own method wins over trait methods. So the model handles both custom_fields and encryptable fields in its own overrides. |
| 404 for non-owner access (not 403) | The spec explicitly says "don't leak existence". Returning 404 for other users' items prevents information leakage about which items exist. |
| `metadata` stored in plaintext | Per ADR-003: metadata is display data (host, port, provider, db_type) — no secrets. Stored as plaintext JSON for searchability. Sensitive values go in encrypted columns. |
| `name` and `url` stored in plaintext | Per ADR-003: these are searchable fields. Encrypted fields cannot be queried with WHERE clauses. |
| `@phpstan-ignore-next-line` for `property_exists` | The Encryptable trait is designed to be reusable, but PHPStan analyzes it in the context of the concrete class where the property is always declared. The ignore comment is necessary for this generic trait pattern. |

### Files Created/Modified

| File | Action |
|---|---|
| `src/app/Traits/Encryptable.php` | Created |
| `src/database/migrations/2026_09_01_160000_create_personal_vault_items_table.php` | Created |
| `src/app/Models/PersonalVaultItem.php` | Created |
| `src/database/factories/PersonalVaultItemFactory.php` | Created |
| `src/app/Http/Requests/Vault/CreateItemRequest.php` | Created |
| `src/app/Http/Requests/Vault/UpdateItemRequest.php` | Created |
| `src/app/Http/Resources/V1/PersonalVaultItemResource.php` | Created |
| `src/app/Policies/PersonalVaultItemPolicy.php` | Created |
| `src/app/Actions/CreateVaultItemAction.php` | Created |
| `src/app/Actions/UpdateVaultItemAction.php` | Created |
| `src/app/Actions/DeleteVaultItemAction.php` | Created |
| `src/app/Http/Controllers/Api/V1/PersonalVaultItemController.php` | Created |
| `src/routes/api.php` | Modified — added vault apiResource routes |
| `src/tests/Feature/Api/V1/Vault/PersonalVaultItemTest.php` | Created (15 tests) |
| `docs/learnings/04-personal-vault-part-1.md` | Created — build walkthrough |

### Test Results

| Test Class | Tests | Assertions | Covers |
|---|---|---|---|
| `PersonalVaultItemTest` | 15 | 56 | Create 4 types, custom fields, list, view, update, delete, 404 for non-owner, encryption verification, null handling, type/favorite filters |
| **Module 05 Total** | **15** | **56** | — |
| **Cumulative Total** | **72** | **231** | — |

### Bugs Found and Fixed

| Bug | Cause | Fix |
|---|---|---|
| `user_id` not in fillable | The `#[Fillable]` attribute didn't include `user_id`, so mass assignment skipped it → NOT NULL constraint violation | Added `user_id` to the Fillable attribute |
| Encrypted values stored as plaintext | The `Encryptable` trait's `setAttribute` was shadowed by the model's own `setAttribute` override (for custom_fields). PHP trait method resolution: class method wins over trait methods. | Model's `setAttribute` now handles both custom_fields encryption AND encryptable field encryption directly |
| Decryption not working on read | Same shadowing issue with `getAttribute` — model's override for custom_fields shadowed the trait's decryption logic | Model's `getAttribute` now handles both custom_fields decryption AND encryptable field decryption directly |
| `deleted_at` column not found | Model used `SoftDeletes` trait but migration didn't have `deleted_at` column | Removed `SoftDeletes` trait — using hard delete instead (archive/restore is Module 06) |
| `withoutScope()` method not found | Controller tried to call `withoutScope('active')` which doesn't exist on Eloquent Builder | Restructured the query to use `whereNull`/`whereNotNull` on `archived_at` directly |
| PHPStan: `$encryptable` iterable type | Property declared as `array` without value type | Added `@var list<string>` PHPDoc annotation |
| PHPStan: `property_exists` always true | PHPStan analyzes the trait in context of the concrete class where the property is declared | Added `@phpstan-ignore-next-line` comment — the trait is designed to be reusable |

### Known Issues / Notes

- The `Encryptable` trait's `getAttribute`/`setAttribute` overrides are shadowed when a model using the trait also defines its own overrides. The `PersonalVaultItem` model handles this by implementing all encryption/decryption logic in its own overrides. Future models using `Encryptable` without custom overrides will work fine with the trait's methods.
- `custom_fields` is stored as an encrypted JSON string, not a JSON column. This means it can't be queried with JSON operators. This is acceptable since custom fields contain secrets.
- The `Encryptable` trait catches decryption failures gracefully (returns raw value) to prevent app crashes during key rotation. Module 20 (audit logging) will add proper error logging.
- Build walkthrough documented in `docs/learnings/04-personal-vault-part-1.md`

---

## Module 04 — Detailed Log

### Completed Steps

| Step | Description | Verification |
|---|---|---|
| 4.1 | Created `tenant_invitations` table migration (id, tenant_id, email, role enum, token UUID, invited_by, accepted_at, expires_at, timestamps) | `php artisan migrate:fresh` → all tables created |
| 4.2 | Updated `tenant_user` pivot — changed `status` to enum (active, suspended, left), added `suspended_at` | Migration runs clean |
| 4.3 | Created `TenantInvitation` model — fillable, casts (accepted_at/expires_at → datetime), `tenant()` + `inviter()` relations, `isAccepted()`, `isExpired()`, `isPending()` helpers | Model loads, PHPStan clean |
| 4.4 | Updated `Tenant` model — added `status`/`suspended_at` to pivot, `activeMembers()` scope, `invitations()` hasMany | Relationships work |
| 4.5 | Updated `User` model — `isMemberOf()` now checks status='active', added `isAdminOf()`, `statusIn()` helpers | All helpers return correct values |
| 4.6 | Created `EmployeeInvitation` notification — sent to invitee email with tenant name, inviter name, token link | Notification class loads |
| 4.7 | Created `SendInviteEmail` job (ShouldQueue) — dispatches notification via anonymous notifiable route | Queue::fake() confirms dispatch in test |
| 4.8 | Created `InviteEmployeeAction` — generates UUID token, creates invitation, dispatches SendInviteEmail job | Action works in tests |
| 4.9 | Created `AcceptInvitationAction` — validates token (not expired, not accepted), attaches user to tenant, marks accepted | Action works in tests |
| 4.10 | Created 3 Form Requests: `InviteMemberRequest`, `AcceptInvitationRequest`, `UpdateMemberRequest` | Validation works in tests |
| 4.11 | Created `TenantMemberResource` (id, name, email, role, status, joined_at, suspended_at, teams_count) and `TenantInvitationResource` (id, email, role, invited_by, accepted_at, expires_at) | Resources transform correctly |
| 4.12 | Created `TenantMemberPolicy` — view (member), invite (admin/owner), update/suspend/restore/remove (admin/owner, cannot act on owner), manageInvitations (admin/owner) | Policy enforced via gates |
| 4.13 | Registered 7 gates in AppServiceProvider delegating to TenantMemberPolicy | Gates resolve correctly |
| 4.14 | Added `AuthorizesRequests` trait to base Controller (Laravel 13 doesn't include it by default) | `$this->authorize()` works |
| 4.15 | Created `TenantMemberController` — 10 methods (index, invite, accept, show, update, suspend, restore, destroy, invitations, cancelInvitation) | All endpoints respond correctly |
| 4.16 | Registered 10 member/invitation routes nested under `tenants/{tenant}` | `php artisan route:list` shows 15 tenant routes total |
| 4.17 | Updated `ResolveTenant` middleware — blocks suspended members with 403, extracts `ensureActiveMember()` helper | Suspended member test passes |
| 4.18 | Updated `TenantHelper` — added `createInvitation()` helper, `attachUserToTenant()` now accepts extra pivot attributes | Test helpers work |
| 4.19 | Wrote 16 feature tests across 2 test classes covering all acceptance criteria | `php artisan test` → 57 passed, 175 assertions |
| 4.20 | Ran verification: Pint (88 files, 0 issues), PHPStan level 8 (0 errors), tests (57 passed) | All three pass clean |

### API Endpoints Implemented

| Method | Endpoint | Auth | Role | Status | Description |
|---|---|---|---|---|---|
| GET | `/api/v1/tenants/{tenant}/members` | Yes | Member | 200 | List all members |
| POST | `/api/v1/tenants/{tenant}/members/invite` | Yes | Admin/Owner | 201 | Invite employee via email |
| POST | `/api/v1/tenants/{tenant}/members/accept` | Yes | Any user | 200/422 | Accept invitation with token |
| GET | `/api/v1/tenants/{tenant}/members/{user}` | Yes | Member | 200 | View member profile |
| PUT | `/api/v1/tenants/{tenant}/members/{user}` | Yes | Admin/Owner | 200 | Change member role |
| POST | `/api/v1/tenants/{tenant}/members/{user}/suspend` | Yes | Admin/Owner | 204 | Suspend member |
| POST | `/api/v1/tenants/{tenant}/members/{user}/restore` | Yes | Admin/Owner | 204 | Restore suspended member |
| DELETE | `/api/v1/tenants/{tenant}/members/{user}` | Yes | Admin/Owner | 204 | Remove member |
| GET | `/api/v1/tenants/{tenant}/invitations` | Yes | Admin/Owner | 200 | List pending invitations |
| DELETE | `/api/v1/tenants/{tenant}/invitations/{invitation}` | Yes | Admin/Owner | 204 | Cancel invitation |

### Architecture Decisions

| Decision | Rationale |
|---|---|
| Gates instead of model policy for TenantMemberPolicy | The `can:` middleware resolves policy by model class. Both TenantPolicy and TenantMemberPolicy operate on `Tenant`, so only one can be the model policy. Registered TenantMemberPolicy methods as named gates in AppServiceProvider instead. |
| `$this->authorize()` in controller instead of `can:` middleware | The gates need both `Tenant` and `User` (target member) arguments. `can:` middleware passes route params, but the gate lookup was ambiguous. Calling `$this->authorize('member.suspend', [$tenant, $user])` in the controller is explicit and clear. |
| `AuthorizesRequests` trait on base Controller | Laravel 13's default Controller is empty (no traits). Added `AuthorizesRequests` to enable `$this->authorize()` across all controllers. |
| `accept` endpoint has no membership check | The whole point of accepting an invitation is that the user is NOT yet a member. The invitation token itself is the authorization. |
| Alter migration for pivot status | Created a separate migration to change `status` from string to enum rather than modifying the Module 03 migration. Preserves migration history. |
| `isMemberOf()` now checks `status='active'` | Previously just checked `left_at IS NULL`. Now also excludes suspended members. This means suspended members fail all membership checks automatically. |
| `ensureActiveMember()` in ResolveTenant | Extracted the membership/suspension check into a private method. Both token-based and header-based resolution use it. Suspended members get 403 with a specific message. |

### Files Created/Modified

| File | Action |
|---|---|
| `src/database/migrations/2026_09_01_150000_create_tenant_invitations_table.php` | Created |
| `src/database/migrations/2026_09_01_150001_update_tenant_user_pivot_status.php` | Created |
| `src/app/Models/TenantInvitation.php` | Created |
| `src/app/Models/Tenant.php` | Modified — added status/suspended_at to pivot, activeMembers(), invitations() |
| `src/app/Models/User.php` | Modified — isMemberOf() checks status, added isAdminOf(), statusIn() |
| `src/app/Notifications/EmployeeInvitation.php` | Created |
| `src/app/Jobs/SendInviteEmail.php` | Created |
| `src/app/Actions/InviteEmployeeAction.php` | Created |
| `src/app/Actions/AcceptInvitationAction.php` | Created |
| `src/app/Http/Requests/Tenant/InviteMemberRequest.php` | Created |
| `src/app/Http/Requests/Tenant/AcceptInvitationRequest.php` | Created |
| `src/app/Http/Requests/Tenant/UpdateMemberRequest.php` | Created |
| `src/app/Http/Resources/V1/TenantMemberResource.php` | Created |
| `src/app/Http/Resources/V1/TenantInvitationResource.php` | Created |
| `src/app/Policies/TenantMemberPolicy.php` | Created |
| `src/app/Http/Controllers/Controller.php` | Modified — added AuthorizesRequests trait |
| `src/app/Http/Controllers/Api/V1/TenantMemberController.php` | Created |
| `src/app/Http/Middleware/ResolveTenant.php` | Modified — blocks suspended members, extracted ensureActiveMember() |
| `src/app/Providers/AppServiceProvider.php` | Modified — registered 7 TenantMemberPolicy gates |
| `src/routes/api.php` | Modified — added 10 member/invitation routes |
| `src/tests/Helpers/TenantHelper.php` | Modified — added createInvitation(), attachUserToTenant() accepts extra attributes |
| `src/tests/Feature/Api/V1/Tenants/InvitationTest.php` | Created (7 tests) |
| `src/tests/Feature/Api/V1/Tenants/MemberManagementTest.php` | Created (9 tests) |
| `docs/learnings/03-company-workspace.md` | Created — build walkthrough |

### Test Results

| Test Class | Tests | Assertions | Covers |
|---|---|---|---|
| `InvitationTest` | 7 | 22 | Invite, member cannot invite, accept, expired, already accepted, list pending, cancel |
| `MemberManagementTest` | 9 | 28 | List members, view profile, change role, cannot change owner, suspend, suspended blocked, restore, remove, cannot remove owner |
| **Module 04 Total** | **16** | **50** | — |
| **Cumulative Total** | **57** | **175** | — |

### Bugs Found and Fixed

| Bug | Cause | Fix |
|---|---|---|
| All member endpoints return 403 | `can:` middleware resolves policy by model class. `can:invite,tenant` used TenantPolicy (not TenantMemberPolicy) since both operate on Tenant | Replaced `can:` middleware with `$this->authorize()` calls in controller using named gates registered in AppServiceProvider |
| `authorize()` method undefined | Laravel 13's base Controller doesn't include `AuthorizesRequests` trait | Added `use AuthorizesRequests` to base Controller |
| Accept endpoint returns 403 | `accept` had `$this->authorize('member.view', $tenant)` but the invitee isn't a member yet | Removed membership check from accept — the token IS the authorization |
| PHPStan: nullable relations | `BelongsTo` returns `Tenant\|null`; accessing `->id` fails | Added null checks in AcceptInvitationAction and SendInviteEmail |
| PHPStan: `expires_at` is string | Model casts not recognized without `@property` annotations | Added `@property` PHPDoc with Carbon types to TenantInvitation model |

### Known Issues / Notes

- `TenantMemberPolicy` is registered as gates (not a model policy) because `Tenant` already has `TenantPolicy` for CRUD. Future modules with the same pattern should use gates.
- The `accept` endpoint is nested under `tenants/{tenant}` but doesn't require membership — the tenant in the URL is for routing context only; the actual tenant comes from the invitation token
- `SendInviteEmail` uses `Notification::route('mail', ...)` because the invitee may not have a User account yet — can't use `$user->notify()`
- `isMemberOf()` now checks `status='active'` — this is stricter than Module 03. All Module 03 tests still pass because the helper attaches with `status: 'active'`.
- `Auth::forgetGuards()` needed in `test_suspended_member_cannot_access_tenant` (same pattern as Modules 02/03)
- Build walkthrough documented in `docs/learnings/03-company-workspace.md`

---

## Module 03 — Detailed Log

### Completed Steps

| Step | Description | Verification |
|---|---|---|
| 3.1 | Created `tenants` table migration (id, name, slug, plan, settings JSON, trial_ends_at, soft deletes) | `php artisan migrate:fresh` → all tables created |
| 3.2 | Created `tenant_user` pivot migration (tenant_id, user_id, role enum, status, joined_at, left_at, unique + index) | Migration runs clean |
| 3.3 | Added `tenant_id` nullable FK to `personal_access_tokens` table (for token-tenant association) | Migration runs clean |
| 3.4 | Created `Tenant` model — $fillable, casts (settings→array, trial_ends_at→datetime), SoftDeletes, `users()` belongsToMany with pivot | Model loads, factory works |
| 3.5 | Created `TenantFactory` — generates unique slug, default plan 'free' | Factory creates valid tenants |
| 3.6 | Updated `User` model — added `tenants()` belongsToMany, `isMemberOf()`, `roleIn()`, `ownsTenant()` helpers | All helper methods return correct values |
| 3.7 | Created `BelongsToTenant` trait — global scope (fail-closed: throws without tenant context), auto-sets tenant_id on create, `tenant()` relation, `withoutTenant()` scope | Trait tests pass (3/3) |
| 3.8 | Created `TenantManager` service — setCurrentTenant, currentTenantId, hasCurrentTenant, forgetCurrentTenant | Registered as singleton in AppServiceProvider |
| 3.9 | Created `ResolveTenant` middleware — Priority 1: token tenant_id, Priority 2: X-Tenant-ID header (verifies membership, 403 if not member), tenant-agnostic if no context | Middleware tests pass |
| 3.10 | Created `CreateTenantRequest` (name required, slug nullable unique) and `UpdateTenantRequest` (name/slug/settings sometimes) | Validation works in tests |
| 3.11 | Created `TenantResource` — id, name, slug, plan, settings, trial_ends_at, role (from pivot), created_at | Resource transforms correctly |
| 3.12 | Created `TenantPolicy` — view (member), update (owner/admin), delete (owner) | Policy enforced via `can:` middleware |
| 3.13 | Created `CreateTenantAction` — creates tenant, generates unique slug, attaches user as owner | Action invoked from controller |
| 3.14 | Created `TenantController` — index (list user's tenants), store (create + owner), show (member only), update (owner/admin), destroy (owner, soft delete) | All 5 CRUD endpoints work |
| 3.15 | Registered tenant routes in `routes/api.php` — auth:sanctum, tenant.resolve on show/update/destroy, can: middleware for policy | `php artisan route:list` shows 5 routes |
| 3.16 | Created test-only `TenantScopedModel` + migration to verify `BelongsToTenant` trait | Trait tests pass |
| 3.17 | Wrote 10 feature tests across 2 test classes covering all acceptance criteria | `php artisan test` → 41 passed, 125 assertions |
| 3.18 | Ran verification: Pint (72 files, 0 issues), PHPStan level 8 (0 errors), tests (41 passed) | All three pass clean |

### API Endpoints Implemented

| Method | Endpoint | Auth | Tenant | Status | Description |
|---|---|---|---|---|---|
| GET | `/api/v1/tenants` | Yes | No | 200 | List user's workspaces |
| POST | `/api/v1/tenants` | Yes | No | 201 | Create new workspace (user becomes owner) |
| GET | `/api/v1/tenants/{tenant}` | Yes | Yes | 200 | Get workspace details (member only) |
| PUT | `/api/v1/tenants/{tenant}` | Yes | Yes | 200 | Update workspace (owner/admin only) |
| DELETE | `/api/v1/tenants/{tenant}` | Yes | Yes | 204 | Soft-delete workspace (owner only) |

### Architecture Decisions

| Decision | Rationale |
|---|---|
| In-house implementation (no package) | ARCHITECTURE.md §3 is prescriptive with exact code. Single-DB packages (ubayedtanvir, rylxes) are new/unproven. DB-per-tenant packages (stancl, spatie) don't match ADR-002. Implementation is ~100 lines — not enough complexity to justify a dependency. |
| Fail-closed trait (throws without tenant context) | Spec requires: "Querying tenant-scoped models without a tenant context throws exception." Prevents cross-tenant data leaks in dev/test before they reach production. |
| `withoutTenant()` scope for explicit bypass | Admin/cross-tenant operations need an opt-out. Making it explicit (named scope) ensures it's intentional and visible in code review. |
| Soft deletes on tenants | Spec says `destroy()` = soft delete. Allows recovery of accidentally deleted workspaces. |
| `status` column on tenant_user pivot | Added beyond spec (TenantHelper already used it). Needed for Module 04 (employee lifecycle: active/inactive/invited). |
| `tenant_id` on personal_access_tokens | Spec §3.6 Priority 1: "Token's associated tenant (if token has tenant_id)." Required the column to exist. |
| `CreateTenantAction` for tenant creation | Follows Module 02 pattern: thin controller delegates to action. Action handles slug generation + owner attachment atomically. |
| `can:` middleware for policy enforcement | Laravel's built-in `can:ability,route_param` middleware runs the policy before the controller. Cleaner than manual `$this->authorize()` calls. |
| Test-only `TenantScopedModel` | Needed a model using `BelongsToTenant` trait to test trait behavior in isolation. Not exposed via API; exists solely for trait tests. |

### Files Created/Modified

| File | Action |
|---|---|
| `src/database/migrations/2026_09_01_140000_create_tenants_table.php` | Created |
| `src/database/migrations/2026_09_01_140001_create_tenant_user_table.php` | Created |
| `src/database/migrations/2026_09_01_140002_add_tenant_id_to_personal_access_tokens.php` | Created |
| `src/database/migrations/2026_09_01_140003_create_tenant_scoped_models_table.php` | Created (test-only) |
| `src/app/Models/Tenant.php` | Created |
| `src/app/Models/TenantScopedModel.php` | Created (test-only) |
| `src/app/Models/User.php` | Modified — added tenants() relationship, isMemberOf(), roleIn(), ownsTenant() |
| `src/database/factories/TenantFactory.php` | Created |
| `src/app/Traits/BelongsToTenant.php` | Created |
| `src/app/Services/TenantManager.php` | Created |
| `src/app/Providers/AppServiceProvider.php` | Modified — registered TenantManager as singleton |
| `src/app/Http/Middleware/ResolveTenant.php` | Created |
| `src/app/Actions/CreateTenantAction.php` | Created |
| `src/app/Http/Requests/Tenant/CreateTenantRequest.php` | Created |
| `src/app/Http/Requests/Tenant/UpdateTenantRequest.php` | Created |
| `src/app/Http/Resources/V1/TenantResource.php` | Created |
| `src/app/Policies/TenantPolicy.php` | Created |
| `src/app/Http/Controllers/Api/V1/TenantController.php` | Created |
| `src/routes/api.php` | Modified — added 5 tenant endpoints with auth, tenant.resolve, and can: middleware |
| `src/tests/Feature/Api/V1/Tenants/TenantCrudTest.php` | Created (7 tests) |
| `src/tests/Feature/Traits/BelongsToTenantTest.php` | Created (3 tests) |
| `docs/learnings/02-multi-tenancy.md` | Created — build walkthrough |

### Test Results

| Test Class | Tests | Assertions | Covers |
|---|---|---|---|
| `TenantCrudTest` | 7 | 21 | Create, list, view as member, non-member 403, owner-only delete, header resolution, invalid header 403 |
| `BelongsToTenantTest` | 3 | 7 | Auto-sets tenant_id, scope filters by current tenant, throws without tenant context |
| **Module 03 Total** | **10** | **28** | — |
| **Cumulative Total** | **41** | **125** | — |

### Bugs Found and Fixed

| Bug | Cause | Fix |
|---|---|---|
| `tenant_user` has no column named `status` | TenantHelper (from Module 01) attaches with `status: 'active'` but migration didn't include it | Added `status` column to pivot migration (needed for Module 04 anyway) |
| ResolveTenant return type error | `Illuminate\Http\Response` doesn't cover `JsonResponse` (from `abort(403)`) | Changed return type to `Symfony\Component\HttpFoundation\Response` (parent class) |
| `$builder->getTable()` undefined | Builder doesn't have `getTable()`; it's on the Model | Changed to `$builder->getModel()->getTable()` |
| Owner delete test returns 403 | `RequestGuard` caches authenticated user across requests in same test (same bug as Module 02) | Added `Auth::forgetGuards()` between the member and owner delete requests |
| PHPStan: `instanceof` always true on `PersonalAccessToken` | `currentAccessToken()` PHPDoc types return as non-nullable in Larastan | Removed `instanceof` check, used `@var PersonalAccessToken|null` assertion |
| PHPStan: `$pivot?->role` on string | `pivot` property on Eloquent model not recognized by PHPStan | Used `getRelation('pivot')` + `instanceof Pivot` check + `getAttribute('role')` |
| PHPStan: `$model->tenant_id` undefined on `Model` | `Model` class doesn't declare `tenant_id` property | Used `getAttribute('tenant_id')` / `setAttribute('tenant_id', ...)` |
| PHPStan: `BelongsToMany` generic type mismatch | Single-param `@return BelongsToMany<User>` doesn't satisfy 4-template-param class | Used full generic: `BelongsToMany<User, $this, Pivot, 'pivot'>` |

### Known Issues / Notes

- `TenantScopedModel` and its migration exist solely for testing the `BelongsToTenant` trait — they're not exposed via API and will be removed once a real tenant-scoped model exists (Module 05+)
- Token-to-tenant association (`tenant_id` on `personal_access_tokens`) is in the schema but not yet used in production flows — tokens are currently created without a tenant_id. Will be used when tenant-scoped token creation is implemented (Module 04+)
- `TenantHelper::attachUserToTenant()` sets `status: 'active'` — the `status` column supports future states (invited, inactive, suspended) for Module 04/22
- `Auth::forgetGuards()` is needed in any test that makes multiple authenticated requests with different users (same pattern as Module 02)
- Build walkthrough documented in `docs/learnings/02-multi-tenancy.md`

---

## Module 02 — Detailed Log

### Completed Steps

| Step | Description | Verification |
|---|---|---|
| 2.1 | Modified `users` migration — added `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at` columns | `php artisan migrate:fresh` → all tables created |
| 2.2 | Updated `User` model — added `HasApiTokens` trait (Sanctum), two-factor casts, hidden fields | Model loads without errors |
| 2.3 | Published Sanctum config (`config/sanctum.php`) and `personal_access_tokens` migration | `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"` → config + migration published |
| 2.4 | Set Sanctum `guard` to `[]` (API-only, bearer-token-only — no session fallback per ADR-001) | Logout test confirms revoked tokens return 401 |
| 2.5 | Removed `EnsureFrontendRequestsAreStateful` middleware from `bootstrap/app.php` (API-only, no SPA cookie auth) | `php artisan route:list` loads without middleware |
| 2.6 | Created 5 Action classes: `RegisterUserAction`, `LoginUserAction`, `ChangePasswordAction`, `RequestPasswordResetAction`, `ResetPasswordAction` | Each is invokable, single use case, delegates to model/Sanctum/broker |
| 2.7 | Created 6 Form Requests: `RegisterRequest`, `LoginRequest`, `UpdateProfileRequest`, `ChangePasswordRequest`, `ForgotPasswordRequest`, `ResetPasswordRequest` | Validation rules match module spec |
| 2.8 | Created `UserResource` (API Resource) — exposes id, name, email, email_verified_at, timestamps; never exposes password, two_factor_secret, remember_token | Profile test confirms sensitive fields absent |
| 2.9 | Created `AuthController` (thin) — delegates to actions, returns API Resources with correct status codes | All 8 endpoints respond correctly |
| 2.10 | Added auth routes to `routes/api.php` with rate limiting (5/min for register+login, 3/min for forgot-password) | `php artisan route:list --path=api/v1/auth` → 8 routes listed |
| 2.11 | Created `PasswordResetNotification` and `WelcomeNotification` (queued, mail channel) | Password reset test confirms notification sent |
| 2.12 | Wrote 31 feature tests across 6 test classes covering all acceptance criteria | `php artisan test` → 31 passed, 89 assertions |
| 2.13 | Ran verification: Pint (54 files, 0 issues), PHPStan level 8 (0 errors), tests (31 passed) | All three pass clean |

### API Endpoints Implemented

| Method | Endpoint | Auth | Rate Limit | Status | Description |
|---|---|---|---|---|---|
| POST | `/api/v1/auth/register` | No | 5/min | 201 | Create user + return token |
| POST | `/api/v1/auth/login` | No | 5/min | 200 | Verify credentials + return token |
| POST | `/api/v1/auth/forgot-password` | No | 3/min | 200 | Send reset email (always 200, doesn't leak user existence) |
| POST | `/api/v1/auth/reset-password` | No | — | 200/422 | Reset password with token |
| POST | `/api/v1/auth/logout` | Yes | — | 204 | Revoke current token |
| GET | `/api/v1/auth/me` | Yes | — | 200 | Get current user |
| PUT | `/api/v1/auth/me` | Yes | — | 200 | Update name/email |
| POST | `/api/v1/auth/password` | Yes | — | 200/422 | Change password |

### Architecture Decisions

| Decision | Rationale |
|---|---|
| Action classes over a single AuthService | Each auth operation is a distinct use case (not reusable cross-cutting logic). Matches ARCHITECTURE.md §6.5 pattern. |
| Thin controller | Controller only receives request → delegates to action → returns resource. No business logic. Per ARCHITECTURE.md §6.3. |
| Sanctum guard set to `[]` | API-only project (ADR-001: "No session-based auth"). Default `['web']` falls back to sessions, which caused token revocation to not work. |
| Removed `EnsureFrontendRequestsAreStateful` | That middleware is for SPA cookie-based auth. Not needed for API-only. Its presence caused session-persisted auth between test requests. |
| `Auth::forgetGuards()` in logout test | `RequestGuard` caches the authenticated user in memory and doesn't reset between requests in the same test. Must manually reset to test token revocation. |
| `authenticatedUser()` helper in controller | `$request->user()` returns `User\|null` but `auth:sanctum` guarantees non-null. Helper satisfies PHPStan level 8 without lying about runtime. |
| Forgot-password always returns 200 | Doesn't leak which emails are registered (security best practice). Notification only sent if user exists. |

### Files Created/Modified

| File | Action |
|---|---|
| `src/database/migrations/0001_01_01_000000_create_users_table.php` | Modified — added 2FA columns |
| `src/database/migrations/2026_09_01_133153_create_personal_access_tokens_table.php` | Created (published from Sanctum) |
| `src/app/Models/User.php` | Modified — added `HasApiTokens`, 2FA casts, hidden fields |
| `src/config/sanctum.php` | Created (published) + modified (`guard` → `[]`) |
| `src/bootstrap/app.php` | Modified — removed `EnsureFrontendRequestsAreStateful` |
| `src/app/Actions/RegisterUserAction.php` | Created |
| `src/app/Actions/LoginUserAction.php` | Created |
| `src/app/Actions/ChangePasswordAction.php` | Created |
| `src/app/Actions/RequestPasswordResetAction.php` | Created |
| `src/app/Actions/ResetPasswordAction.php` | Created |
| `src/app/Http/Requests/Auth/RegisterRequest.php` | Created |
| `src/app/Http/Requests/Auth/LoginRequest.php` | Created |
| `src/app/Http/Requests/Auth/UpdateProfileRequest.php` | Created |
| `src/app/Http/Requests/Auth/ChangePasswordRequest.php` | Created |
| `src/app/Http/Requests/Auth/ForgotPasswordRequest.php` | Created |
| `src/app/Http/Requests/Auth/ResetPasswordRequest.php` | Created |
| `src/app/Http/Resources/V1/UserResource.php` | Created |
| `src/app/Http/Controllers/Api/V1/AuthController.php` | Created |
| `src/routes/api.php` | Modified — added 8 auth endpoints with rate limiting |
| `src/app/Notifications/PasswordResetNotification.php` | Created |
| `src/app/Notifications/WelcomeNotification.php` | Created |
| `src/tests/Feature/Api/V1/Auth/RegisterTest.php` | Created (6 tests) |
| `src/tests/Feature/Api/V1/Auth/LoginTest.php` | Created (6 tests) |
| `src/tests/Feature/Api/V1/Auth/ProfileTest.php` | Created (5 tests) |
| `src/tests/Feature/Api/V1/Auth/LogoutTest.php` | Created (2 tests) |
| `src/tests/Feature/Api/V1/Auth/ChangePasswordTest.php` | Created (4 tests) |
| `src/tests/Feature/Api/V1/Auth/PasswordResetTest.php` | Created (6 tests) |
| `docs/learnings/01-authentication.md` | Created — build walkthrough + build order reference |

### Test Results

| Test Class | Tests | Assertions | Covers |
|---|---|---|---|
| `RegisterTest` | 6 | 14 | Registration, duplicate email, password hashing, validation |
| `LoginTest` | 6 | 12 | Valid/invalid login, validation, rate limiting (429) |
| `ProfileTest` | 5 | 17 | Get profile, 401 without token, update, duplicate email, no sensitive fields |
| `LogoutTest` | 2 | 4 | Token revocation, auth required |
| `ChangePasswordTest` | 4 | 10 | Change password, wrong current, confirmation, auth required |
| `PasswordResetTest` | 6 | 12 | Request reset, no user leak, valid/invalid token, validation |
| **Total** | **31** | **89** | — |

### Bugs Found and Fixed

| Bug | Cause | Fix |
|---|---|---|
| All tests returning 404 | Stale route cache from previous state | `php artisan route:clear` + `php artisan config:clear` |
| Logout test failing — revoked token still authenticates | Sanctum `guard` set to `['web']` (session fallback) + `RequestGuard` caches user in memory | Set `guard` to `[]`, removed `EnsureFrontendRequestsAreStateful`, added `Auth::forgetGuards()` in test |
| PHPStan level 8 errors (9) | `$request->user()` returns `User\|null`; notification `object` type hints; `Password::getRepository()->create()` wrong arg count | Added `authenticatedUser()` helper, typed notifications as `User`, used `Password::createToken()` |

### Known Issues / Notes

- Two-factor authentication columns are in the migration but not yet functional (Module 23)
- `WelcomeNotification` is created but not yet triggered on registration (optional per module spec)
- Tenant relationship on `User` (belongsToMany) deferred to Module 03
- `Auth::forgetGuards()` needed in tests that make multiple authenticated requests — `RequestGuard` caches user across requests in the same test
- Build walkthrough documented in `docs/learnings/01-authentication.md`
