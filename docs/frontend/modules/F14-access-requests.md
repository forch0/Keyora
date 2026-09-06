# Module F14 — Access Requests & Approval Workflow

| Field | Value |
|---|---|
| **Module** | F14 |
| **Name** | Access Requests & Approval Workflow |
| **Dependencies** | F13 |
| **Status** | Complete |

---

## Objective

Build the access request system: users can request access to resources they don't have, owners can approve/reject requests, and view request history.

---

## Tasks

### F14.1 Request access

- [ ] "Request Access" button on resources the user can't access
- [ ] Form: select desired permission, optional message
- [ ] POST `/api/v1/access-requests`
- [ ] Prevent duplicate pending requests

### F14.2 Access requests page

- [ ] Route: `/access-requests`
- [ ] Tabs: "Sent" (my requests) | "Received" (requests for my resources)
- [ ] GET `/api/v1/access-requests` (filtered by sent/received)
- [ ] List shows: resource name, requester, requested permission, status, date

### F14.3 Approve/reject requests

- [ ] For received requests: Approve and Reject buttons
- [ ] Approve: can modify permission and duration before approving
- [ ] PUT `/api/v1/access-requests/{accessRequest}/approve`
- [ ] PUT `/api/v1/access-requests/{accessRequest}/reject`
- [ ] Cancel own pending request: DELETE `/api/v1/access-requests/{accessRequest}`

### F14.4 Request history

- [ ] GET `/api/v1/access-requests/history`
- [ ] Shows all past requests (approved, rejected, cancelled, expired)
- [ ] Filter by status

### F14.5 Hooks

- [ ] `useAccessRequests(params)` — query
- [ ] `useCreateAccessRequest()` — mutation
- [ ] `useApproveRequest()` — mutation
- [ ] `useRejectRequest()` — mutation
- [ ] `useCancelRequest()` — mutation
- [ ] `useAccessRequestHistory()` — query

---

## API Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/v1/access-requests` | List requests |
| `POST` | `/api/v1/access-requests` | Create request |
| `GET` | `/api/v1/access-requests/history` | Request history |
| `GET` | `/api/v1/access-requests/{accessRequest}` | Show request |
| `PUT` | `/api/v1/access-requests/{accessRequest}/approve` | Approve |
| `PUT` | `/api/v1/access-requests/{accessRequest}/reject` | Reject |
| `DELETE` | `/api/v1/access-requests/{accessRequest}` | Cancel |

---

## Acceptance Criteria

- [ ] User can request access to a resource they don't have access to
- [ ] User can view their sent and received requests
- [ ] Owner can approve requests with modified permission/duration
- [ ] Owner can reject requests
- [ ] User can cancel their own pending requests
- [ ] Request history shows all past requests with status
- [ ] Duplicate pending requests are prevented

---

## What This Module Does NOT Include

- Grant management (Module F13)
- Secure external links (Module F15)
