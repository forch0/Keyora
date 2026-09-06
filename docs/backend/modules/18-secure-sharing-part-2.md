# Module 18 — Secure External Sharing: Part 2 (Link Access & Tracking)

| Field | Value |
|---|---|
| **Module** | 18 |
| **Name** | Secure External Sharing — Link Access & Tracking |
| **Dependencies** | Module 17 |
| **Status** | ✅ Complete |

---

## Objective

Build the public-facing link access endpoints — external users open a secure link, verify password/OTP/email, and view the shared resource. Track all access activity for the link creator.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| SS-05 | External access requires email verification | P1 |
| SS-07 | Link expiration on first view | P0 |
| SS-09 | One-time links self-destruct after single view | P0 |
| SS-12 | View link activity (who accessed, when, from where) | P0 |

---

## Tasks

### 18.1 Public API Endpoints (No Auth Required)

These endpoints are NOT behind `auth:sanctum` — they're for external users who don't have platform accounts.

| Method | Endpoint | Description | Auth |
|---|---|---|---|
| `GET` | `/api/v1/s/{uuid}` | Get link info (what resource, what protections required) | No |
| `POST` | `/api/v1/s/{uuid}/verify` | Verify password and/or OTP | No |
| `POST` | `/api/v1/s/{uuid}/email-verify` | Send email verification code | No |
| `POST` | `/api/v1/s/{uuid}/email-confirm` | Confirm email verification code | No |
| `GET` | `/api/v1/s/{uuid}/resource` | Access the shared resource (after verification) | No (session-based) |

### 18.2 Link Access Flow

```
1. External user opens link: GET /api/v1/s/{uuid}
   → Returns: resource type, resource name, required verifications (password? OTP? email?)
   → Does NOT return resource content yet

2. If password required:
   → POST /api/v1/s/{uuid}/verify { password: "..." }
   → If OTP also required, include otp_code in same request
   → Server verifies password (bcrypt) and/or OTP (bcrypt)
   → Returns: temporary access token (signed URL or session token)

3. If email verification required (no password/OTP):
   → POST /api/v1/s/{uuid}/email-verify { email: "user@example.com" }
   → Server sends 6-digit code to email
   → POST /api/v1/s/{uuid}/email-confirm { code: "123456" }
   → Returns: temporary access token

4. Access resource: GET /api/v1/s/{uuid}/resource
   → Must include access token (from step 2 or 3)
   → Server validates token, checks expiration, view limits
   → Returns: decrypted resource content
   → Increments views_count
   → Sets first_viewed_at if not set
   → If one-time link: revokes after this view
   → Logs access (IP, user agent, timestamp)
```

### 18.3 Link Access Tokens

- [ ] Use Laravel's `URL::temporarySignedRoute()` or custom JWT-like token:
  - Token contains: link UUID, expiry (15 minutes), verification status
  - Token is signed with `APP_KEY`
  - Token passed as query parameter or bearer token

### 18.4 Controller

