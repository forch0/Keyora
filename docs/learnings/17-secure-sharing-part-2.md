# 18 — How Module 18 (Secure External Sharing — Part 2) Was Built

| Field | Value |
|---|---|
| **Module** | 18 — Secure External Sharing — Link Access & Tracking |
| **Date** | 2026-09-02 |
| **Spec** | `docs/modules/18-secure-sharing-part-2.md` |

---

## Goal

Build the public-facing link access endpoints — external users open a secure link, verify password/OTP/email, and view the shared resource. Track all access activity for the link creator.

---

## Decisions Made Before Writing Code

### 1. Signed URLs as access tokens

Used Laravel's `URL::temporarySignedRoute()` to generate access tokens. The token is a signed URL valid for 15 minutes. The `resource` endpoint validates the signature via `$request->hasValidSignature()`. This avoids building a custom JWT or session system.

### 2. Business rules in Actions

All verification and access logic lives in Actions:
- `VerifyLinkAccessAction` — checks password (bcrypt) and/or OTP (bcrypt), returns signed URL token
- `SendLinkEmailVerificationAction` — generates 6-digit code, hashes it, sends email
- `ConfirmLinkEmailVerificationAction` — verifies code, marks email verified, returns token
- `AccessSharedLinkAction` — validates expiration/views, increments count, logs access, auto-revokes

The controller only handles: request validation, link resolution, signed URL verification, and response formatting.

### 3. Email verification reuses OTP infrastructure

The email verification flow reuses the `otp_code_hash` field on `SecureLink` to store the hashed verification code. This avoids adding a separate column. The code is cleared after successful verification (single-use).

### 4. Auto-revoke on view limit

When `views_count` reaches `max_views`, or when a one-time link is viewed once, the link is automatically revoked with `revoke_reason = 'view_limit_reached'`. Subsequent access attempts return `410 Gone`.

### 5. Access logging

Every resource access is logged in `secure_link_accesses` with IP address, user agent, email (if available), and timestamp. The creator can view this log via the authenticated `/activity` endpoint.

### 6. Public routes without auth

The `/api/v1/s/{uuid}/*` routes are NOT behind `auth:sanctum` — they're for external users without platform accounts. Access control is via the signed URL token (for resource access) and password/OTP/email verification.

---

## Files Created

| File | Purpose |
|---|---|
| `app/Models/SecureLinkAccess.php` | Access log model |
| `app/Actions/VerifyLinkAccessAction.php` | Verify password/OTP, return token |
| `app/Actions/AccessSharedLinkAction.php` | Validate, increment views, log, auto-revoke |
| `app/Actions/SendLinkEmailVerificationAction.php` | Send 6-digit code to email |
| `app/Actions/ConfirmLinkEmailVerificationAction.php` | Verify code, return token |
| `app/Services/LinkAccessTokenFactory.php` | Invokable signed URL generator |
| `app/Http/Controllers/Api/V1/PublicLinkController.php` | Public link access controller |
| `app/Http/Requests/SecureLinks/VerifyLinkRequest.php` | Validate password/OTP |
| `app/Http/Requests/SecureLinks/EmailVerifyRequest.php` | Validate email |
| `app/Http/Requests/SecureLinks/EmailConfirmRequest.php` | Validate code |
| `app/Http/Resources/V1/PublicLinkResource.php` | External-facing link info |
| `app/Http/Resources/V1/SecureLinkAccessResource.php` | Access log entry |
| `app/Notifications/SecureLinkEmailVerificationNotification.php` | Email verification code |
| `database/factories/SecureLinkAccessFactory.php` | Factory |
| `tests/Feature/Api/V1/SecureLinks/PublicLinkAccessTest.php` | 18 feature tests |

---

## New Endpoints

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET | `/api/v1/s/{uuid}` | No | Get link info (what verification is required) |
| POST | `/api/v1/s/{uuid}/verify` | No | Verify password and/or OTP |
| POST | `/api/v1/s/{uuid}/email-verify` | No | Send email verification code |
| POST | `/api/v1/s/{uuid}/email-confirm` | No | Confirm email verification code |
| GET | `/api/v1/s/{uuid}/resource` | Signed URL | Access the shared resource |
| GET | `/api/v1/share-links/{link}/activity` | Yes | View access log (creator/admin) |

---

## Bugs Found and Fixed During Implementation

### 1. LinkAccessTokenFactory not callable

**Cause:** The factory had a `create()` method but the action called it as `($this->tokenFactory)($link)` (invokable).

**Fix:** Renamed `create()` to `__invoke()`.

### 2. PHPStan — nullable hash fields

**Cause:** `Hash::check($password, $link->password_hash)` — `password_hash` is `string|null`, but `Hash::check()` expects `string`.

**Fix:** Extracted to a variable and added `is_string($hash)` check before calling `Hash::check()`.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 267 tests, 723 assertions (18 new + 249 from Modules 02-17) |
