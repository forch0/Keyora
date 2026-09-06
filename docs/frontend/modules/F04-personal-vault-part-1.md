# Module F04 — Personal Vault: List & Detail

| Field | Value |
|---|---|
| **Module** | F04 |
| **Name** | Personal Vault — Part 1: List & Detail |
| **Dependencies** | F02, F03 |
| **Status** | Not Started |

---

## Objective

Build the personal vault item list page (searchable, filterable, paginated) and the vault item detail page with copy-to-clipboard for sensitive fields. This is the core feature every user needs.

---

## Tasks

### F04.1 Vault item list page

- [ ] Route: `/vault`
- [ ] Search bar (debounced) → GET `/api/v1/vault/items/search?q=`
- [ ] Filter buttons: All, Passwords, API Keys, Server Credentials, Database Credentials
- [ ] Filter: Favorites, Archived
- [ ] Sort dropdown: Name, Created, Last Accessed
- [ ] Paginated list (20 per page) with pagination controls
- [ ] Each item shows: name, type icon, username (masked), favorite star, tags
- [ ] Click item → navigate to `/vault/items/:id`
- [ ] "Add Item" button (Module F05)
- [ ] Empty state when no items

### F04.2 Vault item detail page

- [ ] Route: `/vault/items/:id`
- [ ] Show all fields: name, type, username, password (masked with show/hide toggle), URL, notes, custom fields
- [ ] Copy-to-clipboard buttons for username and password
- [ ] Auto-clear clipboard after 30 seconds
- [ ] Tags display
- [ ] Folder breadcrumb
- [ ] Favorite toggle button
- [ ] Edit button (Module F05)
- [ ] Delete button (Module F05)
- [ ] Archive button (Module F05)
- [ ] Back to list

### F04.3 Vault item hooks

- [ ] `useVaultItems(params)` — query: GET `/api/v1/vault/items` with pagination + filters
- [ ] `useVaultItem(id)` — query: GET `/api/v1/vault/items/{item}`
- [ ] `useRecentVaultItems()` — query: GET `/api/v1/vault/items/recent`
- [ ] `useFavoriteVaultItems()` — query: GET `/api/v1/vault/items/favorites`
- [ ] `useArchivedVaultItems()` — query: GET `/api/v1/vault/items/archived`
- [ ] `useSearchVaultItems(q)` — query: GET `/api/v1/vault/items/search?q=`

### F04.4 Shared components

- [ ] `CopyButton` — button that copies text to clipboard, shows "Copied!" for 2 seconds, auto-clears after 30 seconds
- [ ] `PasswordField` — masked password with show/hide toggle + copy button
- [ ] `VaultItemCard` — compact card for list view
- [ ] `ItemTypeIcon` — icon based on item type (password, api_key, server_credential, database_credential)
- [ ] `EmptyState` — reusable empty state with icon + message + optional action

### F04.5 Favorite toggle

- [ ] `useToggleFavorite()` — mutation: POST `/api/v1/vault/items/{item}/favorite`
- [ ] Optimistic update: toggle star immediately, rollback on error
- [ ] Invalidate vault items query on success

---

## API Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/v1/vault/items` | List items (paginated, filtered) |
| `GET` | `/api/v1/vault/items/{item}` | Get single item |
| `GET` | `/api/v1/vault/items/search` | Search items |
| `GET` | `/api/v1/vault/items/recent` | Recent items |
| `GET` | `/api/v1/vault/items/favorites` | Favorite items |
| `GET` | `/api/v1/vault/items/archived` | Archived items |
| `POST` | `/api/v1/vault/items/{item}/favorite` | Toggle favorite |

---

## Acceptance Criteria

- [ ] User sees a paginated list of their personal vault items
- [ ] User can search items by name
- [ ] User can filter by type (password, api_key, etc.)
- [ ] User can filter by favorites and archived
- [ ] User can sort by name, created date, last accessed
- [ ] Clicking an item opens the detail page with decrypted fields
- [ ] User can copy username and password to clipboard
- [ ] Clipboard auto-clears after 30 seconds
- [ ] User can toggle favorite from list and detail
- [ ] Empty state shows when no items exist
- [ ] Pagination works correctly

---

## What This Module Does NOT Include

- Create/edit/delete (Module F05)
- Folders and tags management (Module F06)
- Trash and bulk operations (Module F07)
- Sharing and access management (Module F13)
