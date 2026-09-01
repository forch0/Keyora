# Module 02 — Authentication

| Field | Value |
|---|---|
| **Module** | 02 |
| **Name** | Authentication |
| **Dependencies** | Module 01 |
| **Status** | Not Started |

---

## Objective

Implement user registration, login, logout, password reset, and API token management using Laravel Sanctum. This module establishes the `User` model and the authentication foundation that all subsequent modules depend on.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| (implied) | User can register an account | P0 |
| (implied) | User can log in and receive API token | P0 |
| (implied) | User can log out (revoke token) | P0 |
| (implied) | User can request password reset | P0 |
| (implied) | User can reset password with token | P0 |
| AC-06 | Change password | P0 |
| AC-07 | Password reset via email | P0 |

---

## Tasks

### 2.1 User Model & Migration

- [ ] Create `users` table migration with columns:
  - `id`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret` (nullable), `two_factor_recovery_codes` (nullable), `two_factor_confirmed_at` (nullable), `remember_token`, `created_at`, `updated_at`
- [ ] Create `User` model with:
  - `$fillable`: `name`, `email`, `password`
  - Password cast: `hashed` (Laravel 10+ attribute casting)
  - `HasApiTokens` trait (Sanctum)
  - `Notifiable` trait
  - Relationship to `Tenant` (belongsToMany — added in Module 03)

### 2.2 Personal Access Tokens Table

- [ ] Run `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`
- [ ] Run `php artisan migrate` to create `personal_access_tokens` table
- [ ] Configure Sanctum in `config/sanctum.php`

### 2.3 Password Resets Table

- [ ] Ensure `password_reset_tokens` migration exists (Laravel default)
- [ ] Configure password reset broker in `config/auth.php`

### 2.4 API Endpoints

| Method | Endpoint | Description | Auth Required |
|---|---|---|---|
| `POST` | `/api/v1/auth/register` | Register new user | No |
| `POST` | `/api/v1/auth/login` | Login, return API token | No |
| `POST` | `/api/v1/auth/logout` | Revoke current token | Yes |
| `GET` | `/api/v1/auth/me` | Get current user profile | Yes |
| `PUT` | `/api/v1/auth/me` | Update profile (name, email) | Yes |
| `POST` | `/api/v1/auth/password` | Change password | Yes |
| `POST` | `/api/v1/auth/forgot-password` | Send password reset email | No |
| `POST` | `/api/v1/auth/reset-password` | Reset password with token | No |

### 2.5 Controllers

- [ ] `app/Http/Controllers/Api/V1/AuthController.php`
  - `register()` — validate input, create user, generate token, return user + token
  - `login()` — validate credentials, attempt auth, generate token, return user + token
  - `logout()` — revoke current token
  - `me()` — return authenticated user
  - `updateProfile()` — update name/email
  - `changePassword()` — verify current password, update new password

### 2.6 Form Requests

- [ ] `app/Http/Requests/Auth/RegisterRequest.php`
  - `name`: required, string, max:255
  - `email`: required, email, unique:users
  - `password`: required, string, min:8, confirmed
- [ ] `app/Http/Requests/Auth/LoginRequest.php`
  - `email`: required, email
  - `password`: required, string
- [ ] `app/Http/Requests/Auth/UpdateProfileRequest.php`
  - `name`: required, string, max:255
  - `email`: required, email, unique:users,email,{user_id}
- [ ] `app/Http/Requests/Auth/ChangePasswordRequest.php`
  - `current_password`: required, string
  - `password`: required, string, min:8, confirmed
- [ ] `app/Http/Requests/Auth/ForgotPasswordRequest.php`
  - `email`: required, email
- [ ] `app/Http/Requests/Auth/ResetPasswordRequest.php`
  - `email`: required, email
  - `token`: required, string
  - `password`: required, string, min:8, confirmed

### 2.7 API Resources

- [ ] `app/Http/Resources/V1/UserResource.php`
  - Fields: `id`, `name`, `email`, `email_verified_at`, `created_at`
  - Never expose: `password`, `two_factor_secret`, `remember_token`

### 2.8 Routes

```php
// routes/api.php
Route::prefix('v1/auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::put('me', [AuthController::class, 'updateProfile']);
        Route::post('password', [AuthController::class, 'changePassword']);
    });
});
```

### 2.9 Rate Limiting

- [ ] Login & register: 5 requests per minute per IP
- [ ] Forgot-password: 3 requests per minute per IP

### 2.10 Notifications

- [ ] `app/Notifications/PasswordResetNotification.php` — sends reset link email
- [ ] `app/Notifications/WelcomeNotification.php` — sent on registration (optional)

---

## Acceptance Criteria

- [ ] `POST /api/v1/auth/register` creates a user and returns `201` with user + token
- [ ] `POST /api/v1/auth/login` with valid credentials returns `200` with user + token
- [ ] `POST /api/v1/auth/login` with invalid credentials returns `422`
- [ ] `POST /api/v1/auth/login` rate-limited after 5 attempts per minute
- [ ] `GET /api/v1/auth/me` with valid token returns `200` with user data
- [ ] `GET /api/v1/auth/me` without token returns `401`
- [ ] `POST /api/v1/auth/logout` revokes token, subsequent requests return `401`
- [ ] `PUT /api/v1/auth/me` updates name and email
- [ ] `POST /api/v1/auth/password` with wrong current password returns `422`
- [ ] `POST /api/v1/auth/password` with correct current password updates password
- [ ] `POST /api/v1/auth/forgot-password` sends reset email
- [ ] `POST /api/v1/auth/reset-password` with valid token resets password
- [ ] Password is hashed in database (never plaintext)
- [ ] API token is returned only on login/register (never exposed again)

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_user_can_register` | Registration creates user, returns 201 + token |
| `test_user_cannot_register_with_duplicate_email` | Duplicate email returns 422 |
| `test_user_can_login_with_valid_credentials` | Login returns 200 + token |
| `test_user_cannot_login_with_invalid_credentials` | Wrong password returns 422 |
| `test_login_is_rate_limited` | 6th request in a minute returns 429 |
| `test_authenticated_user_can_get_profile` | GET /auth/me returns user |
| `test_unauthenticated_request_returns_401` | No token → 401 |
| `test_user_can_logout` | Token revoked after logout |
| `test_user_can_change_password` | Password updated with correct current password |
| `test_user_cannot_change_password_with_wrong_current` | Wrong current password → 422 |
| `test_user_can_request_password_reset` | Reset email sent |
| `test_user_can_reset_password_with_valid_token` | Password reset works |
| `test_password_is_hashed_in_database` | DB password != input password |

---

## What This Module Does NOT Include

- Two-factor authentication (Module 23)
- Tenant/workspace membership (Module 03, 04)
- Device/session management (Module 23)
- Re-authentication for sensitive actions (Module 23)
- Login history logging (Module 20)
