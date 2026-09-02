# 08 — How Module 08 (Permission System Part 1) Was Built

| Field | Value |
|---|---|
| **Module** | 08 — Permission System Part 1 (Core) |
| **Date** | 2026-09-02 |
| **Spec** | `docs/modules/08-permission-system-part-1.md` |

---

## Goal

Build the polymorphic access grant system and `AccessResolver` service. This is the foundation for all sharing, temporary access, and permission checks across the platform.

---

## Decisions Made Before Writing Code

### 1. Polymorphic grants (single table)

A single `access_grants` table with polymorphic `grantable` (VaultItem, SecureFile, etc.) and `subject` (User, Team, Tenant) avoids duplicate tables for each resource/subject combination.

### 2. Permission enum with parallel ranks

`download` and `edit` are both rank 2 — they're parallel, not cumulative. `manage` (rank 4) satisfies all. `share` (rank 3) satisfies view + download. The `satisfies()` method handles this non-linear hierarchy explicitly rather than using a simple rank comparison.

### 3. AccessResolver as scoped singleton

Depends on `TenantManager` for current tenant context. Registered as `scoped` (per-request) so it picks up the correct tenant context for each request.

### 4. Owner check first

The resource owner (user_id or created_by column) gets `Manage` permission without any explicit grant. This is checked first in `getPermission()` before looking up grants.

### 5. Backward-compatible policy

`VaultItemPolicy` checks `AccessResolver` first, then falls back to team membership and admin privileges. This ensures Module 07's behavior still works for items without explicit grants.

---

## Files Created / Modified

### Migration

| File | Purpose |
|---|---|
| `2026_09_02_100000_create_access_grants_table.php` | Polymorphic grants with temporal/view constraints |

### Model

| File | What changed |
|---|---|
| `AccessGrant.php` | Created — BelongsToTenant, morphTo grantable/subject, grantedBy/revokedBy, active/expired/revoked scopes, isActive/isExpired/isRevoked/viewLimitReached/hasStarted helpers |

### Enum

| File | Purpose |
|---|---|
| `Permission.php` | View/Download/Edit/Share/Manage with rank() and satisfies() — download and edit are parallel |

### Service

| File | Purpose |
|---|---|
| `AccessResolver.php` | can(), getPermission(), whoHasAccess(), whatDoesUserHaveAccessTo() — checks owner, direct, team, tenant grants with temporal/view constraints |

### HTTP Layer

| File | Purpose |
|---|---|
| `AccessGrantResource.php` | Transforms grant with subject details, permission, temporal fields |
| `AccessGrantController.php` | index (list grants), summary (grouped by subject type) |

### Modified

| File | What changed |
|---|---|
| `VaultItemPolicy.php` | Now delegates to AccessResolver for view/update/delete |
| `AppServiceProvider.php` | Registers AccessResolver as scoped singleton |
| `routes/api.php` | 2 new routes for access grant viewing |

---

## All New Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/vault/items/{item}/access` | List all active access grants |
| GET | `/api/v1/vault/items/{item}/access/summary` | Summary grouped by subject type |

---

## Bugs Found and Fixed During Implementation

### 1. `isOwner` always returns false

**Cause:** Used `in_array('user_id', $resource->getAttributes())` which checks attribute **values**, not column names.

**Fix:** Changed to `array_key_exists('user_id', $resource->getAttributes())`.

### 2. PHPStan: `permission` is `Permission|string`

**Cause:** The model cast makes `permission` a `Permission` enum at runtime, but PHPStan sees the raw database column type as `string`.

**Fix:** Added `@var Permission` annotation when accessing the cast attribute in loops.

### 3. PHPStan: `sortByDesc` callback type mismatch

**Cause:** Collection type inference doesn't match the callable signature expected by `sortByDesc`.

**Fix:** Replaced collection chain with a simple `foreach` loop for finding the highest permission.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 119 tests, 338 assertions (15 new + 104 from Modules 02-07) |

---

## Key Takeaways

1. **Polymorphic grants** — a single `access_grants` table handles all resource types and subject types via `morphTo()`. This is more flexible than separate tables per resource.

2. **Parallel permissions** — `download` and `edit` are both rank 2. Neither satisfies the other. The `satisfies()` method handles this explicitly rather than using a simple rank comparison.

3. **Owner-first check** — checking ownership before grants avoids needing an explicit "owner grant" row. The owner always has `Manage` permission.

4. **Backward-compatible policy** — `VaultItemPolicy` checks `AccessResolver` first, then falls back to team/admin checks. This preserves Module 07's behavior for items without explicit grants.

5. **`array_key_exists` vs `in_array`** — when checking if a model has a specific column, use `array_key_exists` (checks keys), not `in_array` (checks values).
