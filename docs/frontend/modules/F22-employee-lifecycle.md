# Module F22 — Admin: Employee Lifecycle & Offboarding

| Field | Value |
|---|---|
| **Module** | F22 |
| **Name** | Admin — Employee Lifecycle & Offboarding |
| **Dependencies** | F13, F21 |
| **Status** | Complete |

---

## Objective

Build the offboarding workflow: when an employee leaves, admin can offboard them which auto-revokes all access, removes team memberships, and handles ownership transfer.

---

## Tasks

### F22.1 Offboard member flow

- [ ] "Offboard" button on member detail page (Module F21)
- [ ] Confirmation dialog explaining what will happen:
  - All access grants revoked
  - Removed from all teams
  - Tenant membership ended
  - Owned resources transferred or reassigned
- [ ] POST `/api/v1/tenants/{tenant}/members/{user}/offboard`
- [ ] Show summary of what was revoked/removed after completion

### F22.2 Offboarding checklist

- [ ] Pre-offboarding checklist UI:
  - Show active access grants count
  - Show team memberships
  - Show owned resources
  - Confirm each item will be handled
- [ ] Optional: transfer ownership of resources before offboarding

### F22.3 Offboarding history

- [ ] Show offboarded members in member list (filtered)
- [ ] View offboarding details: date, reason, what was revoked
- [ ] Activity log entries for offboarding events (Module F19)

### F22.4 Hooks

- [ ] `useOffboardMember()` — mutation: POST `/api/v1/tenants/{tenant}/members/{user}/offboard`

---

## API Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `POST` | `/api/v1/tenants/{tenant}/members/{user}/offboard` | Offboard member |
| `POST` | `/api/v1/tenants/{tenant}/members/{user}/revoke-all` | Emergency revoke (pre-offboard) |
| `GET` | `/api/v1/tenants/{tenant}/members/{user}` | Member details (pre-offboard check) |

---

## Acceptance Criteria

- [ ] Admin can offboard a member with a clear confirmation dialog
- [ ] Offboarding revokes all access grants automatically
- [ ] Offboarding removes team memberships
- [ ] Offboarded members are marked as offboarded in the member list
- [ ] Admin can view offboarding history
- [ ] Non-admin users cannot offboard

---

## What This Module Does NOT Include

- Member CRUD (Module F21)
- Activity logs (Module F19)
