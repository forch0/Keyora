# 09 — How Module 09 (Permission System Part 2) Was Built

| Field | Value |
|---|---|
| **Module** | 09 — Permission System Part 2 (Sharing & Access Management) |
| **Date** | 2026-09-02 |
| **Spec** | `docs/modules/09-permission-system-part-2.md` |

---

## Goal

Build the sharing endpoints that allow users to grant, update, and revoke access to resources. Includes bulk team sharing, notifications, and event dispatch for audit logging.

---

## Decisions Made Before Writing Code

### 1. Actions check permission via AccessResolver

All three Actions (Grant, Update, Revoke) verify the user has `share` permission via `AccessResolver::can()`. This keeps permission logic centralized and consistent with Module 08.

### 2. Duplicate grant updates existing

If an active grant already exists for the same subject + resource, `GrantAccessAction` updates it instead of creating a duplicate. This prevents grant proliferation and matches the spec.

### 3. Soft delete on revoke

Revoking sets `revoked_at`, `revoked_by`, and `revoke_reason` — the grant row stays in the database for audit history. Hard delete would lose the revocation record.

### 4. EventServiceProvider with explicit $listen

Laravel 11+ supports auto-discovery, but explicit mapping in `EventServiceProvider` is clearer and ensures listeners are always registered in the correct order.

### 5. NotifyAccessGranted notifies team members

For team grants, all team members receive the notification. For tenant-wide grants, no notification is sent (too noisy for company-wide access).

---

## Files Created / Modified

### Actions

| File | Purpose |
|---|---|
| `GrantAccessAction.php` | Verify share permission, deduplicate, create grant, dispatch event |
| `UpdateAccessAction.php` | Verify share permission, update permission/constraints, dispatch event |
| `RevokeAccessAction.php` | Verify share permission, soft-delete via revoked_at, dispatch event |

### Events

| File | Purpose |
|---|---|
| `AccessGranted.php` | Carries AccessGrant + User (grantedBy) |
| `AccessUpdated.php` | Carries AccessGrant + User (updatedBy) |
| `AccessRevoked.php` | Carries AccessGrant + User (revokedBy) + reason |

### Notification + Listeners

| File | Purpose |
|---|---|
| `AccessGrantedNotification.php` | Database notification to grantee |
| `LogAccessGranted.php` | Stub for Module 20 activity log |
| `LogAccessRevoked.php` | Stub for Module 20 activity log |
| `NotifyAccessGranted.php` | Sends notification to user or team members |

### HTTP Layer

| File | Purpose |
|---|---|
| `GrantAccessRequest.php` | Validates subject_type/id, permission, temporal constraints, tenant membership |
| `UpdateAccessRequest.php` | Validates permission, expires_at, max_views |
| `AccessGrantPolicy.php` | create/update/delete check share permission |
| `AccessGrantController.php` | Extended with store, bulkStore, update, destroy |

### Infrastructure

| File | Purpose |
|---|---|
| `EventServiceProvider.php` | Explicit event-listener mappings |
| `bootstrap/app.php` | Registers EventServiceProvider via withProviders |

---

## All New Endpoints

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/v1/vault/items/{item}/access` | Grant access |
| POST | `/api/v1/vault/items/{item}/access/bulk` | Bulk grant to teams |
| PUT | `/api/v1/vault/items/{item}/access/{grant}` | Update permission |
| DELETE | `/api/v1/vault/items/{item}/access/{grant}` | Revoke access |

---

## Bugs Found and Fixed During Implementation

### 1. `no such table: notifications`

**Cause:** Laravel's notifications table migration hadn't been generated.

**Fix:** Ran `php artisan notifications:table` + `php artisan migrate`.

### 2. PHPStan: `User` not found in notification

**Cause:** `@param User` annotation resolved to `App\Notifications\User` (wrong namespace).

**Fix:** Changed to `@param object` and used `getAttribute()` for type-safe access.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 132 tests, 370 assertions (13 new + 119 from Modules 02-08) |

---

## Key Takeaways

1. **Deduplication in Actions** — `GrantAccessAction` checks for existing active grants before creating. This prevents duplicate rows and keeps the grant table clean.

2. **Soft delete for audit** — revoking sets `revoked_at` instead of hard-deleting. The grant remains in the database for audit history and "who had access" queries.

3. **Event-driven architecture** — grant/update/revoke all dispatch events. Listeners handle notifications (now) and activity logging (Module 20). This decouples side effects from the core action.

4. **Custom form request validation** — `GrantAccessRequest` validates that the subject (User/Team/Tenant) belongs to the current tenant, preventing cross-tenant grants.

5. **Notifications table** — Laravel's database notification channel requires a `notifications` table. Don't forget `php artisan notifications:table` when using `->notify()`.
