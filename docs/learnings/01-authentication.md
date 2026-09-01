# 01 — How Module 02 (Authentication) Was Built

| Field | Value |
|---|---|
| **Module** | 02 — Authentication |
| **Date** | 2026-09-01 |
| **Branch** | `feature/KEY-02-authentication` |
| **Base branch** | `develop` |
| **Spec** | `docs/modules/02-authentication.md` |
| **Architecture ref** | `docs/ARCHITECTURE.md` |

---

## Goal

Implement user registration, login, logout, profile management, password change, and password reset using Laravel Sanctum token-based authentication. This is the foundation every subsequent module depends on.

---

## Decisions Made Before Writing Code

### 1. Follow the architecture doc, not just the module spec

The module spec lists all logic inside `AuthController`. But `ARCHITECTURE.md` §6.3 is explicit:

> Controllers are thin — no business logic. Delegate to Action classes for use cases.

So I split the work: business logic went into invokable Action classes, and the controller stayed thin (receive request → delegate → return resource).

### 2. Action classes over a single AuthService

The architecture doc defines two patterns:
- **Actions** (`app/Actions/`) — one use case per invokable class
- **Services** (`app/Services/`) — reusable cross-cutting logic

Auth operations are each distinct use cases (register, login, change password, etc.), not reusable logic shared across the app. So Action classes were the right fit — each one does exactly one thing and is easy to test in isolation.

### 3. API-only — no session auth

`ARCHITECTURE.md` ADR-001 states: "No session-based auth, no CSRF tokens." The default Laravel bootstrap included `EnsureFrontendRequestsAreStateful` middleware (for SPA cookie auth) and Sanctum's guard was set to `['web']` (falls back to sessions). Both were removed to stay true to the API-only decision.

---

## Files Created / Modified

### Modified

| File | What changed |
|---|---|
| `src/database/migrations/0001_01_01_000000_create_users_table.php` | Added `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at` columns |
| `src/app/Models/User.php` | Added `HasApiTokens` trait (Sanctum), two-factor casts, hidden fields |
| `src/bootstrap/app.php` | Removed `EnsureFrontendRequestsAreStateful` middleware (API-only, no sessions) |
| `src/config/sanctum.php` | Published from Sanctum; set `guard` to `[]` (bearer-token-only auth) |
| `src/routes/api.php` | Added all auth endpoints with rate limiting |

### Created — Actions

| File | Purpose |
|---|---|
| `src/app/Actions/RegisterUserAction.php` | Create user + generate Sanctum token |
| `src/app/Actions/LoginUserAction.php` | Verify credentials + generate token |
| `src/app/Actions/ChangePasswordAction.php` | Verify current password + update |
| `src/app/Actions/RequestPasswordResetAction.php` | Create reset token + send notification (doesn't leak if email doesn't exist) |
| `src/app/Actions/ResetPasswordAction.php` | Reset password via Laravel's password broker |

### Created — Controller

| File | Purpose |
|---|---|
| `src/app/Http/Controllers/Api/V1/AuthController.php` | Thin controller — delegates to actions, returns API Resources |

### Created — Form Requests

| File | Validates |
|---|---|
| `src/app/Http/Requests/Auth/RegisterRequest.php` | name, email (unique), password (min 8, confirmed) |
| `src/app/Http/Requests/Auth/LoginRequest.php` | email, password |
| `src/app/Http/Requests/Auth/UpdateProfileRequest.php` | name, email (unique, ignoring current user) |
| `src/app/Http/Requests/Auth/ChangePasswordRequest.php` | current_password, password (min 8, confirmed) |
| `src/app/Http/Requests/Auth/ForgotPasswordRequest.php` | email |
| `src/app/Http/Requests/Auth/ResetPasswordRequest.php` | email, token, password (min 8, confirmed) |

### Created — API Resource

| File | Purpose |
|---|---|
| `src/app/Http/Resources/V1/UserResource.php` | Transforms User → JSON; exposes id, name, email, email_verified_at, timestamps. Never exposes password, two_factor_secret, remember_token |

### Created — Notifications

| File | Purpose |
|---|---|
| `src/app/Notifications/PasswordResetNotification.php` | Sends password reset email with token link |
| `src/app/Notifications/WelcomeNotification.php` | Welcome email on registration (optional, for future use) |

### Created — Tests

| File | Tests |
|---|---|
| `src/tests/Feature/Api/V1/Auth/RegisterTest.php` | 6 tests — register, duplicate email, password hashing, validation |
| `src/tests/Feature/Api/V1/Auth/LoginTest.php` | 6 tests — valid/invalid login, validation, rate limiting |
| `src/tests/Feature/Api/V1/Auth/ProfileTest.php` | 5 tests — get profile, 401 without token, update, duplicate email, no sensitive fields exposed |
| `src/tests/Feature/Api/V1/Auth/LogoutTest.php` | 2 tests — logout revokes token, requires auth |
| `src/tests/Feature/Api/V1/Auth/ChangePasswordTest.php` | 4 tests — change, wrong current password, confirmation, auth required |
| `src/tests/Feature/Api/V1/Auth/PasswordResetTest.php` | 6 tests — request reset, no user leak, valid/invalid token, validation |

