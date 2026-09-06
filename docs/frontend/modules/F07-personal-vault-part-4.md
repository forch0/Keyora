# Module F07 — Personal Vault: Trash & Bulk Operations

| Field | Value |
|---|---|
| **Module** | F07 |
| **Name** | Personal Vault — Part 4: Trash & Bulk Operations |
| **Dependencies** | F05, F06 |
| **Status** | Not Started |

---

## Objective

Build the trash page (restore, force delete, empty trash) and bulk operations (select multiple items → delete, move, archive, tag, share).

---

## Tasks

### F07.1 Trash page

- [ ] Route: `/vault/trash`
- [ ] List trashed items (paginated, 20 per page)
- [ ] GET `/api/v1/vault/trash?per_page=20`
- [ ] Per-item: Restore button → POST `/api/v1/vault/trash/{item}/restore`
- [ ] Per-item: Force delete button → DELETE `/api/v1/vault/trash/{item}/force`
- [ ] "Empty Trash" button → DELETE `/api/v1/vault/trash` with confirmation dialog
- [ ] Empty state when trash is empty

### F07.2 Bulk selection

- [ ] Add checkboxes to vault list items
- [ ] "Select all" checkbox in list header
- [ ] Bulk action bar appears when items are selected
- [ ] Show count of selected items
- [ ] "Clear selection" button

### F07.3 Bulk operations

- [ ] Bulk delete: POST `/api/v1/vault/items/bulk/delete` with `{ ids: [...] }`
- [ ] Bulk move: POST `/api/v1/vault/items/bulk/move` with `{ ids: [...], folder_id }`
- [ ] Bulk archive: POST `/api/v1/vault/items/bulk/archive` with `{ ids: [...] }`
- [ ] Bulk restore: POST `/api/v1/vault/items/bulk/restore` with `{ ids: [...] }`
- [ ] Bulk tag: POST `/api/v1/vault/items/bulk/tag` with `{ ids: [...], tag_ids: [...] }`
- [ ] Bulk create: POST `/api/v1/vault/items/bulk/create` with `{ items: [...] }`
- [ ] Bulk share: POST `/api/v1/vault/items/bulk/share` (Module F13)
- [ ] All bulk operations show confirmation dialog
- [ ] Invalidate list query after success

### F07.4 Hooks

- [ ] `useTrash(params)` — query: GET `/api/v1/vault/trash`
- [ ] `useRestoreFromTrash()` — mutation: POST `/api/v1/vault/trash/{item}/restore`
- [ ] `useForceDelete()` — mutation: DELETE `/api/v1/vault/trash/{item}/force`
- [ ] `useEmptyTrash()` — mutation: DELETE `/api/v1/vault/trash`
- [ ] `useBulkDelete()` — mutation: POST `/api/v1/vault/items/bulk/delete`
- [ ] `useBulkMove()` — mutation: POST `/api/v1/vault/items/bulk/move`
- [ ] `useBulkArchive()` — mutation: POST `/api/v1/vault/items/bulk/archive`
- [ ] `useBulkRestore()` — mutation: POST `/api/v1/vault/items/bulk/restore`
- [ ] `useBulkTag()` — mutation: POST `/api/v1/vault/items/bulk/tag`
- [ ] `useBulkCreate()` — mutation: POST `/api/v1/vault/items/bulk/create`

---

## API Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/v1/vault/trash` | List trashed items |
| `POST` | `/api/v1/vault/trash/{item}/restore` | Restore from trash |
| `DELETE` | `/api/v1/vault/trash/{item}/force` | Force delete |
| `DELETE` | `/api/v1/vault/trash` | Empty trash |
| `POST` | `/api/v1/vault/items/bulk/delete` | Bulk delete |
| `POST` | `/api/v1/vault/items/bulk/move` | Bulk move to folder |
| `POST` | `/api/v1/vault/items/bulk/archive` | Bulk archive |
| `POST` | `/api/v1/vault/items/bulk/restore` | Bulk restore |
| `POST` | `/api/v1/vault/items/bulk/tag` | Bulk tag |
| `POST` | `/api/v1/vault/items/bulk/create` | Bulk create |

---

## Acceptance Criteria

- [ ] User can view trashed items in the trash page
- [ ] User can restore individual items from trash
- [ ] User can force delete individual items from trash
- [ ] User can empty the entire trash with confirmation
- [ ] User can select multiple items in the vault list
- [ ] User can bulk delete, move, archive, restore, and tag selected items
- [ ] Bulk operations show confirmation dialogs
- [ ] List updates after bulk operations complete

---

## What This Module Does NOT Include

- Bulk sharing (Module F13)
- Trash for files and notes (Modules F16, F17)
