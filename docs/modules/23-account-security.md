# Module 23 — Account Security

| Field | Value |
|---|---|
| **Module** | 23 |
| **Name** | Account Security (2FA, Recovery Codes, Re-authentication) |
| **Dependencies** | Module 02, Module 20 |
| **Status** | ✅ Complete |

---

## Objective

Build two-factor authentication (TOTP), recovery codes, re-authentication for sensitive actions, and device/session management. This completes the account security features.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| AC-01 | Two-factor authentication (TOTP) | P0 |
| AC-02 | Recovery codes generated on 2FA enablement | P0 |
| AC-03 | Active device management (view and revoke sessions) | P1 → Module 21 |
| AC-04 | Login history | P0 → Module 20 |
| AC-05 | Logout from all devices | P0 |
| AC-08 | Re-authentication for sensitive actions | P1 |

---

## Tasks

### 23.1 Two-Factor Authentication

- [ ] Add 2FA columns to `users` table (if not already from Module 02):
  - `two_factor_secret` — text, nullable (encrypted)
  - `two_factor_recovery_codes` — json, nullable (encrypted)
  - `two_factor_confirmed_at` — timestamp, nullable

- [ ] Create `app/Services/TwoFactorService.php`:
  - `generateSecret(): string` — generate TOTP secret
  - `getQrCodeUri(User $user): string` — generate QR code URI for authenticator apps
  - `verifyCode(User $user, string $code): bool` — verify TOTP code
  - `generateRecoveryCodes(): array` — generate 8 one-time recovery codes
  - `verifyRecoveryCode(User $user, string $code): bool` — verify and consume recovery code

- [ ] Use `pragmarx/google2fa` or `spomky-labs/otphp` package for TOTP

### 23.2 API Endpoints — 2FA

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/auth/2fa/enable` | Generate 2FA secret and QR code |
| `POST` | `/api/v1/auth/2fa/confirm` | Confirm 2FA with first TOTP code |
| `POST` | `/api/v1/auth/2fa/disable` | Disable 2FA (requires current password) |
| `GET` | `/api/v1/auth/2fa/recovery-codes` | Get new recovery codes (requires re-auth) |
| `POST` | `/api/v1/auth/2fa/verify` | Verify 2FA code during login |

### 23.3 2FA Enable Flow

```
1. POST /api/v1/auth/2fa/enable
   → Generate secret, store temporarily (not yet confirmed)
   → Return: QR code URI, secret key (for manual entry)

2. User scans QR code with authenticator app

3. POST /api/v1/auth/2fa/confirm { code: "123456" }
   → Verify code against secret
   → If valid: set two_factor_confirmed_at, generate recovery codes
   → Return: recovery codes (shown once, user must save)

4. 2FA is now active for this user
```

### 23.4 2FA Login Flow

```
1. POST /api/v1/auth/login { email, password }
   → If credentials valid AND 2FA enabled:
     → Return: { "requires_2fa": true, "2fa_token": "temp_token" }
   → If credentials valid AND no 2FA:
     → Return: user + API token (normal flow)

2. POST /api/v1/auth/2fa/verify { 2fa_token, code }
   → Verify TOTP code
   → If valid: return user + API token
   → If invalid: return 422

   OR

   POST /api/v1/auth/2fa/verify { 2fa_token, recovery_code }
   → Verify recovery code
   → If valid: consume code, return user + API token
   → If invalid: return 422
```

### 23.5 Re-Authentication

- [ ] Create `app/Http/Middleware/RequireReauthentication.php`:
  - Checks if user has recently authenticated (within 15 minutes)
  - If not, returns `423` with message "Re-authentication required"
  - User must POST password to re-authenticate

- [ ] API endpoints:

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/auth/reauthenticate` | Re-authenticate with password |
| `GET` | `/api/v1/auth/reauthenticate/status` | Check if re-auth is needed |

- [ ] Sensitive actions requiring re-authentication:
  - Deleting vault items
  - Sharing externally (creating secure links)
  - Exporting data
  - Disabling 2FA
  - Changing password
  - Offboarding employees
  - Emergency revocation

- [ ] Track re-auth timestamp in session/cache: `reauth:{user_id}` → timestamp