### Created — Migration (published from Sanctum)

| File | Purpose |
|---|---|
| `src/database/migrations/2026_09_01_133153_create_personal_access_tokens_table.php` | Sanctum's token storage table |

---

## Request Flow (how a login request travels through the system)

```
1. Client sends POST /api/v1/auth/login
   Body: { "email": "...", "password": "..." }
        │
        ▼
2. Route match → routes/api.php
   Middleware: throttle:5,1 (5 requests/min per IP)
        │
        ▼
3. Form Request: LoginRequest
   - authorize() → true (public endpoint)
   - rules() → validates email + password
   - If validation fails → 422 with error envelope
        │
        ▼
4. Controller: AuthController::login()
   - Receives validated data
   - Delegates to LoginUserAction
        │
        ▼
5. Action: LoginUserAction::__invoke()
   - Finds user by email
   - Checks password via Hash::check()
   - If invalid → returns null
   - If valid → creates Sanctum token → returns [user, token]
        │
        ▼
6. Controller receives [user, token]
   - If null → returns 422 with AUTH_INVALID_CREDENTIALS error
   - If success → wraps user in UserResource, adds token
        │
        ▼
7. Response: 200 OK
   {
     "data": { "id": 1, "name": "...", "email": "...", ... },
     "token": "1|abcdef..."
   }
```

---

## All Endpoints

