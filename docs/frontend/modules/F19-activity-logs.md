# Module F19 — Activity Logs

| Field | Value |
|---|---|
| **Module** | F19 |
| **Name** | Activity Logs |
| **Dependencies** | F03, F10 |
| **Status** | Complete |

---

## Objective

Build the activity log pages: personal history, company feed (admin), employee overview (admin), and resource history.

---

## Tasks

### F19.1 Personal activity history

- [ ] Route: `/activity`
- [ ] GET `/api/v1/activity-logs` (paginated)
- [ ] Timeline view: action, resource, timestamp, IP, user agent
- [ ] Filter by action type
- [ ] Filter by date range

### F19.2 Company activity feed (admin)

- [ ] GET `/api/v1/tenants/{tenant}/activity-logs` (requires admin role)
- [ ] Shows all activity across the tenant
- [ ] Filter by user, action, date range
- [ ] Non-admin users get 403 → hide this tab

### F19.3 Employee overview (admin)

- [ ] GET `/api/v1/tenants/{tenant}/members/{user}/activity-logs`
- [ ] Shows activity for a specific employee
- [ ] Accessed from member management page (Module F21)

### F19.4 Resource history

- [ ] GET `/api/v1/vault/items/{item}/activity-logs`
- [ ] GET `/api/v1/files/{file}/activity-logs`
- [ ] Shown as a tab on resource detail pages
- [ ] Shows who accessed/modified the resource and when

### F19.5 Hooks

- [ ] `usePersonalActivityLogs(params)` — query
- [ ] `useCompanyActivityLogs(tenantId, params)` — query
- [ ] `useEmployeeActivityLogs(tenantId, userId, params)` — query
- [ ] `useResourceActivityLogs(resource, id, params)` — query

---

## API Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/v1/activity-logs` | Personal history |
| `GET` | `/api/v1/tenants/{tenant}/activity-logs` | Company feed |
| `GET` | `/api/v1/tenants/{tenant}/members/{user}/activity-logs` | Employee overview |
| `GET` | `/api/v1/vault/items/{item}/activity-logs` | Resource history |
| `GET` | `/api/v1/files/{file}/activity-logs` | File history |

---

## Acceptance Criteria

- [ ] User can view their personal activity history
- [ ] Admin can view the company activity feed
- [ ] Admin can view activity for a specific employee
- [ ] Resource detail pages show activity history tab
- [ ] Logs can be filtered by action type and date range
- [ ] Non-admin users cannot access company feed

---

## What This Module Does NOT Include

- Security alerts (Module F20)
- Admin member management (Module F21)