- [ ] `app/Http/Controllers/Api/V1/PublicLinkController.php`:
  - `show()` — return link info (what's required to access)
  - `verify()` — verify password and/or OTP, return access token
  - `sendEmailVerification()` — send verification code to email
  - `confirmEmailVerification()` — verify code, return access token
  - `resource()` — return decrypted resource content (requires valid access token)

### 18.5 Link Access Tracking

- [ ] Create `secure_link_accesses` migration:

```
secure_link_accesses
  id              -- bigIncrements
  secure_link_id  -- foreignId (constrained, cascadeOnDelete)
  ip_address      -- string
  user_agent      -- string
  email           -- string, nullable (if email verification used)
  accessed_at     -- timestamp
  created_at

  index(secure_link_id)
  index(accessed_at)
```

- [ ] Create `SecureLinkAccess` model
- [ ] Record every access in `secure_link_accesses` table
- [ ] Include: IP address, user agent, email (if provided), timestamp

### 18.6 Link Activity Endpoint (For Link Creator)

| Method | Endpoint | Description | Auth |
|---|---|---|---|
| `GET` | `/api/v1/share-links/{link}/activity` | View who accessed the link and when | Yes |

- [ ] Returns paginated list of `SecureLinkAccess` records
- [ ] Only accessible by link creator or admin

### 18.7 Expiration & View Limit Enforcement

- [ ] On every resource access:
  - Check `revoked_at IS NULL` → else `410 Gone`
  - Check `expires_at` not in past → else `410 Gone`
  - If `first_viewed_at` is set and `first_view_expires_hours` is set:
    - Check `first_viewed_at + first_view_expires_hours > now()` → else `410 Gone`
  - Check `views_count < max_views` (if `max_views` is set) → else `410 Gone`
  - If `is_one_time` and `views_count >= 1` → `410 Gone`

### 18.8 First View Tracking

- [ ] On first resource access:
  - If `first_viewed_at IS NULL`, set `first_viewed_at = now()`
  - This starts the countdown for `first_view_expires_hours`

### 18.9 View Count & Auto-Revoke

- [ ] Increment `views_count` on every resource access
- [ ] If `views_count >= max_views` (and `max_views` is set):
  - Set `revoked_at = now()`, `revoke_reason = 'view_limit_reached'`
  - Subsequent access attempts return `410 Gone`
- [ ] If `is_one_time = true` (max_views = 1):
  - After first view, link is auto-revoked

### 18.10 API Resources

- [ ] `PublicLinkResource.php` (for external users):
  - `uuid`, `resource_type`, `resource_name`, `requires_password`, `requires_otp`, `requires_email_verification`, `is_expired`, `is_revoked`, `views_remaining`, `expires_at`

- [ ] `SecureLinkAccessResource.php` (for link creator):
  - `id`, `ip_address`, `user_agent`, `email`, `accessed_at`

### 18.11 Routes

```php
// Public routes (no auth)
Route::prefix('v1/s/{uuid}')->group(function () {
    Route::get('/', [PublicLinkController::class, 'show']);
    Route::post('/verify', [PublicLinkController::class, 'verify']);
    Route::post('/email-verify', [PublicLinkController::class, 'sendEmailVerification']);
    Route::post('/email-confirm', [PublicLinkController::class, 'confirmEmailVerification']);
    Route::get('/resource', [PublicLinkController::class, 'resource']);
});

// Authenticated routes (for creators)
Route::middleware(['auth:sanctum', 'tenant.resolve'])->group(function () {
    Route::get('v1/share-links/{link}/activity', [SecureLinkController::class, 'activity']);
});
```

---

## Acceptance Criteria

- [ ] External user can open a link via `GET /api/v1/s/{uuid}` and see what verification is required
- [ ] Resource content is NOT returned until verification is complete
- [ ] Password-protected link requires correct password → returns access token
- [ ] Wrong password → `422` with error
- [ ] OTP-protected link requires correct OTP code → returns access token
- [ ] Wrong OTP → `422` with error
- [ ] Email verification sends code to provided email
- [ ] Correct email verification code → returns access token
- [ ] After verification, `GET /resource` returns decrypted resource content
- [ ] Each access increments `views_count`
- [ ] First access sets `first_viewed_at` (starts countdown for first-view expiration)
- [ ] One-time link is auto-revoked after first view → second access returns `410`
- [ ] Link with `max_views` is auto-revoked when limit reached → `410`
- [ ] Expired link returns `410 Gone`
- [ ] Revoked link returns `410 Gone`
- [ ] Every access is logged in `secure_link_accesses` (IP, user agent, timestamp)
- [ ] Link creator can view access activity via authenticated endpoint
- [ ] Non-creator cannot view access activity → `403`

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_can_get_link_info` | GET /s/{uuid} returns link info without content |
| `test_password_verification_works` | Correct password → access token |
| `test_wrong_password_rejected` | Wrong password → 422 |
| `test_otp_verification_works` | Correct OTP → access token |
| `test_wrong_otp_rejected` | Wrong OTP → 422 |
| `test_email_verification_flow` | Send code → confirm code → access token |
| `test_resource_access_after_verification` | GET /resource returns content |
| `test_resource_access_without_verification` | No token → 403 |
| `test_view_count_increments` | Each access increments views_count |
| `test_first_view_sets_timestamp` | First access sets first_viewed_at |
| `test_one_time_link_self_destructs` | Second access → 410 |
| `test_max_views_enforced` | Over limit → 410 |
| `test_expired_link_returns_410` | Past expires_at → 410 |
| `test_revoked_link_returns_410` | Revoked → 410 |
| `test_first_view_expiration` | Past first_viewed_at + hours → 410 |
| `test_access_logged` | secure_link_accesses record created |
| `test_creator_can_view_activity` | GET /activity returns access log |
| `test_non_creator_cannot_view_activity` | Non-creator → 403 |

---

## What This Module Does NOT Include

- Activity logging to the main audit log (Module 20)
- In-platform document viewer (post-MVP)
- Dynamic watermarking (post-MVP)
