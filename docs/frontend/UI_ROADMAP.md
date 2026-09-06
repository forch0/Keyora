# Keyora Frontend — UI Roadmap

> Screen-by-screen breakdown mapped to API endpoints, in priority order. Build top-to-bottom.

---

## Priority order

1. **Auth** — login, register, 2FA, password reset (nothing else works without this)
2. **Personal vault** — the core feature, every user needs this immediately
3. **Dashboard** — landing page after login
4. **Shared vault (org/team)** — the collaborative feature
5. **Access management** — sharing, requests, revocation
6. **Secure files & notes** — secondary content types
7. **Admin & security** — tenant management, activity logs, alerts, devices
8. **Tools** — password generator, strength checker
9. **Settings** — profile, 2FA management, password change

---

## Phase 1: Auth (must-have first)

### Login screen
- Email + password form
- "Forgot password?" link
- Loading state during submit
- Error display for invalid credentials

| Action | Endpoint |
|---|---|
| Get CSRF cookie | `GET /sanctum/csrf-cookie` |
| Login | `POST /api/v1/auth/login` |
| Fetch profile | `GET /api/v1/auth/me` |

### 2FA verify screen
- 6-digit code input
- "Use recovery code" toggle
- Recovery code input (alternative)
- Resend / back to login

| Action | Endpoint |
|---|---|
| Verify 2FA | `POST /api/v1/auth/2fa/verify` |

### Register screen
- Name, email, password, confirm password
- Validation errors inline

| Action | Endpoint |
|---|---|
| Register | `POST /api/v1/auth/register` |

