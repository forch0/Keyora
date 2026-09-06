# 15 — How Module 15 (Access Requests) Was Built

| Field | Value |
|---|---|
| **Module** | 15 — Access Requests |
| **Date** | 2026-09-02 |
| **Spec** | `docs/modules/15-access-requests.md` |

---

## Goal

Build the access request workflow — users can request access to resources they don't have access to. Resource owners can approve (with optional modifications to permission/duration), reject (with optional note), or the requester can cancel their own pending request.

---

## Decisions Made Before Writing Code

### 1. Reuse GrantAccessAction on approval

When a request is approved, `ApproveAccessRequestAction` calls the existing `GrantAccessAction` to create the actual `AccessGrant`. This reuses all the existing sharing infrastructure — no duplication of grant logic.

### 2. Duration presets extended

Module 14 added `15m`, `30m`, `1h`, `24h` presets. Module 15 extends the request form to also accept `7d`, `30d`, and `permanent`. The `ApproveAccessRequestAction` handles these custom durations by converting them to `Carbon` expiry times.

### 3. No BelongsToTenant on AccessRequest

The `AccessRequest` model uses `HasFactory` but not `BelongsToTenant`. The model has a `tenant_id` column and a `tenant()` relationship, but queries are scoped manually in the controller via `scopeForUser()`. This avoids the global scope throwing when no tenant context is set.

### 4. Route parameter naming

The route parameter must match the controller method parameter name for Laravel's implicit route model binding. Used `{accessRequest}` (not `{request}`) to avoid conflicts with `Request $request` in controller methods.

### 5. Notifications sent synchronously from controller

Notifications are sent directly from the controller (not via queued jobs) to keep the implementation simple. The notification classes implement `ShouldQueue` so they'll be queued by Laravel's queue system if configured.

---

## Files Created

| File | Purpose |
|---|---|
| `app/Models/AccessRequest.php` | Request model with morphTo resource, scopes |
| `app/Actions/CreateAccessRequestAction.php` | Validates no existing access/pending, creates request |
| `app/Actions/ApproveAccessRequestAction.php` | Approves, creates grant via GrantAccessAction, supports overrides |
| `app/Actions/RejectAccessRequestAction.php` | Rejects with optional note |
| `app/Http/Requests/AccessRequests/CreateAccessRequestRequest.php` | Validate creation |
| `app/Http/Requests/AccessRequests/ApproveAccessRequestRequest.php` | Validate approval overrides |
| `app/Http/Requests/AccessRequests/RejectAccessRequestRequest.php` | Validate rejection note |
| `app/Http/Resources/V1/AccessRequestResource.php` | API resource |
| `app/Policies/AccessRequestPolicy.php` | view/approve/reject/cancel authorization |
| `app/Http/Controllers/Api/V1/AccessRequestController.php` | Full CRUD + approve/reject/history |
| `app/Events/AccessRequested.php` | Event on request submission |
| `app/Events/AccessRequestApproved.php` | Event on approval (carries grant) |
| `app/Events/AccessRequestRejected.php` | Event on rejection |
| `app/Notifications/AccessRequestReceived.php` | Notifies resource owner |
| `app/Notifications/AccessRequestApprovedNotification.php` | Notifies requester on approval |
| `app/Notifications/AccessRequestRejectedNotification.php` | Notifies requester on rejection |
| `database/factories/AccessRequestFactory.php` | Factory |
| `tests/Feature/Api/V1/AccessRequests/AccessRequestTest.php` | 17 feature tests |

---

## New Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/access-requests` | List requests (sent + received, filterable) |
| POST | `/api/v1/access-requests` | Create access request |
| GET | `/api/v1/access-requests/history` | Full request history (paginated) |
| GET | `/api/v1/access-requests/{accessRequest}` | Get request details |
| PUT | `/api/v1/access-requests/{accessRequest}/approve` | Approve (optionally modify) |
| PUT | `/api/v1/access-requests/{accessRequest}/reject` | Reject with optional note |
| DELETE | `/api/v1/access-requests/{accessRequest}` | Cancel (requester only, pending only) |

---

## Bugs Found and Fixed During Implementation

### 1. Route model binding — parameter name mismatch

**Cause:** Routes used `{request}` but controller methods used `AccessRequest $accessRequest`. Laravel's implicit binding requires matching names.

**Fix:** Changed route parameters to `{accessRequest}`.

### 2. PHPStan — `Rule` vs `Rule\In` return type

**Cause:** The `@return` annotation used `Rule` but `Rule::in()` returns `Illuminate\Validation\Rules\In`.

**Fix:** Added `In` import and updated annotations to `array<int, Rule|In|string>`.

### 3. Tenant factory slug collision

**Cause:** `createTenant()` uses a fixed slug `test-company`. Creating a second tenant in the same test caused a unique constraint violation.

**Fix:** Passed custom `name` and `slug` attributes for the second tenant.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 222 tests, 606 assertions (17 new + 205 from Modules 02-14) |
