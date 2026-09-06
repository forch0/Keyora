# Module F11 — Shared Vault: Org & Team Items

| Field | Value |
|---|---|
| **Module** | F11 |
| **Name** | Shared Vault — Org & Team Items |
| **Dependencies** | F04, F05, F10 |
| **Status** | Not Started |

---

## Objective

Build the shared vault pages for org-level and team-level vault items. These are tenant-scoped and require the `X-Tenant-ID` header from Module F10.

---

## Tasks

### F11.1 Org vault list page

- [ ] Route: `/shared`
- [ ] GET `/api/v1/tenants/{tenant}/vault/items` (tenant from auth store)
- [ ] Same list UI as personal vault (search, filter, sort, pagination)
- [ ] Show owner and access badge per item
- [ ] "Add Org Item" button

### F11.2 Org vault item detail

- [ ] Route: `/shared/items/:id`
- [ ] GET `/api/v1/tenants/{tenant}/vault/items/{item}`
- [ ] Same detail UI as personal vault
- [ ] Show who has access (link to access management — Module F13)

### F11.3 Org vault CRUD

- [ ] Create: POST `/api/v1/tenants/{tenant}/vault/items`
- [ ] Update: PUT `/api/v1/tenants/{tenant}/vault/items/{item}`
- [ ] Delete: DELETE `/api/v1/tenants/{tenant}/vault/items/{item}`
- [ ] Reuse vault item form components from F05

### F11.4 Team vault list

- [ ] Route: `/shared/teams/:teamId`
- [ ] GET `/api/v1/tenants/{tenant}/teams/{team}/vault/items`
- [ ] Same list UI, scoped to team
- [ ] Team selector dropdown to switch between teams

### F11.5 Team vault CRUD

- [ ] Create: POST `/api/v1/tenants/{tenant}/teams/{team}/vault/items`
- [ ] Update: PUT `/api/v1/tenants/{tenant}/teams/{team}/vault/items/{item}`
- [ ] Delete: DELETE `/api/v1/tenants/{tenant}/teams/{team}/vault/items/{item}`

### F11.6 Hooks

- [ ] `useOrgVaultItems(params)` — query
- [ ] `useOrgVaultItem(id)` — query
- [ ] `useCreateOrgVaultItem()` — mutation
- [ ] `useUpdateOrgVaultItem(id)` — mutation
- [ ] `useDeleteOrgVaultItem()` — mutation
- [ ] `useTeamVaultItems(teamId, params)` — query
- [ ] `useTeamVaultItem(teamId, id)` — query
- [ ] `useCreateTeamVaultItem(teamId)` — mutation
- [ ] `useUpdateTeamVaultItem(teamId, id)` — mutation
- [ ] `useDeleteTeamVaultItem(teamId)` — mutation

---

## API Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/v1/tenants/{tenant}/vault/items` | List org items |
| `POST` | `/api/v1/tenants/{tenant}/vault/items` | Create org item |
| `GET` | `/api/v1/tenants/{tenant}/vault/items/{item}` | Get org item |
| `PUT` | `/api/v1/tenants/{tenant}/vault/items/{item}` | Update org item |
| `DELETE` | `/api/v1/tenants/{tenant}/vault/items/{item}` | Delete org item |
| `GET` | `/api/v1/tenants/{tenant}/teams/{team}/vault/items` | List team items |
| `POST` | `/api/v1/tenants/{tenant}/teams/{team}/vault/items` | Create team item |
| `GET` | `/api/v1/tenants/{tenant}/teams/{team}/vault/items/{item}` | Get team item |
| `PUT` | `/api/v1/tenants/{tenant}/teams/{team}/vault/items/{item}` | Update team item |
| `DELETE` | `/api/v1/tenants/{tenant}/teams/{team}/vault/items/{item}` | Delete team item |

---

## Acceptance Criteria

- [ ] User can view org-level vault items for the selected tenant
- [ ] User can view team-level vault items for a specific team
- [ ] User can create, edit, and delete org/team items (if they have permission)
- [ ] 403 errors are handled gracefully ("You don't have permission")
- [ ] Items show who owns them and who has access

---

## What This Module Does NOT Include

- Teams management (Module F12)
- Access management / sharing (Module F13)
