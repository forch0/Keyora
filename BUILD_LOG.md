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
| 03 | Multi-Tenancy | ⬜ Not Started | — | — |
| 04 | Company Workspace | ⬜ Not Started | — | — |
| 05 | Personal Vault — Part 1 | ⬜ Not Started | — | — |
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

**Module 03 — Multi-Tenancy**
- Tenant model and migrations
- `BelongsToTenant` trait + `TenantScope` global scope
- `TenantManager` singleton service
- `ResolveTenant` middleware (token scope → X-Tenant-ID header → URL param)
- Tenant-aware models vs non-tenant models
- Dependencies: Module 02 ✅

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
