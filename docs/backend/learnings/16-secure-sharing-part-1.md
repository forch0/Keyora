# 17 — How Module 17 (Secure External Sharing — Part 1) Was Built

| Field | Value |
|---|---|
| **Module** | 17 — Secure External Sharing — Link Creation |
| **Date** | 2026-09-02 |
| **Spec** | `docs/modules/17-secure-sharing-part-1.md` |

---

## Goal

Build the secure share link system — create password-protected, OTP-protected, expiring, and one-time links for sharing resources externally with non-platform users.

---

## Decisions Made Before Writing Code

### 1. Business rules in Actions, not controllers

All link creation logic lives in `CreateSecureLinkAction`:
- Permission verification (share permission)
- UUID generation
- Password hashing (bcrypt)
- OTP generation (6-digit code, bcrypt hashed)
- OTP email sending
- Creator notification
- Event dispatch

The controller only handles: authentication, authorization, delegation, and response formatting.

### 2. Resource type-specific controller methods

Laravel's container can't resolve `Model $resource` (abstract class). Instead of one `store()` method, the controller has `storeForVaultItem()`, `storeForFile()`, `storeForNote()` — each with a concrete type hint. These delegate to a private `store()` helper.

### 3. OTP code only in creation response

The `SecureLinkResource` has a `withOtp(string $otpCode)` method that includes the OTP code only when explicitly called. The list/show endpoints never call this method, so the OTP code is never exposed after creation.

### 4. One-time link sets max_views = 1

When `is_one_time` is true, the action automatically sets `max_views = 1`. The form request validates that `is_one_time` and `max_views` are mutually exclusive.

### 5. No BelongsToTenant on SecureLink

The `SecureLink` model doesn't use `BelongsToTenant` — it has a `tenant_id` column and `tenant()` relationship, but queries are scoped manually. This avoids global scope issues when resolving links by UUID (which happens without tenant context in Module 18).

---

## Files Created

| File | Purpose |
|---|---|
| `app/Models/SecureLink.php` | Model with UUID, morph resource, scopes, helpers |
| `app/Actions/CreateSecureLinkAction.php` | Create link with all options, send OTP, notify |
| `app/Http/Controllers/Api/V1/SecureLinkController.php` | Thin controller (store/index/destroy per type) |
| `app/Http/Requests/SecureLinks/CreateSecureLinkRequest.php` | Validate all link options |
| `app/Http/Resources/V1/SecureLinkResource.php` | API resource with conditional OTP |
| `app/Policies/SecureLinkPolicy.php` | view/delete authorization |
| `app/Events/SecureLinkCreated.php` | Event with link + creator |
| `app/Notifications/SecureLinkOtpNotification.php` | OTP email to recipient |
| `app/Notifications/SecureLinkCreatedNotification.php` | Confirmation to creator (database) |
| `database/factories/SecureLinkFactory.php` | Factory |
| `tests/Feature/Api/V1/SecureLinks/SecureLinkTest.php` | 15 feature tests |

---

## New Endpoints

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/v1/vault/items/{item}/share-links` | Create link for vault item |
| GET | `/api/v1/vault/items/{item}/share-links` | List links for vault item |
| POST | `/api/v1/files/{file}/share-links` | Create link for file |
| GET | `/api/v1/files/{file}/share-links` | List links for file |
| POST | `/api/v1/notes/{note}/share-links` | Create link for note |
| GET | `/api/v1/notes/{note}/share-links` | List links for note |
| DELETE | `/api/v1/share-links/{link}` | Revoke a link |

---

## Bugs Found and Fixed During Implementation

### 1. Container can't resolve abstract Model

**Cause:** Controller method `store(CreateSecureLinkRequest $request, Model $resource)` — Laravel's container can't instantiate the abstract `Model` class.

**Fix:** Created separate methods per resource type (`storeForVaultItem`, `storeForFile`, `storeForNote`) with concrete type hints, delegating to a private `store()` helper.

### 2. OTP code not in response

**Cause:** `SecureLinkResource::withOtp()` set a flag but didn't pass the actual OTP code (which isn't stored on the model — only the hash is).

**Fix:** Changed `withOtp()` to accept the OTP code string and store it on the resource instance.

### 3. `withoutTenant()` on model without trait

**Cause:** Controller called `SecureLink::withoutTenant()` but the model doesn't use `BelongsToTenant`.

**Fix:** Used `SecureLink::query()` instead.

### 4. PHPStan — `empty()` on non-empty array offset

**Cause:** `empty($data['recipient_email'])` on a `non-empty-array` always evaluates to false per PHPStan.

**Fix:** Extracted to a variable and used null/empty-string checks instead.

### 5. Notification fake with AnonymousNotifiable

**Cause:** `Notification::assertSentTo(AnonymousNotifiable::class, ...)` passed the class name as a string, but the fake expects an object.

**Fix:** Used `new AnonymousNotifiable` instead.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 249 tests, 680 assertions (15 new + 234 from Modules 02-16) |
