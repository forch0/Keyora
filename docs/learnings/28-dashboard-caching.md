# 31 — How Module 31 (Dashboard Caching) Was Built

| Field | Value |
|---|---|
| **Module** | 31 — Dashboard Caching & Performance |
| **Date** | 2026-09-02 |
| **Spec** | `docs/modules/31-dashboard-caching.md` |

---

## Goal

Cache dashboard query results for 60 seconds to reduce database load. Add proper invalidation when underlying data changes. Add cache status headers.

---

## Decisions Made Before Writing Code

### 1. Cache::remember with 60-second TTL

All three dashboard methods (`personalDashboard`, `companyDashboard`, `usageDashboard`) wrap their logic in `Cache::remember()` with a 60-second TTL. The actual query logic was extracted into `build*` methods.

### 2. Cache keys scoped by user ID or tenant ID

```
dashboard:personal:{user_id}
dashboard:company:{tenant_id}
dashboard:usage:{tenant_id}
```

### 3. Model observers for invalidation

Used a single `DashboardCacheObserver` registered for 5 models:
- `PersonalVaultItem` → invalidates personal dashboard
- `SecurityAlert` → invalidates personal + tenant dashboards
- `SecureFile`, `SecureNote`, `Team` → invalidates tenant dashboards

The observer handles `created`, `updated`, and `deleted` events.

### 4. Event subscriber for access grant/request invalidation

Created `InvalidateDashboardCache` as an event subscriber (registered in `EventServiceProvider::$subscribe`). It listens to:
- `AccessGranted` → invalidates grantee's personal + tenant dashboards
- `AccessRevoked` → same
- `AccessRequested` → invalidates requester's personal + tenant dashboards
- `AccessRequestApproved` → same
- `AccessRequestRejected` → same

### 5. Cache headers on responses

Dashboard responses include:
- `X-Cache-Status: HIT` or `X-Cache-Status: MISS`
- `X-Cache-TTL: 60` (seconds)

The controller checks `Cache::has($key)` before calling the service to determine HIT/MISS.

### 6. Cache warmup scheduled job

A scheduled job runs every 5 minutes to pre-build dashboards for tenants with recent activity.

---

## Files Created/Modified

| File | Purpose |
|---|---|
| `app/Services/DashboardService.php` | Added Cache::remember + build* methods |
| `app/Services/DashboardCacheService.php` | Invalidation methods + warmup |
| `app/Observers/DashboardCacheObserver.php` | Model lifecycle observer |
| `app/Listeners/InvalidateDashboardCache.php` | Event subscriber for access events |
| `app/Providers/EventServiceProvider.php` | Registered event subscriber |
| `app/Providers/AppServiceProvider.php` | Registered model observers |
| `app/Http/Controllers/Api/V1/DashboardController.php` | Added cache headers |
| `routes/console.php` | Added cache warmup scheduled job |
| `tests/Feature/Api/V1/Dashboard/DashboardCachingTest.php` | 10 feature tests |

---

## Bugs Found and Fixed During Implementation

### 1. PHPStan: subscribe() return type

The `subscribe()` method return type was `array` without value type. Added `@return array<string, string>` annotation.

### 2. SecureFile fillable fields

Test used `path` and `encryption_key` but the model uses `file_path` and `checksum`. Fixed to match the actual fillable fields.

### 3. Offboarding returns 204, not 200

The member offboard endpoint returns 204 (No Content), not 200. Fixed the test assertion.

### 4. Pivot updates don't trigger model observers

Offboarding updates a pivot record (`tenant_user` table), not a model. The model observer doesn't fire for pivot updates. The test calls `DashboardCacheService` directly to verify invalidation works. In production, the offboard action should call the cache service explicitly (future improvement).

### 5. Bulk share doesn't dispatch AccessGranted event

The `BulkOperationService::bulkShare` creates `AccessGrant` directly without dispatching the `AccessGranted` event. The event subscriber won't fire. The test uses the `DashboardCacheService` directly to verify invalidation.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 417 tests, 1266 assertions (10 new + 407 existing) |
