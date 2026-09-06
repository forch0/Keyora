# 24 — How Module 24 (Dashboards) Was Built

| Field | Value |
|---|---|
| **Module** | 24 — Dashboards |
| **Date** | 2026-09-02 |
| **Spec** | `docs/modules/24-dashboards.md` |

---

## Goal

Build read-only dashboard endpoints that aggregate data across all modules — personal dashboard, company dashboard, and usage dashboard.

---

## Decisions Made Before Writing Code

### 1. All aggregation in DashboardService

Per the project's architecture rule (business logic in services, not controllers), all aggregation queries live in `DashboardService`. The `DashboardController` is thin — it authenticates, authorizes, resolves the tenant, delegates to the service, and formats the JSON response.

### 2. Efficient aggregate queries

All counts use `COUNT()` and `GROUP BY` at the database level — no N+1 queries. For example:
- Vault summary: `select type, count(*) group by type` in a single query
- Company overview: individual `count()` queries per model (members, teams, vault items, files, notes)
- Teams with counts: `withCount(['vaultItems', 'files', 'notes'])` in a single query

### 3. Plan limits via config (no billing)

Created `config/plans.php` with simple usage limits per plan tier (free/team/business/enterprise). No billing or pricing — the project is open source. The config is easily extensible if billing is added later.

### 4. Personal dashboard is tenant-agnostic

The personal dashboard endpoint (`/dashboard/personal`) doesn't require tenant context — it shows the user's personal vault items, which are not tenant-scoped. However, `AccessGrant` is tenant-scoped, so queries for "shared with me" and "expiring access" use `withoutTenant()` to bypass the tenant scope.

### 5. Company/usage dashboards require tenant context

The company and usage dashboards are under `tenant.resolve` middleware and require admin/owner access. The controller checks `$user->isAdminOf($tenant)` and returns 403 for non-admins.

---

## Files Created/Modified

| File | Purpose |
|---|---|
| `app/Http/Controllers/Api/V1/DashboardController.php` | New — thin controller for 3 dashboard endpoints |
| `app/Models/Team.php` | Extended — added `files()` and `notes()` relationships |
| `app/Services/DashboardService.php` | New — all aggregation logic |
| `config/plans.php` | New — plan limits (free/team/business/enterprise) |
| `routes/api.php` | Extended — 3 new dashboard routes |
| `tests/Feature/Api/V1/Dashboard/DashboardTest.php` | 17 feature tests |

---

## New Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/dashboard/personal` | Personal dashboard (any authenticated user) |
| GET | `/api/v1/dashboard/company` | Company dashboard (admin/owner only) |
| GET | `/api/v1/dashboard/usage` | Usage dashboard (admin/owner only) |

---

## Bugs Found and Fixed During Implementation

### 1. AccessGrant tenant scope on personal dashboard

**Cause:** The personal dashboard doesn't set a tenant context (personal vault items aren't tenant-scoped). But `AccessGrant` uses `BelongsToTenant`, so querying it without a tenant context throws an exception.

**Fix:** Used `AccessGrant::withoutTenant()` for the "shared with me" and "expiring access" queries in the personal dashboard.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 361 tests, 975 assertions (17 new + 344 from Modules 02-23) |
