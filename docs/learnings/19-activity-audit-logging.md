# 20 — How Module 20 (Activity & Audit Logging) Was Built

| Field | Value |
|---|---|
| **Module** | 20 — Activity & Audit Logging |
| **Date** | 2026-09-02 |
| **Spec** | `docs/modules/20-activity-audit-logging.md` |

---

## Goal

Build the append-only audit logging system that records every significant action across the platform — vault access, sharing, revocation, logins, file downloads, etc.

---

## Decisions Made Before Writing Code

### 1. Append-only model

`ActivityLog` has `UPDATED_AT = null` — no updates, no soft deletes. Records can only be created or cleaned up by the retention command.

### 2. ActivityLogger service with sync + async

The `ActivityLogger` service has two methods:
- `log()` — writes synchronously (used in tests and immediate writes)
- `dispatch()` — dispatches `LogActivity` job to the `audit` queue

The service auto-resolves `tenant_id` from `TenantManager` and `ip_address`/`user_agent` from the request.

### 3. Event listeners for all prior module events

Created 12 event listeners that call `ActivityLogger::log()`:
- `LogAccessGranted`, `LogAccessRevoked`, `LogAccessUpdated`, `LogAccessExpired`
- `LogEmergencyRevoked`, `LogAllAccessRevokedForResource`
- `LogAccessRequested`, `LogAccessRequestApproved`, `LogAccessRequestRejected`
- `LogFileUploaded`, `LogSecureLinkCreated`, `LogResourceViewed`

All registered in `EventServiceProvider`.

### 4. Direct logging in controllers/actions

Some actions don't have events, so logging is done directly:
- `AuthController` — login, logout, register, password change
- `CreateVaultItemAction` — vault item creation
- `PersonalVaultItemController::show()` — vault item view
- `SecureFileController::download()` — file download
- `AccessSharedLinkAction` — secure link access

### 5. TenantPolicy auto-discovery

Creating `TenantPolicy` caused Laravel to auto-discover it for the `Tenant` model. This broke existing tests that relied on the default policy behavior. Fixed by adding `view()` and `delete()` methods to the policy.

### 6. Resource history route binding

The `resourceHistory` method accepts a raw ID (not model-bound) and manually resolves the resource. This avoids route model binding conflicts with existing `vault/items/{item}` routes.

---

## Files Created/Modified

| File | Purpose |
|---|---|
| `app/Models/ActivityLog.php` | Append-only audit log model |
| `app/Services/ActivityLogger.php` | Logger service (sync + async) |
| `app/Jobs/LogActivity.php` | Async queue job |
| `app/Http/Controllers/Api/V1/ActivityLogController.php` | 4 endpoints |
| `app/Http/Resources/V1/ActivityLogResource.php` | API resource |
| `app/Policies/TenantPolicy.php` | View/delete/activity feed gates |
| `app/Console/Commands/CleanupActivityLogs.php` | Retention command |
| `app/Listeners/LogAccessGranted.php` | Updated from stub |
| `app/Listeners/LogAccessRevoked.php` | Updated from stub |
| `app/Listeners/LogAccessExpired.php` | New |
| `app/Listeners/LogAccessUpdated.php` | New |
| `app/Listeners/LogEmergencyRevoked.php` | New |
| `app/Listeners/LogAllAccessRevokedForResource.php` | New |
| `app/Listeners/LogAccessRequested.php` | New |
| `app/Listeners/LogAccessRequestApproved.php` | New |
| `app/Listeners/LogAccessRequestRejected.php` | New |
| `app/Listeners/LogFileUploaded.php` | New |
| `app/Listeners/LogSecureLinkCreated.php` | New |
| `app/Listeners/LogResourceViewed.php` | New |
| `app/Providers/EventServiceProvider.php` | Registered all listeners |
| `app/Providers/AppServiceProvider.php` | Registered viewActivityFeed gate |
| `app/Http/Controllers/Api/V1/AuthController.php` | Login/logout/register logging |
| `app/Http/Controllers/Api/V1/SecureFileController.php` | Download logging |
| `app/Http/Controllers/Api/V1/PersonalVaultItemController.php` | View logging |
| `app/Actions/CreateVaultItemAction.php` | Creation logging |
| `app/Actions/AccessSharedLinkAction.php` | Link access logging |
| `database/factories/ActivityLogFactory.php` | Factory |
| `tests/Feature/Api/V1/Activity/ActivityLogTest.php` | 18 feature tests |

---

## New Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/activity-logs` | Personal activity history |
| GET | `/api/v1/tenants/{tenant}/activity-logs` | Company feed (admin/owner) |
| GET | `/api/v1/tenants/{tenant}/members/{user}/activity-logs` | Employee overview (admin/owner) |
| GET | `/api/v1/vault/items/{item}/activity-logs` | Resource history |
| GET | `/api/v1/files/{file}/activity-logs` | File history |

---

## Bugs Found and Fixed During Implementation

### 1. TenantPolicy auto-discovery broke existing tests

**Cause:** Creating `App\Policies\TenantPolicy` caused Laravel to auto-discover it for `App\Models\Tenant`. Existing tests that relied on default policy behavior (allow all) now got 403.

**Fix:** Added `view()` (any member) and `delete()` (owner only) methods to `TenantPolicy`.

### 2. Route model binding conflict

**Cause:** `vault/items/{item}/activity-logs` route with `mixed $item` parameter still triggered implicit binding.

**Fix:** Changed controller to accept raw ID and manually resolve the resource.

### 3. AccessRequest model field names

**Cause:** Listeners used `$event->request->requestedBy` and `$event->request->approvedBy` but the model uses `requester_id` and `reviewed_by`.

**Fix:** Used `User::find($event->request->requester_id)` instead.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 300 tests, 788 assertions (18 new + 282 from Modules 02-19) |
