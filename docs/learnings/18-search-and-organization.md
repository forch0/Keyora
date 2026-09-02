# 19 — How Module 19 (Search & Organization) Was Built

| Field | Value |
|---|---|
| **Module** | 19 — Search & Organization |
| **Date** | 2026-09-02 |
| **Spec` | `docs/modules/19-search-and-organization.md` |

---

## Goal

Build a unified global search across all resource types (vault items, files, notes, people, teams) and shared organization features (filters, sorting, recently accessed, recently created, expiring soon).

---

## Decisions Made Before Writing Code

### 1. Search service, not action

GlobalSearch is a service (not an invokable Action) because it's read-only — no mutations, no business rules, just querying. The service has a `search()` method that delegates to private methods per type.

### 2. PersonalVaultItem vs VaultItem

The personal vault uses `PersonalVaultItem` (user-scoped, no tenant), while the org/team vault uses `VaultItem` (tenant-scoped). The search service searches `PersonalVaultItem` for vault items since that's what users create in their personal vault.

### 3. resource_views table for recently accessed

Created a `resource_views` table to track every resource view per user. Updated `ViewTracker` to create records in this table on every view. The `recent` endpoint aggregates from this table, grouped by resource type.

### 4. Filters added to existing controller

Added `tag` (by name), `shared`, and `sort` query parameters to the existing `PersonalVaultItemController::index()` method. The `sort` parameter supports `-` prefix for descending order.

### 5. Expiring soon aggregates grants and links

The `expiring` endpoint checks both `AccessGrant` records (where `expires_at` is within 48 hours) and `SecureLink` records (same criteria), returning both grouped by type.

---

## Files Created/Modified

| File | Purpose |
|---|---|
| `app/Models/ResourceView.php` | View tracking model |
| `app/Services/GlobalSearch.php` | Search across all resource types |
| `app/Http/Controllers/Api/V1/SearchController.php` | Search, recent, recentCreated, expiring |
| `app/Services/ViewTracker.php` | Updated to record resource_views |
| `app/Http/Controllers/Api/V1/PersonalVaultItemController.php` | Added tag/shared/sort filters |
| `database/factories/ResourceViewFactory.php` | Factory |
| `tests/Feature/Api/V1/Search/SearchAndOrganizationTest.php` | 15 feature tests |

---

## New Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/search?q={query}` | Global search across all types |
| GET | `/api/v1/search?q={query}&type={type}` | Search within a specific type |
| GET | `/api/v1/recent` | Recently accessed items |
| GET | `/api/v1/recent/created` | Recently created items |
| GET | `/api/v1/expiring` | Items expiring within 48 hours |

---

## Bugs Found and Fixed During Implementation

### 1. Arrow function with `use` and `void` return type

**Cause:** `fn (Builder $q) use ($tenantId): void => ...` — arrow functions can't have `void` return type (they always return a value).

**Fix:** Removed `use` (arrow functions auto-capture) and `: void` return type.

### 2. Wrong model in tests

**Cause:** Tests created `VaultItem` instances but the personal vault endpoint uses `PersonalVaultItem` (different table).

**Fix:** Updated tests to use `PersonalVaultItem` and the correct tag/folder models.

### 3. PHPStan — specific array shapes vs `array<string, mixed>`

**Cause:** `map()` callbacks return specific array shapes (e.g., `array{id: int, name: string}`), but method return types declared `Collection<int, array<string, mixed>>`. PHPStan doesn't accept specific shapes as subtypes of generic `array<string, mixed>`.

**Fix:** Changed return types to `Collection<int, mixed>`.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 282 tests, 759 assertions (15 new + 267 from Modules 02-18) |
