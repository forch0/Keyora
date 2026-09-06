# Module F17 — Secure Notes

| Field | Value |
|---|---|
| **Module** | F17 |
| **Name** | Secure Notes |
| **Dependencies** | F03, F10, F13 |
| **Status** | Not Started |

---

## Objective

Build the secure notes management UI: create, edit, delete, pin, search notes. Note folders. Note access management. Note trash.

---

## Tasks

### F17.1 Notes list page

- [ ] Route: `/notes`
- [ ] GET `/api/v1/notes` (paginated, tenant-scoped)
- [ ] List/grid view: title, preview, pinned indicator, owner, folder
- [ ] Search: GET `/api/v1/notes/search`
- [ ] Pinned notes section at top
- [ ] Note folder sidebar

### F17.2 Note CRUD

- [ ] Create: POST `/api/v1/notes`
- [ ] Show: GET `/api/v1/notes/{note}`
- [ ] Update: PUT `/api/v1/notes/{note}`
- [ ] Delete: DELETE `/api/v1/notes/{note}`
- [ ] Toggle pin: POST `/api/v1/notes/{note}/pin`

### F17.3 Note detail view

- [ ] Rich text display (sanitized)
- [ ] Edit mode (markdown or rich text editor)
- [ ] Pin/unpin button
- [ ] Access management panel (Module F13)
- [ ] Copy content button

### F17.4 Note folders

- [ ] List: GET `/api/v1/notes/folders`
- [ ] Create: POST `/api/v1/notes/folders`
- [ ] Update: PUT `/api/v1/notes/folders/{folder}`
- [ ] Delete: DELETE `/api/v1/notes/folders/{folder}`

### F17.5 Note trash

- [ ] GET `/api/v1/notes/trash`
- [ ] Restore: POST `/api/v1/notes/trash/{note}/restore`
- [ ] Force delete: DELETE `/api/v1/notes/trash/{note}/force`
- [ ] Empty trash: DELETE `/api/v1/notes/trash`

### F17.6 Note access management

- [ ] Reuse `AccessManagementPanel` from Module F13
- [ ] GET `/api/v1/notes/{note}/access`
- [ ] POST `/api/v1/notes/{note}/access`
- [ ] PUT `/api/v1/notes/{note}/access/{grant}`
- [ ] DELETE `/api/v1/notes/{note}/access/{grant}`
- [ ] POST `/api/v1/notes/{note}/access/revoke-all`

### F17.7 Hooks

- [ ] `useNotes(params)` — query
- [ ] `useNote(id)` — query
- [ ] `useCreateNote()` — mutation
- [ ] `useUpdateNote(id)` — mutation
- [ ] `useDeleteNote()` — mutation
- [ ] `useTogglePin()` — mutation
- [ ] `useSearchNotes(q)` — query
- [ ] `useNoteFolders()` — query
- [ ] `useNoteTrash(params)` — query

---

## API Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/v1/notes` | List notes |
| `POST` | `/api/v1/notes` | Create note |
| `GET` | `/api/v1/notes/search` | Search notes |
| `GET` | `/api/v1/notes/{note}` | Show note |
| `PUT` | `/api/v1/notes/{note}` | Update note |
| `DELETE` | `/api/v1/notes/{note}` | Delete note |
| `POST` | `/api/v1/notes/{note}/pin` | Toggle pin |
| `GET` | `/api/v1/notes/trash` | Trash list |
| `POST` | `/api/v1/notes/trash/{note}/restore` | Restore |
| `DELETE` | `/api/v1/notes/trash/{note}/force` | Force delete |
| `DELETE` | `/api/v1/notes/trash` | Empty trash |
| `GET/POST` | `/api/v1/notes/folders` | Folder CRUD |
| `PUT/DELETE` | `/api/v1/notes/folders/{folder}` | Folder update/delete |
| `GET/POST` | `/api/v1/notes/{note}/access` | Access management |
| `PUT/DELETE` | `/api/v1/notes/{note}/access/{grant}` | Update/revoke grant |
| `POST` | `/api/v1/notes/{note}/access/revoke-all` | Revoke all |

---

## Acceptance Criteria

- [ ] User can create, edit, and delete secure notes
- [ ] User can pin and unpin notes
- [ ] User can search notes
- [ ] User can organize notes in folders
- [ ] Deleted notes go to trash and can be restored or force-deleted
- [ ] User can manage access to notes
- [ ] Pinned notes appear at the top of the list

---

## What This Module Does NOT Include

- Secure links for notes (Module F15)
- Note activity logs (Module F19)