### 23.6 Logout from All Devices

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/auth/logout-all` | Revoke all API tokens |

- [ ] Revokes all `personal_access_tokens` for the user
- [ ] Logs `auth.logout_all` activity
- [ ] Returns `204`

### 23.7 Recovery Codes

- [ ] Generate 8 one-time codes (format: `XXXXX-XXXXX`)
- [ ] Store hashed (bcrypt) in `two_factor_recovery_codes` JSON
- [ ] Each code can only be used once (removed from list after use)
- [ ] User can regenerate codes (requires re-authentication)
- [ ] Codes are only shown once on generation

### 23.8 Form Requests

- [ ] `EnableTwoFactorRequest`: (empty — just needs auth)
- [ ] `ConfirmTwoFactorRequest`: `code` (required, string, size:6)
- [ ] `DisableTwoFactorRequest`: `password` (required, string — current password)
- [ ] `VerifyTwoFactorRequest`: `2fa_token` (required), `code` (nullable, string, size:6) OR `recovery_code` (nullable, string)
- [ ] `ReauthenticateRequest`: `password` (required, string — current password)

### 23.9 Middleware Registration

```php
// app/Http/Kernel.php
protected $routeMiddleware = [
    'reauth' => \App\Http\Middleware\RequireReauthentication::class,
];
```

- [ ] Apply `reauth` middleware to sensitive routes:

```php
Route::delete('v1/vault/items/{item}', [PersonalVaultItemController::class, 'destroy'])
    ->middleware('reauth');

Route::post('v1/vault/items/{item}/share-links', [SecureLinkController::class, 'store'])
    ->middleware('reauth');
```

### 23.10 Notifications

- [ ] `app/Notifications/TwoFactorEnabled.php` — confirmation email
- [ ] `app/Notifications/TwoFactorDisabled.php` — alert email
- [ ] `app/Notifications/RecoveryCodesRegenerated.php` — alert email

### 23.11 Routes

```php
Route::middleware('auth:sanctum')->prefix('v1/auth')->group(function () {
    Route::post('2fa/enable', [TwoFactorController::class, 'enable']);
    Route::post('2fa/confirm', [TwoFactorController::class, 'confirm']);
    Route::post('2fa/disable', [TwoFactorController::class, 'disable']);
    Route::get('2fa/recovery-codes', [TwoFactorController::class, 'recoveryCodes']);
    Route::post('2fa/verify', [TwoFactorController::class, 'verify']);
    Route::post('reauthenticate', [AuthController::class, 'reauthenticate']);
    Route::get('reauthenticate/status', [AuthController::class, 'reauthStatus']);
    Route::post('logout-all', [AuthController::class, 'logoutAll']);
});
```

---

## Acceptance Criteria

- [ ] User can enable 2FA — generates QR code and secret
- [ ] User confirms 2FA with first TOTP code → recovery codes generated
- [ ] Recovery codes are shown only once
- [ ] Login with 2FA enabled requires TOTP code or recovery code
- [ ] Correct TOTP code → login succeeds, API token returned
- [ ] Wrong TOTP code → `422`
- [ ] Recovery code works once and is consumed
- [ ] User can disable 2FA (requires current password)
- [ ] User can regenerate recovery codes (requires re-authentication)
- [ ] Sensitive actions require re-authentication if not recently authenticated
- [ ] Re-authentication with correct password resets the timer
- [ ] Re-authentication with wrong password → `422`
- [ ] User can logout from all devices (revokes all tokens)
- [ ] 2FA secret and recovery codes are encrypted in database
- [ ] Notifications sent for 2FA enable/disable/recovery code regeneration

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_user_can_enable_2fa` | POST returns QR code and secret |
| `test_user_can_confirm_2fa` | POST with valid code → confirmed, recovery codes returned |
| `test_login_requires_2fa_code` | 2FA user gets requires_2fa response |
| `test_2fa_code_verification_works` | Correct code → token returned |
| `test_wrong_2fa_code_rejected` | Wrong code → 422 |
| `test_recovery_code_works_once` | Recovery code → login, then code consumed |
| `test_user_can_disable_2fa` | POST with password → 2FA disabled |
| `test_cannot_disable_2fa_without_password` | No password → 422 |
| `test_recovery_codes_can_be_regenerated` | GET with re-auth → new codes |
| `test_sensitive_action_requires_reauth` | After 15 min → 423 |
| `test_reauth_resets_timer` | POST password → sensitive action works |
| `test_reauth_wrong_password` | Wrong password → 422 |
| `test_logout_all_revokes_tokens` | POST → all tokens revoked |
| `test_2fa_secret_encrypted` | DB value is encrypted |
| `test_recovery_codes_encrypted` | DB values are encrypted |

---

## What This Module Does NOT Include

- SMS-based 2FA (post-MVP)
- Hardware key support (WebAuthn/U2F) (post-MVP)
- 2FA enforcement at tenant level (post-MVP)
- Biometric authentication (post-MVP)
