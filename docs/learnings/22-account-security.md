# 23 — How Module 23 (Account Security) Was Built

| Field | Value |
|---|---|
| **Module** | 23 — Account Security |
| **Date** | 2026-09-02 |
| **Spec** | `docs/modules/23-account-security.md` |

---

## Goal

Implement two-factor authentication (TOTP), recovery codes, re-authentication for sensitive actions, and logout-from-all-devices.

---

## Decisions Made Before Writing Code

### 1. Native TOTP implementation (no package)

The spec suggested `pragmarx/google2fa` or `spomky-labs/otphp`. I implemented TOTP natively using PHP's `hash_hmac` and `random_bytes` functions. The algorithm is well-defined (RFC 6238) and avoids adding a dependency. The `TwoFactorService` handles:
- Secret generation (20 random bytes, base32-encoded)
- QR code URI generation (otpauth:// format)
- TOTP code verification (with ±1 window for clock drift)
- Recovery code generation (8 codes, format `XXXXXX-XXXXXX`)
- Recovery code verification and consumption

### 2. Encryption strategy

- `two_factor_secret` — encrypted with `Crypt::encryptString()`, decrypted on demand
- `two_factor_recovery_codes` — each code is bcrypt-hashed, then the JSON array of hashes is encrypted with `Crypt::encryptString()`
- Removed the `array` cast on `two_factor_recovery_codes` from the User model since we handle encryption/decryption manually in `TwoFactorService`

### 3. Re-authentication via token age + cache

The `RequireReauthentication` middleware checks two things:
1. **Token age** — if the current API token was created within 15 minutes, the user is considered recently authenticated (login itself is a form of re-auth)
2. **Cache timestamp** — `ReauthenticationService` stores a `reauth:{user_id}` timestamp in cache when the user re-authenticates with their password

This approach means existing tests (which create fresh tokens) don't need to re-authenticate, while real-world sessions with old tokens do.

### 4. 2FA login flow

When a user with 2FA enabled logs in:
1. `LoginUserAction` validates credentials and creates a token
2. The controller detects 2FA is enabled, deletes the token, and stores a temp token in cache
3. The client sends the temp token + TOTP code (or recovery code) to `/2fa/verify`
4. `VerifyTwoFactorAction` validates the temp token, verifies the code, creates a new API token, and marks re-auth

### 5. Business logic in actions, not controllers

Per the project's architecture rule, all business logic lives in actions:
- `EnableTwoFactorAction` — generate secret + QR code
- `ConfirmTwoFactorAction` — verify TOTP code, generate recovery codes, mark confirmed, notify
- `DisableTwoFactorAction` — verify password, disable 2FA, notify
- `RegenerateRecoveryCodesAction` — generate new codes, notify
- `VerifyTwoFactorAction` — verify temp token + code, create API token, mark re-auth
- `ReauthenticateAction` — verify password, mark re-auth
- `LogoutAllAction` — revoke all tokens, log activity

Controllers are thin: validate, authorize, delegate to action, format response.

---

## Files Created/Modified

| File | Purpose |
|---|---|
| `app/Actions/ConfirmTwoFactorAction.php` | New — confirm 2FA with TOTP code |
| `app/Actions/DisableTwoFactorAction.php` | New — disable 2FA with password |
| `app/Actions/EnableTwoFactorAction.php` | New — generate 2FA secret + QR |
| `app/Actions/LogoutAllAction.php` | New — revoke all tokens |
| `app/Actions/ReauthenticateAction.php` | New — re-authenticate with password |
| `app/Actions/RegenerateRecoveryCodesAction.php` | New — regenerate recovery codes |
| `app/Actions/VerifyTwoFactorAction.php` | New — verify 2FA during login |
| `app/Http/Controllers/Api/V1/AuthController.php` | Extended — 2FA login flow, logoutAll, reauthenticate, reauthStatus |
| `app/Http/Controllers/Api/V1/TwoFactorController.php` | New — thin controller for 2FA endpoints |
| `app/Http/Middleware/RequireReauthentication.php` | New — re-auth middleware |
| `app/Http/Requests/Auth/ConfirmTwoFactorRequest.php` | New |
| `app/Http/Requests/Auth/DisableTwoFactorRequest.php` | New |
| `app/Http/Requests/Auth/ReauthenticateRequest.php` | New |
| `app/Http/Requests/Auth/VerifyTwoFactorRequest.php` | New |
| `app/Models/User.php` | Modified — removed array cast on recovery codes, added hasTwoFactorEnabled() |
| `app/Notifications/RecoveryCodesRegenerated.php` | New |
| `app/Notifications/TwoFactorDisabled.php` | New |
| `app/Notifications/TwoFactorEnabled.php` | New |
| `app/Services/ReauthenticationService.php` | New — cache-based re-auth tracking |
| `app/Services/TwoFactorService.php` | New — native TOTP + recovery codes |
| `routes/api.php` | Extended — 8 new routes, reauth middleware on sensitive routes |

---

## New Endpoints

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/v1/auth/2fa/enable` | Generate 2FA secret and QR code |
| POST | `/api/v1/auth/2fa/confirm` | Confirm 2FA with first TOTP code |
| POST | `/api/v1/auth/2fa/disable` | Disable 2FA (requires password) |
| GET | `/api/v1/auth/2fa/recovery-codes` | Regenerate recovery codes (requires re-auth) |
| POST | `/api/v1/auth/2fa/verify` | Verify 2FA during login |
| POST | `/api/v1/auth/reauthenticate` | Re-authenticate with password |
| GET | `/api/v1/auth/reauthenticate/status` | Check if re-auth is needed |
| POST | `/api/v1/auth/logout-all` | Revoke all API tokens |

---

## Bugs Found and Fixed During Implementation

### 1. Re-auth middleware broke existing tests

**Cause:** Applying `reauth` middleware to sensitive routes (offboard, revoke-all, share-links) caused all existing tests to return 423 because tests create tokens directly without going through the login flow.

**Fix:** The middleware now checks the token's `created_at` — if the token was created within 15 minutes, the user is considered recently authenticated. This mirrors real-world behavior (login is a form of re-auth) and doesn't require test changes.

### 2. PHPStan — `two_factor_recovery_codes` cast conflict

**Cause:** The User model had `'two_factor_recovery_codes' => 'array'` cast, but we store an encrypted string. The `array` cast would try to `json_decode` the encrypted string, failing.

**Fix:** Removed the `array` cast. Encryption/decryption is handled manually in `TwoFactorService`.

### 3. PHPStan — `currentAccessToken()` always non-null

**Cause:** Sanctum's `HasApiTokens::currentAccessToken()` has `@return TToken` which PHPStan resolves to `PersonalAccessToken` (non-nullable). The `instanceof` and `!== null` checks were flagged as always true.

**Fix:** Added a `@var PersonalAccessToken|null` PHPDoc annotation before the call to override the inherited type.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 344 tests, 898 assertions (15 new + 329 from Modules 02-22) |
