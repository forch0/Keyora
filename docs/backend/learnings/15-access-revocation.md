# 16 — How Module 16 (Access Revocation) Was Built

| Field | Value |
|---|---|
| **Module** | 16 — Access Revocation |
| **Date** | 2026-09-02 |
| **Spec** | `docs/modules/16-access-revocation.md` |

---

## Goal

Build comprehensive revocation controls — revoke individual access (existing), team access, bulk resource access, emergency revoke (break-glass), and automatic revocation on offboarding.

---

## Decisions Made Before Writing Code

### 1. Business rules in Actions, not controllers

All revocation logic lives in Actions:
- `EmergencyRevokeAction` — revokes all user + team grants, sends notification, dispatches event
- `RevokeAllAccessAction` — revokes all grants for a resource, notifies affected users
- `RevokeTeamAccessAction` — revokes team grants for a specific resource
- `OffboardEmployeeAction` — calls EmergencyRevokeAction + detaches from tenant

The controller only handles: authentication, authorization (admin/manage checks), calling the action, and formatting the JSON response.

### 2. Soft-delete only (audit trail)

All revocations set `revoked_at`, `revoked_by`, and `revoke_reason` — no hard deletes. This preserves the full audit trail for compliance.

### 3. Secure links deferred to Module 17

The spec mentions revoking secure links in EmergencyRevokeAction, but SecureLink doesn't exist yet (Module 17). The action has a placeholder comment and will be extended when Module 17 is implemented.

### 4. OffboardEmployeeAction as a separate action

Rather than embedding offboarding logic in a controller, it's a separate action that composes `EmergencyRevokeAction` and tenant detachment. This makes it reusable from admin tools, scheduled jobs, or future HR integrations.

---

## Files Created

| File | Purpose |
|---|---|
| `app/Actions/EmergencyRevokeAction.php` | Revoke all user + team grants, notify, dispatch event |
| `app/Actions/RevokeAllAccessAction.php` | Revoke all grants for a resource, notify affected users |
| `app/Actions/RevokeTeamAccessAction.php` | Revoke team grants for a resource |
| `app/Actions/OffboardEmployeeAction.php` | Offboard: revoke all + detach from tenant |
| `app/Http/Controllers/Api/V1/EmergencyRevokeController.php` | Thin controller for revocation endpoints |
| `app/Http/Requests/AccessRequests/EmergencyRevokeRequest.php` | Validate reason field |
| `app/Events/EmergencyRevoked.php` | Event with target user, revoked by, count |
| `app/Events/AllAccessRevokedForResource.php` | Event with resource, revoked by, count |
| `app/Notifications/AccessRevokedNotification.php` | Notifies affected user (mail + database) |
| `tests/Feature/Api/V1/Access/AccessRevocationTest.php` | 12 feature tests |

---

## New Endpoints

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/v1/tenants/{tenant}/members/{user}/revoke-all` | Emergency revoke ALL access for a user |
| POST | `/api/v1/vault/items/{item}/access/revoke-all` | Revoke all access for a vault item |
| POST | `/api/v1/vault/items/{item}/access/revoke-team/{team}` | Revoke team access for a vault item |
| POST | `/api/v1/files/{file}/access/revoke-all` | Revoke all access for a file |
| POST | `/api/v1/notes/{note}/access/revoke-all` | Revoke all access for a note |

---

## Bugs Found and Fixed During Implementation

### 1. PHPStan — `User::find()` can return null

**Cause:** `User::find($userId)` returns `User|null`, but `notify()` was called without a null check that PHPStan recognized.

**Fix:** Used `$affectedUser instanceof User` instead of `$affectedUser !== null`.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 234 tests, 636 assertions (12 new + 222 from Modules 02-15) |
