# Module F13 — Access Management: Grants & Sharing

| Field | Value |
|---|---|
| **Module** | F13 |
| **Name** | Access Management — Grants & Sharing |
| **Dependencies** | F05, F11 |
| **Status** | Complete |

---

## Objective

Build the access management UI: grant access to individuals/teams/tenant, set permissions and time limits, view who has access, revoke access. Works for vault items, files, and notes.

---

## Tasks

### F13.1 Access management panel

- [ ] Create `src/features/access/AccessManagementPanel.tsx`
- [ ] Reusable component — accepts resource type + ID
- [ ] GET `/{resource}/{id}/access` — list current grants
- [ ] GET `/{resource}/{id}/access/summary` — summary stats
- [ ] Show: list of users/teams with access, their permission level, expiry

### F13.2 Grant access form

- [ ] Grant to individual: select user from tenant members
- [ ] Grant to team: select team
- [ ] Grant to entire tenant (company)
- [ ] Permission selector: view, download, edit, manage, share
- [ ] Time limit options: 1 hour, 1 day, 7 days, 30 days, custom
- [ ] Max views: optional view count limit
- [ ] Start on first view toggle
- [ ] Bulk grant: POST `/{resource}/{id}/access/bulk` (grant to multiple users/teams at once)

### F13.3 Manage existing grants

- [ ] Update grant: PUT `/{resource}/{id}/access/{grant}` (change permission)
- [ ] Revoke grant: DELETE `/{resource}/{id}/access/{grant}`
- [ ] Revoke all: POST `/{resource}/{id}/access/revoke-all` (with confirmation)
- [ ] Revoke team access: POST `/{resource}/{id}/access/revoke-team/{team}`

### F13.4 Expiration countdown

- [ ] GET `/{resource}/{id}/access/countdown`
- [ ] Show time remaining for temporary grants
- [ ] Visual indicator for expiring soon (< 1 hour)

### F13.5 Hooks

- [ ] `useAccessGrants(resource, id)` — query
- [ ] `useAccessSummary(resource, id)` — query
- [ ] `useGrantAccess(resource, id)` — mutation
- [ ] `useBulkGrantAccess(resource, id)` — mutation
- [ ] `useUpdateGrant(resource, id)` — mutation
- [ ] `useRevokeGrant(resource, id)` — mutation
- [ ] `useRevokeAllAccess(resource, id)` — mutation
- [ ] `useRevokeTeamAccess(resource, id)` — mutation
- [ ] `useAccessCountdown(resource, id)` — query

---

## API Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/v1/vault/items/{item}/access` | List grants |
| `GET` | `/api/v1/vault/items/{item}/access/summary` | Grant summary |
| `GET` | `/api/v1/vault/items/{item}/access/countdown` | Expiration countdown |
| `POST` | `/api/v1/vault/items/{item}/access` | Grant access |
| `POST` | `/api/v1/vault/items/{item}/access/bulk` | Bulk grant |
| `PUT` | `/api/v1/vault/items/{item}/access/{grant}` | Update grant |
| `DELETE` | `/api/v1/vault/items/{item}/access/{grant}` | Revoke grant |
| `POST` | `/api/v1/vault/items/{item}/access/revoke-all` | Revoke all |
| `POST` | `/api/v1/vault/items/{item}/access/revoke-team/{team}` | Revoke team |

> Same pattern applies to `/api/v1/files/{file}/access` and `/api/v1/notes/{note}/access`

---

## Acceptance Criteria

- [ ] User can view who has access to a resource
- [ ] User can grant access to individuals, teams, or entire tenant
- [ ] User can set permissions (view, download, edit, manage, share)
- [ ] User can set time limits and max views
- [ ] User can revoke individual grants and all access
- [ ] Expiration countdown shows for temporary grants
- [ ] Bulk grant works for multiple recipients
- [ ] Revoke-all requires confirmation

---

## What This Module Does NOT Include

- Access requests (Module F14)
- Secure external links (Module F15)
- Re-authentication for sensitive revocation (Module F24)
