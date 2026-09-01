# Module 24 — Dashboards

| Field | Value |
|---|---|
| **Module** | 24 |
| **Name** | Dashboards |
| **Dependencies** | All prior modules |
| **Status** | Not Started |

---

## Objective

Build aggregated dashboard endpoints that summarize data across modules — personal dashboard, company dashboard, and usage dashboard. These are read-only endpoints that pull data from existing models.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| DB-01 | Personal dashboard: vault summary, favorites, recently viewed | P0 |
| DB-02 | Personal dashboard: shared with me, expiring access, pending requests | P1 |
| DB-03 | Company dashboard: overview, members, teams | P1 |
| DB-04 | Company dashboard: shared vaults, files, access requests | P1 |
| DB-05 | Company dashboard: temporary access, security activity, expiring access | P1 |
| DB-06 | Company dashboard: recent activity feed | P1 |
| SA-07 | Usage dashboard (storage used, members, vaults, items) | P1 |

---

## Tasks

### 24.1 API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/dashboard/personal` | Personal dashboard summary |
| `GET` | `/api/v1/dashboard/company` | Company dashboard summary |
| `GET` | `/api/v1/dashboard/usage` | Usage dashboard (plan limits vs actual) |

### 24.2 Personal Dashboard

- [ ] `GET /api/v1/dashboard/personal` returns:

```json
{
  "data": {
    "vault_summary": {
      "total_items": 47,
      "by_type": {
        "password": 20,
        "api_key": 10,
        "server": 8,
        "database": 9
      },
      "favorites_count": 5,
      "archived_count": 3
    },
    "recently_viewed": [ ... 5 items ... ],
    "recently_added": [ ... 5 items ... ],
    "shared_with_me": {
      "total": 12,
      "items": [ ... 5 recent ... ]
    },
    "expiring_access": {
      "count": 3,
      "items": [ ... expiring within 48h ... ]
    },
    "pending_requests": {
      "sent": 2,
      "received": 1
    },
    "security_alerts_unread": 1
  }
}
```

### 24.3 Company Dashboard

- [ ] `GET /api/v1/dashboard/company` returns (admin/owner only):

```json
{
  "data": {
    "overview": {
      "total_members": 25,
      "active_members": 23,
      "suspended_members": 2,
      "total_teams": 5,
      "total_vault_items": 340,
      "total_files": 120,
      "total_notes": 45
    },
    "members": [ ... recent members ... ],
    "teams": [ ... teams with item counts ... ],
    "access_requests": {
      "pending": 4,
      "recent": [ ... 5 recent ... ]
    },
    "temporary_access": {
      "active": 8,
      "expiring_24h": 2
    },
    "security_activity": {
      "recent_alerts": 3,
      "recent_revocations": 1,
      "failed_logins_24h": 5
    },
    "expiring_access": {
      "count": 6,
      "items": [ ... expiring within 48h ... ]
    },
    "recent_activity": [ ... 10 recent activity logs ... ]
  }
}
```

### 24.4 Usage Dashboard

- [ ] `GET /api/v1/dashboard/usage` returns:

```json
{
  "data": {
    "plan": "team",
    "limits": {
      "max_members": 10,
      "max_storage_mb": 1024,
      "max_vault_items": 500
    },
    "usage": {
      "members": 8,
      "storage_used_mb": 245.5,
      "vault_items": 340,
      "files": 120,
      "notes": 45
    },
    "percentages": {
      "members": 80,
      "storage": 24,
      "vault_items": 68
    }
  }
}
```

### 24.5 Controller

- [ ] `app/Http/Controllers/Api/V1/DashboardController.php`:
  - `personal()` — aggregate data from personal vault, access grants, access requests, security alerts
  - `company()` — aggregate data from tenant members, teams, vault items, files, notes, activity logs (admin/owner only)
  - `usage()` — calculate plan limits vs actual usage (depends on Module 25 for plan limits)

### 24.6 Dashboard Service

- [ ] `app/Services/DashboardService.php`:
  - `personalDashboard(User $user): array`
  - `companyDashboard(Tenant $tenant): array`
  - `usageDashboard(Tenant $tenant): array`

- Methods perform efficient aggregate queries (COUNT, GROUP BY) — no N+1 queries
- Cache results for 60 seconds (optional, using `Cache::remember()`)

### 24.7 Policy

- [ ] Personal dashboard: any authenticated user
- [ ] Company dashboard: admin/owner only
- [ ] Usage dashboard: admin/owner only

### 24.8 Routes

```php
Route::middleware(['auth:sanctum', 'tenant.resolve'])->group(function () {
    Route::get('v1/dashboard/personal', [DashboardController::class, 'personal']);
    Route::get('v1/dashboard/company', [DashboardController::class, 'company']);
    Route::get('v1/dashboard/usage', [DashboardController::class, 'usage']);
});
```

---

## Acceptance Criteria

- [ ] `GET /dashboard/personal` returns vault summary with counts by type
- [ ] Personal dashboard includes favorites count and archived count
- [ ] Personal dashboard includes 5 recently viewed items
- [ ] Personal dashboard includes 5 recently added items
- [ ] Personal dashboard includes shared-with-me count and recent items
- [ ] Personal dashboard includes expiring access count and items
- [ ] Personal dashboard includes pending requests count (sent + received)
- [ ] Personal dashboard includes unread security alerts count
- [ ] `GET /dashboard/company` returns overview with member/team/item counts
- [ ] Company dashboard includes recent members
- [ ] Company dashboard includes teams with item counts
- [ ] Company dashboard includes pending access requests
- [ ] Company dashboard includes temporary access stats
- [ ] Company dashboard includes security activity summary
- [ ] Company dashboard includes recent activity feed (10 logs)
- [ ] Non-admin cannot access company dashboard → `403`
- [ ] `GET /dashboard/usage` returns plan limits vs actual usage
- [ ] Usage dashboard shows percentages
- [ ] Non-admin cannot access usage dashboard → `403`
- [ ] Dashboard queries are efficient (no N+1 problems)

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_personal_dashboard_returns_summary` | GET returns vault summary |
| `test_personal_dashboard_includes_favorites_count` | Favorites count correct |
| `test_personal_dashboard_includes_recently_viewed` | 5 recent items returned |
| `test_personal_dashboard_includes_shared_with_me` | Shared items count correct |
| `test_personal_dashboard_includes_expiring_access` | Expiring items listed |
| `test_personal_dashboard_includes_pending_requests` | Sent + received counts |
| `test_personal_dashboard_includes_security_alerts` | Unread count correct |
| `test_company_dashboard_returns_overview` | Member/team/item counts |
| `test_company_dashboard_includes_recent_members` | Recent members listed |
| `test_company_dashboard_includes_teams` | Teams with counts |
| `test_company_dashboard_includes_access_requests` | Pending count + recent |
| `test_company_dashboard_includes_activity_feed` | 10 recent logs |
| `test_non_admin_cannot_access_company_dashboard` | Non-admin → 403 |
| `test_usage_dashboard_returns_limits` | Plan limits returned |
| `test_usage_dashboard_returns_usage` | Actual usage returned |
| `test_usage_dashboard_returns_percentages` | Percentages calculated |
| `test_non_admin_cannot_access_usage_dashboard` | Non-admin → 403 |

---

## What This Module Does NOT Include

- Chart/graph data endpoints (post-MVP)
- Customizable dashboard widgets (post-MVP)
- Export dashboard data (post-MVP)
- Real-time dashboard updates (post-MVP)
