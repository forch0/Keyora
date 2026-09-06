# Module F15 — Secure External Sharing (Links)

| Field | Value |
|---|---|
| **Module** | F15 |
| **Name** | Secure External Sharing (Links) |
| **Dependencies** | F13 |
| **Status** | Complete |

---

## Objective

Build the secure link sharing UI: create shareable links with optional password, expiry, and view limits. View active links and their activity. Revoke links.

---

## Tasks

### F15.1 Create secure link

- [ ] "Share Link" button on vault items, files, and notes
- [ ] Form: optional password, expiry time, max views
- [ ] POST `/{resource}/{id}/share-links`
- [ ] Generated link displayed with copy button
- [ ] Warning: "This link bypasses authentication — share carefully"

### F15.2 List secure links

- [ ] GET `/{resource}/{id}/share-links`
- [ ] Show: link URL, created date, expiry, views used/max, status
- [ ] Revoke link: DELETE `/api/v1/secure-links/{link}`
- [ ] Link activity: GET `/api/v1/secure-links/{link}/activity`

### F15.3 Public link access page

- [ ] Route: `/s/{token}` (public, no auth required)
- [ ] GET `/api/v1/s/{token}` — show link info (resource name, requires password?)
- [ ] If password required: POST `/api/v1/s/{token}/verify`
- [ ] Email verification flow: POST `/api/v1/s/{token}/email-verify` + `/email-confirm`
- [ ] GET `/api/v1/s/{token}/resource` — display the shared resource
- [ ] Show watermark with viewer email/IP (if configured)

### F15.4 Hooks

- [ ] `useSecureLinks(resource, id)` — query
- [ ] `useCreateSecureLink(resource, id)` — mutation
- [ ] `useRevokeSecureLink()` — mutation
- [ ] `useSecureLinkActivity(linkId)` — query

---

## API Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/v1/vault/items/{item}/share-links` | List links for item |
| `POST` | `/api/v1/vault/items/{item}/share-links` | Create link |
| `GET` | `/api/v1/files/{file}/share-links` | List links for file |
| `POST` | `/api/v1/files/{file}/share-links` | Create link for file |
| `GET` | `/api/v1/notes/{note}/share-links` | List links for note |
| `POST` | `/api/v1/notes/{note}/share-links` | Create link for note |
| `DELETE` | `/api/v1/secure-links/{link}` | Revoke link |
| `GET` | `/api/v1/secure-links/{link}/activity` | Link activity |
| `GET` | `/api/v1/s/{token}` | Public link info |
| `POST` | `/api/v1/s/{token}/verify` | Verify password |
| `POST` | `/api/v1/s/{token}/email-verify` | Send email verification |
| `POST` | `/api/v1/s/{token}/email-confirm` | Confirm email verification |
| `GET` | `/api/v1/s/{token}/resource` | View shared resource |

---

## Acceptance Criteria

- [ ] User can create a secure share link with optional password and expiry
- [ ] User can view all active links for a resource
- [ ] User can revoke a link
- [ ] User can view link activity (who accessed, when)
- [ ] Public link page works without authentication
- [ ] Password-protected links require password entry
- [ ] Links expire automatically when time/view limit is reached

---

## What This Module Does NOT Include

- Internal access grants (Module F13)
- Access requests (Module F14)