| Method | Endpoint | Auth | Rate Limit | Status | Description |
|---|---|---|---|---|---|
| POST | `/api/v1/auth/register` | No | 5/min | 201 | Create user + return token |
| POST | `/api/v1/auth/login` | No | 5/min | 200 | Verify credentials + return token |
| POST | `/api/v1/auth/forgot-password` | No | 3/min | 200 | Send reset email (always 200, doesn't leak) |
| POST | `/api/v1/auth/reset-password` | No | — | 200/422 | Reset password with token |
| POST | `/api/v1/auth/logout` | Yes | — | 204 | Revoke current token |
| GET | `/api/v1/auth/me` | Yes | — | 200 | Get current user |
| PUT | `/api/v1/auth/me` | Yes | — | 200 | Update name/email |
| POST | `/api/v1/auth/password` | Yes | — | 200/422 | Change password |

---

## Layered Architecture (how the pieces fit)

```
routes/api.php                          ← URL definitions + rate limiting
    │
    ▼
AuthController                          ← Thin: receives request, delegates, returns resource
    │
    ├── Form Requests                   ← Validation (reject bad input before controller runs)
    │
    ▼
Action Classes                          ← Business logic (one use case each)
    │
    ├── User model                      ← Data representation
    ├── Sanctum (token generation)      ← Framework
    ├── Password broker (reset)         ← Framework
    └── Notifications                   ← Side effects (send email)
    │
    ▼
UserResource                            ← Transform model → JSON response
```

---

## Bugs Found and Fixed During Implementation

### 1. Routes returning 404 in tests

**Cause:** A stale route cache from a previous state was preventing new routes from registering.

**Fix:** `php artisan route:clear` + `php artisan config:clear`.

### 2. Logout test failing — token revoked but user still authenticated

**Cause:** Two issues stacked:
1. Sanctum's guard config was set to `['web']`, meaning it fell back to session-based auth before checking bearer tokens. The session persisted the user between test requests.
2. `RequestGuard` (Laravel's internal class) caches the authenticated user in memory and doesn't reset it when the request changes.

**Fix:**
- Set `config/sanctum.php` `guard` to `[]` — bearer-token-only, no session fallback
- Removed `EnsureFrontendRequestsAreStateful` from `bootstrap/app.php` — not needed for API-only
- Called `Auth::forgetGuards()` in the logout test to reset the cached guard state between requests

### 3. PHPStan level 8 errors

**Cause:** `$request->user()` returns `User|null`, but in `auth:sanctum`-protected routes it's always non-null. PHPStan doesn't know that.

**Fix:** Added a private `authenticatedUser()` helper in the controller that returns `User` or aborts with 401. This satisfies the type system without lying about the runtime behavior.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — 54 files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 31 tests, 89 assertions |

---

## What This Module Does NOT Include

Per the module spec, these are deferred to later modules:

- Two-factor authentication → Module 23
- Tenant/workspace membership → Module 03, 04
- Device/session management → Module 23
- Re-authentication for sensitive actions → Module 23
- Login history logging → Module 20

---

## Key Takeaways

1. **Read the architecture doc before the module spec** — the module spec tells you *what* to build; the architecture doc tells you *how* to build it. When they conflict, the architecture doc wins.

2. **Thin controllers are a discipline, not a default** — Laravel makes it easy to stuff everything in the controller. Extracting Action classes takes more files but makes each piece testable and replaceable.

3. **API-only means API-only** — Laravel's defaults assume you might have a frontend. Removing session-based middleware and Sanctum's session guard fallback was necessary to match the architecture decision.

4. **Test the negative paths** — rate limiting, wrong passwords, missing tokens, duplicate emails, user existence leaks. These matter more than the happy path for security.

---

## Build Order — Files Created A to Z

The order is **bottom-up**: database first, then model, then business logic, then HTTP layer, then tests. Each layer depends on the one below it, so you can't skip ahead without breaking something.

### The sequence

```
STEP 1 → Database layer (foundation — everything depends on this)
STEP 2 → Model layer (represents the data)
STEP 3 → Business logic (actions that do the work)
STEP 4 → Input validation (form requests — guards the controller)
STEP 5 → Output transformation (API resources — shapes the response)
STEP 6 → Controller (wires everything together)
STEP 7 → Routes (exposes endpoints to the outside world)
STEP 8 → Notifications / side effects (emails, events)
STEP 9 → Tests (verifies the whole thing works)
STEP 10 → Verification (pint, phpstan, test suite)
```

### Why this order

| Step | What | Why it goes here |
|---|---|---|
| 1 | **Migration** | Tables must exist before anything can query them. Run `php artisan migrate` immediately after. |
| 2 | **Model** | The model maps to the table. Actions and controllers will type-hint it, so it must exist first. Add traits (`HasApiTokens`, etc.), casts, fillable fields, relationships. |
| 3 | **Actions** | Business logic that operates on the model. The controller will delegate to these, so they must exist before the controller. Each action is one use case. |
| 4 | **Form Requests** | Validation rules. The controller will type-hint these in method signatures, so they must exist before the controller. |
| 5 | **API Resource** | Transforms the model into JSON. The controller returns this, so it must exist before the controller. |
| 6 | **Controller** | Now everything it depends on exists: actions (logic), form requests (validation), resources (response). The controller just wires them together — thin. |
| 7 | **Routes** | Points URLs to controller methods. Controller must exist first or the route registration errors. Add rate limiting here. |
| 8 | **Notifications** | Side effects triggered by actions (emails, etc.). Can be done after actions since actions reference them, but they don't block the HTTP flow. |
| 9 | **Tests** | Now the full system works end-to-end. Tests hit the routes, which hit the controller, which uses actions, which use the model, which uses the database. Write tests last so you're testing the real thing. |
| 10 | **Verification** | `pint` (formatting) → `phpstan` (static analysis) → `php artisan test` (tests). Fix any issues, re-run until clean. |

### For Module 02 specifically, the order was

```
 1. Migration          → 0001_01_01_000000_create_users_table.php (modified — added 2FA columns)
 2. Migration          → 2026_09_01_133153_create_personal_access_tokens_table.php (published from Sanctum)
    → php artisan migrate
 3. Model              → User.php (added HasApiTokens, casts, hidden fields)
 4. Config             → sanctum.php (published, set guard to [])
 5. Action             → RegisterUserAction.php
 6. Action             → LoginUserAction.php
 7. Action             → ChangePasswordAction.php
 8. Action             → RequestPasswordResetAction.php
 9. Action             → ResetPasswordAction.php
10. Form Request       → RegisterRequest.php
11. Form Request       → LoginRequest.php
12. Form Request       → UpdateProfileRequest.php
13. Form Request       → ChangePasswordRequest.php
14. Form Request       → ForgotPasswordRequest.php
15. Form Request       → ResetPasswordRequest.php
16. API Resource       → UserResource.php
17. Controller         → AuthController.php
18. Routes             → routes/api.php (modified — added auth endpoints + rate limiting)
19. Notification       → PasswordResetNotification.php
20. Notification       → WelcomeNotification.php
21. Test               → RegisterTest.php
22. Test               → LoginTest.php
23. Test               → ProfileTest.php
24. Test               → LogoutTest.php
25. Test               → ChangePasswordTest.php
26. Test               → PasswordResetTest.php
27. Verification       → pint → phpstan → php artisan test
```

### The rule of thumb

> **Each file should only reference files that already exist.**
>
> If you create the controller before the actions, your IDE shows errors and you can't reason about the flow. If you create tests before the routes, every test 404s. Build from the bottom up and each step just works.

### Quick reference for any future module

```
migration → migrate → model → actions → form requests → resource → controller → routes → notifications → tests → verify
```

