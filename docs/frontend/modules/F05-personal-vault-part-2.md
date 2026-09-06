# Module F05 — Personal Vault: Create, Edit & Delete

| Field | Value |
|---|---|
| **Module** | F05 |
| **Name** | Personal Vault — Part 2: Create, Edit & Delete |
| **Dependencies** | F04 |
| **Status** | Complete |

---

## Objective

Build the create and edit vault item forms, delete confirmation, and archive/restore actions. Users need to add, modify, and remove their stored credentials.

---

## Tasks

### F05.1 Create vault item form

- [ ] Route: `/vault/new` (or modal dialog from list page)
- [ ] Type selector: password, api_key, server_credential, database_credential
- [ ] Fields: name, username, password, URL, notes
- [ ] Password field with show/hide + generate button (Module F08)
- [ ] Custom fields: add/remove key-value pairs dynamically
- [ ] Folder selector (Module F06 — placeholder for now)
- [ ] Tag selector (Module F06 — placeholder for now)
- [ ] Form validation (React Hook Form + Zod)
- [ ] Submit → POST `/api/v1/vault/items`
- [ ] On success → redirect to item detail, invalidate list query

### F05.2 Edit vault item form

- [ ] Route: `/vault/items/:id/edit` (or modal from detail page)
- [ ] Pre-populate all fields with current values
- [ ] Password field shows placeholder ("••••••••") unless user clicks "Change password"
- [ ] Submit → PUT `/api/v1/vault/items/{item}`
- [ ] On success → redirect to item detail, invalidate queries

### F05.3 Delete vault item

- [ ] Delete button on detail page
- [ ] Confirmation dialog: "Are you sure you want to delete '{name}'? This moves it to trash."
- [ ] Submit → DELETE `/api/v1/vault/items/{item}`
- [ ] On success → redirect to list, invalidate queries

### F05.4 Archive / restore

- [ ] Archive button on detail page → POST `/api/v1/vault/items/{item}/archive`
- [ ] Restore button on archived items → POST `/api/v1/vault/items/{item}/restore`
- [ ] Optimistic UI update

### F05.5 Hooks

- [ ] `useCreateVaultItem()` — mutation: POST `/api/v1/vault/items`
- [ ] `useUpdateVaultItem(id)` — mutation: PUT `/api/v1/vault/items/{item}`
- [ ] `useDeleteVaultItem()` — mutation: DELETE `/api/v1/vault/items/{item}`
- [ ] `useArchiveVaultItem()` — mutation: POST `/api/v1/vault/items/{item}/archive`
- [ ] `useRestoreVaultItem()` — mutation: POST `/api/v1/vault/items/{item}/restore`

---

## API Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `POST` | `/api/v1/vault/items` | Create item |
| `PUT` | `/api/v1/vault/items/{item}` | Update item |
| `DELETE` | `/api/v1/vault/items/{item}` | Delete item (soft delete) |
| `POST` | `/api/v1/vault/items/{item}/archive` | Archive item |
| `POST` | `/api/v1/vault/items/{item}/restore` | Restore archived item |

---

## Acceptance Criteria

- [ ] User can create a new vault item with all fields
- [ ] User can add custom key-value fields to an item
- [ ] User can edit an existing vault item
- [ ] Password field in edit mode doesn't show current password unless "Change" is clicked
- [ ] User can delete an item with confirmation
- [ ] User can archive and restore items
- [ ] Form validation shows inline errors for required fields
- [ ] After create/edit/delete, the list updates to reflect changes

---

## What This Module Does NOT Include

- Folders and tags (Module F06)
- Trash management (Module F07)
- Bulk operations (Module F07)
- Password generator tool (Module F08)
