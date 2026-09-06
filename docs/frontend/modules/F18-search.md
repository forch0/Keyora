# Module F18 — Search & Discovery

| Field | Value |
|---|---|
| **Module** | F18 |
| **Name** | Search & Discovery |
| **Dependencies** | F04, F11, F16, F17 |
| **Status** | Complete |

---

## Objective

Build the global search modal and discovery pages: search across all resource types, view recent items, and see expiring access.

---

## Tasks

### F18.1 Global search modal

- [ ] Triggered from navbar search button (keyboard shortcut: Cmd/Ctrl+K)
- [ ] GET `/api/v1/search?q={query}`
- [ ] Results grouped by type: vault items, files, notes
- [ ] Click result → navigate to resource detail
- [ ] Debounced input (300ms)
- [ ] Recent searches shown when empty

### F18.2 Recent items page

- [ ] GET `/api/v1/search/recent`
- [ ] GET `/api/v1/search/recent/created`
- [ ] Show recently accessed and recently created items across all types

### F18.3 Expiring access page

- [ ] GET `/api/v1/search/expiring`
- [ ] Show resources where the user's access is expiring soon
- [ ] Sort by expiration time
- [ ] "Request extension" button (links to access requests — Module F14)

### F18.4 Hooks

- [ ] `useGlobalSearch(q)` — query (debounced)
- [ ] `useRecentItems()` — query
- [ ] `useRecentCreated()` — query
- [ ] `useExpiringAccess()` — query

---

## API Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/v1/search` | Global search |
| `GET` | `/api/v1/search/recent` | Recent items |
| `GET` | `/api/v1/search/recent/created` | Recently created |
| `GET` | `/api/v1/search/expiring` | Expiring access |

---

## Acceptance Criteria

- [ ] Cmd/Ctrl+K opens the search modal
- [ ] Search results are grouped by resource type
- [ ] Clicking a result navigates to the resource
- [ ] Recent items page shows recently accessed resources
- [ ] Expiring access page shows resources with expiring grants
- [ ] Search is debounced to avoid excessive API calls

---

## What This Module Does NOT Include

- Per-resource search (already in F04, F17)
- Activity logs (Module F19)
