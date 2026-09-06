# Module F09 — Dashboard

| Field | Value |
|---|---|
| **Module** | F09 |
| **Name** | Dashboard |
| **Dependencies** | F03, F04 |
| **Status** | Complete |

---

## Objective

Build the personal and company dashboards. The personal dashboard is the landing page after login, showing stats and recent activity. The company dashboard (admin only) shows organization-wide metrics.

---

## Tasks

### F09.1 Personal dashboard

- [ ] Route: `/` (landing page after login)
- [ ] GET `/api/v1/dashboard/personal`
- [ ] Stats cards: total items, favorites, archived, recent activity count
- [ ] Recent items list (last 5 accessed) — clickable to detail
- [ ] Quick actions: Add Item, Generate Password
- [ ] Expiring access warnings (if any)

### F09.2 Company dashboard (admin)

- [ ] Route: `/admin/dashboard` or integrated into `/admin`
- [ ] GET `/api/v1/dashboard/company` (requires tenant context)
- [ ] Stats: total users, total items, active grants, pending requests
- [ ] GET `/api/v1/dashboard/usage`
- [ ] Usage metrics: storage, members, vault items per team
- [ ] Recent company activity feed (last 10 events)

### F09.3 Dashboard components

- [ ] `StatCard` — reusable stat card with icon, label, value
- [ ] `RecentItemsList` — compact list of recent vault items
- [ ] `ActivityFeed` — compact activity log entries

### F09.4 Hooks

- [ ] `usePersonalDashboard()` — query: GET `/api/v1/dashboard/personal`
- [ ] `useCompanyDashboard()` — query: GET `/api/v1/dashboard/company`
- [ ] `useUsageDashboard()` — query: GET `/api/v1/dashboard/usage`

---

## API Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/v1/dashboard/personal` | Personal stats |
| `GET` | `/api/v1/dashboard/company` | Company stats (admin) |
| `GET` | `/api/v1/dashboard/usage` | Usage metrics (admin) |

---

## Acceptance Criteria

- [ ] Personal dashboard shows after login with relevant stats
- [ ] Recent items are clickable and navigate to detail
- [ ] Quick actions work (Add Item, Generate Password)
- [ ] Company dashboard shows for admin users
- [ ] Non-admin users cannot access company dashboard

---

## What This Module Does NOT Include

- Activity log full page (Module F19)
- Admin member management (Module F21)