### Forgot password screen
- Email input
- Success message (always shows, doesn't leak if email exists)

| Action | Endpoint |
|---|---|
| Request reset | `POST /api/v1/auth/forgot-password` |

### Reset password screen
- New password + confirm
- Reads token + email from URL query params

| Action | Endpoint |
|---|---|
| Reset password | `POST /api/v1/auth/reset-password` |

---

## Phase 2: Personal vault (core feature)

### Vault item list
- Searchable, filterable list of personal vault items
- Filter by type (password, api_key, server_credential, database_credential)
- Filter by favorite, archived
- Sort by name, created_at, last_accessed_at
- Pagination
- "Add item" button
- Bulk select → delete, move, archive, tag, share

| Action | Endpoint |
|---|---|
| List items | `GET /api/v1/vault/items` |
| Search | `GET /api/v1/vault/items/search` |
| Recent items | `GET /api/v1/vault/items/recent` |
| Favorites | `GET /api/v1/vault/items/favorites` |
| Archived | `GET /api/v1/vault/items/archived` |
| Bulk delete | `POST /api/v1/vault/items/bulk/delete` |
| Bulk move | `POST /api/v1/vault/items/bulk/move` |
| Bulk archive | `POST /api/v1/vault/items/bulk/archive` |
| Bulk tag | `POST /api/v1/vault/items/bulk/tag` |
| Bulk share | `POST /api/v1/vault/items/bulk/share` |

### Vault item detail
- Show decrypted fields (username, password, notes, custom fields)
- Copy-to-clipboard buttons for username/password
- Edit, delete, favorite, archive actions
- Tags display
- Folder location

| Action | Endpoint |
|---|---|
| Get item | `GET /api/v1/vault/items/{item}` |
| Update | `PUT /api/v1/vault/items/{item}` |
| Delete | `DELETE /api/v1/vault/items/{item}` |
| Toggle favorite | `POST /api/v1/vault/items/{item}/favorite` |
| Archive | `POST /api/v1/vault/items/{item}/archive` |
| Restore | `POST /api/v1/vault/items/{item}/restore` |

### Vault item create/edit form
- Type selector (password, api_key, server_credential, database_credential)
- Fields: name, username, password, url, notes
- Custom fields (key-value pairs, add/remove)
- Folder selector
- Tag selector (multi-select)
- Password generator integration

| Action | Endpoint |
|---|---|
| Create | `POST /api/v1/vault/items` |
| Update | `PUT /api/v1/vault/items/{item}` |

### Folders tree
- Nested folder tree sidebar
- Create, rename, delete folders
- Drag items between folders

| Action | Endpoint |
|---|---|
| List folders | `GET /api/v1/vault/folders` |
| Create folder | `POST /api/v1/vault/folders` |
| Update folder | `PUT /api/v1/vault/folders/{folder}` |
| Delete folder | `DELETE /api/v1/vault/folders/{folder}` |

### Tags management
- List tags
- Create, rename, delete tags
- Filter items by tag

| Action | Endpoint |
|---|---|
| List tags | `GET /api/v1/vault/tags` |
| Create tag | `POST /api/v1/vault/tags` |
| Update tag | `PUT /api/v1/vault/tags/{tag}` |
| Delete tag | `DELETE /api/v1/vault/tags/{tag}` |

### Trash
- List trashed items (paginated)
- Restore individual items
- Force delete individual items
- Empty trash (with confirmation)

| Action | Endpoint |
|---|---|
| List trash | `GET /api/v1/vault/trash` |
| Restore | `POST /api/v1/vault/trash/{item}/restore` |
| Force delete | `DELETE /api/v1/vault/trash/{item}/force` |
| Empty trash | `DELETE /api/v1/vault/trash` |

---

## Phase 3: Dashboard

### Personal dashboard
- Stats: total items, recent activity, favorites count
- Recent items list
- Quick actions

| Action | Endpoint |
|---|---|
| Personal dashboard | `GET /api/v1/dashboard/personal` |

### Company dashboard (admin)
- Stats: total users, total items, access grants
- Recent company activity
- Usage metrics

| Action | Endpoint |
|---|---|
| Company dashboard | `GET /api/v1/dashboard/company` |
| Usage dashboard | `GET /api/v1/dashboard/usage` |

---

## Phase 4: Shared vault (org & team)

### Tenant switcher
- Dropdown to switch between workspaces
- Sends `X-Tenant-ID` header on all subsequent requests

| Action | Endpoint |
|---|---|
| List tenants | `GET /api/v1/tenants` |

### Org vault item list
- Same as personal vault but for org-level items
- Shows who has access

| Action | Endpoint |
|---|---|
| List org items | `GET /api/v1/tenants/{tenant}/vault/items` |
| Create org item | `POST /api/v1/tenants/{tenant}/vault/items` |
| Show | `GET /api/v1/tenants/{tenant}/vault/items/{item}` |
| Update | `PUT /api/v1/tenants/{tenant}/vault/items/{item}` |
| Delete | `DELETE /api/v1/tenants/{tenant}/vault/items/{item}` |

### Team vault item list
- Items scoped to a specific team within the tenant

| Action | Endpoint |
|---|---|
| List team items | `GET /api/v1/tenants/{tenant}/teams/{team}/vault/items` |
| Create | `POST /api/v1/tenants/{tenant}/teams/{team}/vault/items` |
| Show | `GET /api/v1/tenants/{tenant}/teams/{team}/vault/items/{item}` |
| Update | `PUT /api/v1/tenants/{tenant}/teams/{team}/vault/items/{item}` |
| Delete | `DELETE /api/v1/tenants/{tenant}/teams/{team}/vault/items/{item}` |

### Teams management
- List teams, create, update, delete
- Manage team members

| Action | Endpoint |
|---|---|
| List teams | `GET /api/v1/tenants/{tenant}/teams` |
| Create team | `POST /api/v1/tenants/{tenant}/teams` |
| Update team | `PUT /api/v1/tenants/{tenant}/teams/{team}` |
| Delete team | `DELETE /api/v1/tenants/{tenant}/teams/{team}` |
| List team members | `GET /api/v1/tenants/{tenant}/teams/{team}/members` |
| Add member | `POST /api/v1/tenants/{tenant}/teams/{team}/members` |
| Remove member | `DELETE /api/v1/tenants/{tenant}/teams/{team}/members/{user}` |

---

## Phase 5: Access management

### Access grants (per item)
- List who has access to a resource
- Grant access to individuals, teams, or entire tenant
- Set permissions (view, download, edit, manage, share)
- Set time limits (duration, expires_at, max_views)
- Revoke individual grants
- Revoke all access (emergency)

| Action | Endpoint |
|---|---|
| List grants | `GET /api/v1/vault/items/{item}/access` |
| Grant summary | `GET /api/v1/vault/items/{item}/access/summary` |
| Expiration countdown | `GET /api/v1/vault/items/{item}/access/countdown` |
| Grant access | `POST /api/v1/vault/items/{item}/access` |
| Bulk grant | `POST /api/v1/vault/items/{item}/access/bulk` |
| Update grant | `PUT /api/v1/vault/items/{item}/access/{grant}` |
| Revoke grant | `DELETE /api/v1/vault/items/{item}/access/{grant}` |
| Revoke all | `POST /api/v1/vault/items/{item}/access/revoke-all` |
| Revoke team | `POST /api/v1/vault/items/{item}/access/revoke-team/{team}` |

### Access requests
- User can request access to a resource
- Owner sees pending requests, can approve/reject
- Request history

| Action | Endpoint |
|---|---|
| List requests | `GET /api/v1/access-requests` |
| Create request | `POST /api/v1/access-requests` |
| Request history | `GET /api/v1/access-requests/history` |
| Show request | `GET /api/v1/access-requests/{accessRequest}` |
| Approve | `PUT /api/v1/access-requests/{accessRequest}/approve` |
| Reject | `PUT /api/v1/access-requests/{accessRequest}/reject` |
| Cancel | `DELETE /api/v1/access-requests/{accessRequest}` |

### Secure links
- Create shareable links with optional password, expiry, view limits
- List active links
- View link activity
- Revoke links

| Action | Endpoint |
|---|---|
| List links (item) | `GET /api/v1/vault/items/{item}/share-links` |
| Create link (item) | `POST /api/v1/vault/items/{item}/share-links` |
| Delete link | `DELETE /api/v1/secure-links/{link}` |
| Link activity | `GET /api/v1/secure-links/{link}/activity` |

---

## Phase 6: Secure files & notes

### Secure files
- Upload, download, replace files
- File folders
- File access management (same pattern as vault items)
- File trash

| Action | Endpoint |
|---|---|
| List files | `GET /api/v1/files` |
| Upload | `POST /api/v1/files` |
| Bulk upload | `POST /api/v1/files/bulk` |
| Download | `GET /api/v1/files/{file}/download` |
| Update | `PUT /api/v1/files/{file}` |
| Replace | `POST /api/v1/files/{file}/replace` |
| Delete | `DELETE /api/v1/files/{file}` |
| File folders | `GET/POST /api/v1/files/folders` |
| File trash | `GET /api/v1/files/trash` |
| File access | `GET/POST /api/v1/files/{file}/access` |

### Secure notes
- Create, edit, delete notes
- Pin notes
- Note search
- Note folders
- Note access management
- Note trash

| Action | Endpoint |
|---|---|
| List notes | `GET /api/v1/notes` |
| Create | `POST /api/v1/notes` |
| Search | `GET /api/v1/notes/search` |
| Show | `GET /api/v1/notes/{note}` |
| Update | `PUT /api/v1/notes/{note}` |
| Delete | `DELETE /api/v1/notes/{note}` |
| Toggle pin | `POST /api/v1/notes/{note}/pin` |
| Note folders | `GET/POST /api/v1/notes/folders` |
| Note trash | `GET /api/v1/notes/trash` |
| Note access | `GET/POST /api/v1/notes/{note}/access` |

---

## Phase 7: Admin & security

### Tenant management (admin)
- Create, update, delete tenants
- Manage members (invite, change role, suspend, offboard)
- View invitations

| Action | Endpoint |
|---|---|
| List tenants | `GET /api/v1/tenants` |
| Create tenant | `POST /api/v1/tenants` |
| Show tenant | `GET /api/v1/tenants/{tenant}` |
| Update tenant | `PUT /api/v1/tenants/{tenant}` |
| Delete tenant | `DELETE /api/v1/tenants/{tenant}` |
| List members | `GET /api/v1/tenants/{tenant}/members` |
| Invite member | `POST /api/v1/tenants/{tenant}/members/invite` |
| Change role | `PUT /api/v1/tenants/{tenant}/members/{user}/role` |
| Suspend | `POST /api/v1/tenants/{tenant}/members/{user}/suspend` |
| Offboard | `POST /api/v1/tenants/{tenant}/members/{user}/offboard` |
| Revoke all (user) | `POST /api/v1/tenants/{tenant}/members/{user}/revoke-all` |

### Activity logs
- Personal history
- Company feed (admin)
- Employee overview (admin)
- Resource history
- Filter by action, date range

| Action | Endpoint |
|---|---|
| Personal history | `GET /api/v1/activity-logs` |
| Company feed | `GET /api/v1/tenants/{tenant}/activity-logs` |
| Employee overview | `GET /api/v1/tenants/{tenant}/members/{user}/activity-logs` |
| Resource history | `GET /api/v1/vault/items/{item}/activity-logs` |

### Security alerts
- List alerts (unread count badge in navbar)
- Mark as read, dismiss
- Mark all as read

| Action | Endpoint |
|---|---|
| List alerts | `GET /api/v1/security-alerts` |
| Unread count | `GET /api/v1/security-alerts/unread-count` |
| Mark read | `POST /api/v1/security-alerts/{alert}/read` |
| Dismiss | `POST /api/v1/security-alerts/{alert}/dismiss` |
| Mark all read | `POST /api/v1/security-alerts/read-all` |

### Devices
- List active devices/sessions
- Revoke a device

| Action | Endpoint |
|---|---|
| List devices | `GET /api/v1/devices` |
| Revoke device | `DELETE /api/v1/devices/{device}` |

---

## Phase 8: Tools

### Password generator
- Length slider, character type toggles
- Generate button
- Copy to clipboard
- Strength meter

| Action | Endpoint |
|---|---|
| Generate | `POST /api/v1/tools/password/generate` |
| Check strength | `POST /api/v1/tools/password/strength` |

### Global search
- Search across all resource types
- Recent items
- Expiring access

| Action | Endpoint |
|---|---|
| Search | `GET /api/v1/search` |
| Recent | `GET /api/v1/search/recent` |
| Expiring | `GET /api/v1/search/expiring` |

---

## Phase 9: Settings

### Profile settings
- Update name, email
- Change password
- View profile

| Action | Endpoint |
|---|---|
| Get profile | `GET /api/v1/auth/me` |
| Update profile | `PUT /api/v1/auth/me` |
| Change password | `POST /api/v1/auth/password` |

### 2FA settings
- Enable 2FA (show QR code)
- Confirm with TOTP code
- View recovery codes
- Disable 2FA

| Action | Endpoint |
|---|---|
| Enable | `POST /api/v1/auth/2fa/enable` |
| Confirm | `POST /api/v1/auth/2fa/confirm` |
| Recovery codes | `GET /api/v1/auth/2fa/recovery-codes` |
| Disable | `POST /api/v1/auth/2fa/disable` |

### Re-authentication modal
- Triggered by 423 Locked responses
- Password input
- Retry original request after success

| Action | Endpoint |
|---|---|
| Check status | `GET /api/v1/auth/reauthenticate/status` |
| Re-authenticate | `POST /api/v1/auth/reauthenticate` |
