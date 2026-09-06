# Module F12 — Teams Management

| Field | Value |
|---|---|
| **Module** | F12 |
| **Name** | Teams Management |
| **Dependencies** | F10, F11 |
| **Status** | Complete |

---

## Objective

Build the teams management UI: list teams, create/edit/delete teams, and manage team members.

---

## Tasks

### F12.1 Teams list page

- [ ] Route: `/admin/teams` or `/shared/teams`
- [ ] GET `/api/v1/tenants/{tenant}/teams`
- [ ] Card grid showing each team: name, member count, item count
- [ ] "Create Team" button

### F12.2 Team CRUD

- [ ] Create team: POST `/api/v1/tenants/{tenant}/teams`
- [ ] Edit team: PUT `/api/v1/tenants/{tenant}/teams/{team}`
- [ ] Delete team: DELETE `/api/v1/tenants/{tenant}/teams/{team}`
- [ ] Soft delete trash: GET `/api/v1/tenants/{tenant}/teams/trash`
- [ ] Restore: POST `/api/v1/tenants/{tenant}/teams/{team}/restore`
- [ ] Force delete: DELETE `/api/v1/tenants/{tenant}/teams/{team}/force`

### F12.3 Team members

- [ ] Route: `/admin/teams/:teamId/members`
- [ ] GET `/api/v1/tenants/{tenant}/teams/{team}/members`
- [ ] Add member: POST `/api/v1/tenants/{tenant}/teams/{team}/members`
- [ ] Update role: PUT `/api/v1/tenants/{tenant}/teams/{team}/members/{user}`
- [ ] Remove member: DELETE `/api/v1/tenants/{tenant}/teams/{team}/members/{user}`

### F12.4 Hooks

- [ ] `useTeams()` — query
- [ ] `useCreateTeam()` — mutation
- [ ] `useUpdateTeam(id)` — mutation
- [ ] `useDeleteTeam()` — mutation
- [ ] `useTeamMembers(teamId)` — query
- [ ] `useAddTeamMember(teamId)` — mutation
- [ ] `useUpdateTeamMember(teamId, userId)` — mutation
- [ ] `useRemoveTeamMember(teamId)` — mutation

---

## API Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/v1/tenants/{tenant}/teams` | List teams |
| `POST` | `/api/v1/tenants/{tenant}/teams` | Create team |
| `PUT` | `/api/v1/tenants/{tenant}/teams/{team}` | Update team |
| `DELETE` | `/api/v1/tenants/{tenant}/teams/{team}` | Delete team |
| `GET` | `/api/v1/tenants/{tenant}/teams/trash` | Trashed teams |
| `POST` | `/api/v1/tenants/{tenant}/teams/{team}/restore` | Restore team |
| `DELETE` | `/api/v1/tenants/{tenant}/teams/{team}/force` | Force delete team |
| `GET` | `/api/v1/tenants/{tenant}/teams/{team}/members` | List members |
| `POST` | `/api/v1/tenants/{tenant}/teams/{team}/members` | Add member |
| `PUT` | `/api/v1/tenants/{tenant}/teams/{team}/members/{user}` | Update role |
| `DELETE` | `/api/v1/tenants/{tenant}/teams/{team}/members/{user}` | Remove member |

---

## Acceptance Criteria

- [ ] User can list teams for the selected tenant
- [ ] User can create, edit, and delete teams (with permission)
- [ ] User can view and manage team members
- [ ] User can add and remove team members
- [ ] Deleted teams go to trash and can be restored or force-deleted

---

## What This Module Does NOT Include

- Tenant member management (Module F21)
- Team vault items (Module F11)
