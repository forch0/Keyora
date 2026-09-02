# Module 31 — Dashboard Caching & Performance

| Field | Value |
|---|---|
| **Module** | 31 |
| **Name** | Dashboard Caching & Performance |
| **Dependencies** | Module 24 |
| **Status** | Not Started |

---

## Objective

Cache dashboard query results to reduce database load. Dashboards are read-heavy endpoints that aggregate data across many tables. The Module 24 spec mentioned `Cache::remember()` but it wasn't implemented. This module adds caching with proper invalidation.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| DC-01 | Cache personal dashboard for 60 seconds | P1 |
| DC-02 | Cache company dashboard for 60 seconds | P1 |
| DC-03 | Cache usage dashboard for 60 seconds | P1 |
| DC-04 | Invalidate cache on data changes | P1 |
| DC-05 | Cache tags for granular invalidation | P2 |

---

## Tasks

### 31.1 Cache Strategy

- [ ] Use `Cache::remember()` with a 60-second TTL for all dashboard queries
- [ ] Cache keys are scoped by user ID (personal) or tenant ID (company/usage):

```
dashboard:personal:{user_id}
dashboard:company:{tenant_id}
dashboard:usage:{tenant_id}
```

- [ ] If Redis is available, use cache tags for granular invalidation
- [ ] If Redis is not available, use prefix-based key flushing

### 31.2 Update DashboardService

- [ ] Wrap `personalDashboard()` with cache:
```php
return Cache::remember(
    "dashboard:personal:{$user->id}",
    now()->addSeconds(60),
    fn () => $this->buildPersonalDashboard($user),
);
```

- [ ] Extract the current logic into `buildPersonalDashboard()`, `buildCompanyDashboard()`, `buildUsageDashboard()`
- [ ] Public methods check cache first, build on miss

### 31.3 Cache Invalidation

- [ ] Create `app/Services/DashboardCacheService.php`:
  - `invalidatePersonalDashboard(User $user): void`
  - `invalidateCompanyDashboard(Tenant $tenant): void`
  - `invalidateUsageDashboard(Tenant $tenant): void`
  - `invalidateAllForTenant(Tenant $tenant): void`

- [ ] Trigger invalidation on:
  - Vault item created/updated/deleted/archived → personal + company
  - File created/updated/deleted → company + usage
  - Note created/updated/deleted → company + usage
  - Member joined/suspended/left/offboarded → company + usage
  - Team created/deleted → company
  - Access grant created/revoked → personal + company
  - Access request created/approved/denied → personal + company
  - Security alert created/read → personal
  - Activity log created → company (recent activity feed)

### 31.4 Event-Based Invalidation

- [ ] Create `app/Listeners/InvalidateDashboardCache.php`:
  - Listens to relevant events (or model events) that change dashboard data
  - Calls the appropriate `DashboardCacheService` method

- [ ] Register listener in `EventServiceProvider` for:
  - `vault_item.created`, `vault_item.updated`, `vault_item.deleted`
  - `secure_file.created`, `secure_file.deleted`
  - `secure_note.created`, `secure_note.deleted`
  - `member.joined`, `member.offboarded`, `member.suspended`
  - `access.grant.created`, `access.grant.revoked`
  - `access.request.created`, `access.request.resolved`
  - `security_alert.created`

- [ ] Alternatively, use model observers if events aren't already dispatched

### 31.5 Cache Headers

- [ ] Add response headers to dashboard endpoints:
  - `X-Cache-Status: HIT` or `X-Cache-Status: MISS`
  - `X-Cache-TTL: 45` (seconds remaining)

### 31.6 Cache Warmup (Optional)

- [ ] Add a scheduled job to warm dashboard caches for active tenants:
  - Runs every 5 minutes
  - Identifies tenants with activity in the last hour
  - Pre-builds their dashboards

- [ ] Register in `routes/console.php`:
```php
Schedule::call(function (): void {
    app(DashboardCacheService::class)->warmActiveDashboards();
})->everyFiveMinutes();
```

---

## Acceptance Criteria

- [ ] Personal dashboard is cached for 60 seconds
- [ ] Company dashboard is cached for 60 seconds
- [ ] Usage dashboard is cached for 60 seconds
- [ ] Second request within 60s returns cached data (faster response)
- [ ] Cache is invalidated when relevant data changes
- [ ] Cache status headers are present in responses
- [ ] Cache invalidation doesn't break on cache miss
- [ ] Existing dashboard tests still pass

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_personal_dashboard_cached` | Second call within 60s is a cache hit |
| `test_company_dashboard_cached` | Second call within 60s is a cache hit |
| `test_usage_dashboard_cached` | Second call within 60s is a cache hit |
| `test_cache_invalidated_on_vault_item_create` | Creating item invalidates cache |
| `test_cache_invalidated_on_member_offboard` | Offboarding invalidates company cache |
| `test_cache_invalidated_on_access_grant` | Grant creation invalidates personal cache |
| `test_cache_headers_present` | X-Cache-Status header in response |
| `test_cache_miss_still_works` | First request (cache miss) returns correct data |
| `test_cache_expires_after_60s` | After 60s, cache rebuilt |
| `test_invalidation_on_file_delete` | File deletion invalidates company + usage |

---

## What This Module Does NOT Include

- Redis-specific cache tags (works with any cache driver)
- Client-side caching (ETag/Last-Modified headers — post-MVP)
- Dashboard data streaming (post-MVP)
- Per-widget caching (post-MVP)
