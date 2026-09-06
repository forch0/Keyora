# Module F21 — Admin: Tenant & Member Management

| Field | Value |
|---|---|
| **Module** | F21 |
| **Name** | Admin — Tenant & Member Management |
| **Dependencies** | F10, F12 |
| **Status** | Not Started |

---

## Objective

Build the admin pages for tenant management and member management: invite, change roles, suspend, restore, and offboard members.

---

## Tasks

### F21.1 Tenant settings page

- [ ] Route: `/admin/settings`
- [ ] GET `/api/v1/tenants/{tenant}`
- [ ] Update: PUT `/api/v1/tenants/{tenant}` (name, slug)
- [ ] Delete: DELETE `/api/v1/tenants/{tenant}` (with confirmation — irreversible)

### F21.2 Member list page

- [ ] Route: `/admin/members`
- [ ] GET `/api/v1/tenants/{tenant}/members`
- [ ] Table: name, email, role, status, joined date, last active
- [ ] Filter by role, status
- [ ] Search members

### F21.3 Member management

- [ ] View member: GET `/api/v1/tenants/{tenant}/members/{user}`
- [ ] Change role: PUT `/api/v1/tenants/{tenant}/members/{user}/role`
- [ ] Assign teams: POST `/api/v1/tenants/{tenant}/members/{user}/teams`
- [ ] Remove from team: DELETE `/api/v1/tenants/{tenant}/members/{user}/teams/{team}`
- [ ] Suspend: POST `/api/v1/tenants/{tenant}/members/{user}/suspend`
- [ ] Restore: POST `/api/v1/tenants/{tenant}/members/{user}/restore`
- [ ] Remove member: DELETE `/api/v1/tenants/{tenant}/members/{user}`

### F21.4 Invitations

- [ ] Invite member: POST `/api/v1/tenants/{tenant}/members/invite`
- [ ] List invitations: GET `/api/v1/tenants/{tenant}/invitations`
- [ ] Cancel invitation: DELETE `/api/v1/tenants/{tenant}/invitations/{invitation}`
- [ ] Accept invitation: POST `/api/v1/tenants/{tenant}/members/accept`
- [ ] Complete onboarding: POST `/api/v1/tenants/{tenant}/members/onboarding-complete`

### F21.5 Emergency revoke (per user)

- [ ] POST `/api/v1/tenants/{tenant}/members/{user}/revoke-all`
- [ ] Confirmation: "This will immediately revoke all access grants for this user."
- [ ] Shows count of revoked grants

### F21.6 Hooks

- [ ] `useTenantSettings(id)` — query
- [ ] `useUpdateTenant()` — mutation
- [ ] `useDeleteTenant()` — mutation
- [ ] `useTenantMembers(params)` — query
- [ ] `useTenantMember(userId)` — query
- [ ] `useChangeMemberRole()` — mutation
- [ ] `useAssignTeams()` — mutation
- [ ] `useSuspendMember()` — mutation
- [ ] `useRestoreMember()` — mutation
- [ ] `useRemoveMember()` — mutation
- [ ] `useInviteMember()` — mutation
- [ ] `useInvitations()` — query
- [ ] `useCancelInvitation()` — mutation
- [ ] `useRevokeAllForUser()` — mutation

---

## API Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/v1/tenants/{tenant}` | Tenant details |
| `PUT` | `/api/v1/tenants/{tenant}` | Update tenant |
| `DELETE` | `/api/v1/tenants/{tenant}` | Delete tenant |
| `GET` | `/api/v1/tenants/{tenant}/members` | List members |
| `GET` | `/api/v1/tenants/{tenant}/members/{user}` | Member details |
| `PUT` | `/api/v1/tenants/{tenant}/members/{user}` | Update member |
| `PUT` | `/api/v1/tenants/{tenant}/members/{user}/role` | Change role |
| `POST` | `/api/v1/tenants/{tenant}/members/{user}/teams` | Assign teams |
| `DELETE` | `/api/v1/tenants/{tenant}/members/{user}/teams/{team}` | Remove from team |
| `POST` | `/api/v1/tenants/{tenant}/members/{user}/suspend` | Suspend |
| `POST` | `/api/v1/tenants/{tenant}/members/{user}/restore` | Restore |
| `DELETE` | `/api/v1/tenants/{tenant}/members/{user}` | Remove |
| `POST` | `/api/v1/tenants/{tenant}/members/{user}/revoke-all` | Emergency revoke |
| `POST` | `/api/v1/tenants/{tenant}/members/invite` | Invite |
| `GET` | `/api/v1/tenants/{tenant}/invitations` | List invitations |
| `DELETE` | `/api/v1/tenants/{tenant}/invitations/{invitation}` | Cancel invitation |
| `POST` | `/api/v1/tenants/{tenant}/members/accept` | Accept invitation |
| `POST` | `/api/v1/tenants/{tenant}/members/onboarding-complete` | Complete onboarding |

---

## Acceptance Criteria

- [ ] Admin can view and update tenant settings
- [ ] Admin can list and search members
- [ ] Admin can change member roles
- [ ] Admin can suspend and restore members
- [ ] Admin can remove members
- [ ] Admin can invite new members
- [ ] Admin can cancel pending invitations
- [ ] Admin can emergency-revoke all access for a user
- [ ] Non-admin users cannot access these pages

---

## What This Module Does NOT Include

- Employee offboarding workflow (Module F22)
- Activity logs (Module F19)
