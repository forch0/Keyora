# Module F06 — Personal Vault: Folders, Tags & Organization

| Field | Value |
|---|---|
| **Module** | F06 |
| **Name** | Personal Vault — Part 3: Folders, Tags & Organization |
| **Dependencies** | F04, F05 |
| **Status** | Not Started |

---

## Objective

Build the folder tree sidebar, tag management, and wire folder/tag selectors into the create/edit forms from F05. Users need to organize their vault items.

---

## Tasks

### F06.1 Folder tree sidebar

- [ ] Create `src/features/vault/components/FolderTree.tsx`
- [ ] Fetch folders: GET `/api/v1/vault/folders`
- [ ] Render nested tree structure
- [ ] Click folder → filter vault list by folder
- [ ] "All Items" root node
- [ ] Expand/collapse folders
- [ ] Drag-and-drop items between folders (optional — can defer)

### F06.2 Folder CRUD

- [ ] Create folder dialog: name + parent folder selector
- [ ] POST `/api/v1/vault/folders`
- [ ] Rename folder: PUT `/api/v1/vault/folders/{folder}`
- [ ] Delete folder: DELETE `/api/v1/vault/folders/{folder}` (items move to root)
- [ ] Confirmation on delete: "Items in this folder will be moved to root."

### F06.3 Tag management

- [ ] Create `src/features/vault/components/TagSelector.tsx`
- [ ] Fetch tags: GET `/api/v1/vault/tags`
- [ ] Multi-select tag picker in create/edit forms
- [ ] Create new tag inline: POST `/api/v1/vault/tags`
- [ ] Tag filter chips on list page
- [ ] Tag management page (list, rename, delete):
  - PUT `/api/v1/vault/tags/{tag}`
  - DELETE `/api/v1/vault/tags/{tag}`

### F06.4 Wire into F05 forms

- [ ] Update create/edit forms to include folder selector
- [ ] Update create/edit forms to include tag selector
- [ ] Update vault list to show folder filter sidebar
- [ ] Update vault list to show tag filter chips

### F06.5 Hooks

- [ ] `useFolders()` — query: GET `/api/v1/vault/folders`
- [ ] `useCreateFolder()` — mutation: POST `/api/v1/vault/folders`
- [ ] `useUpdateFolder(id)` — mutation: PUT `/api/v1/vault/folders/{folder}`
- [ ] `useDeleteFolder()` — mutation: DELETE `/api/v1/vault/folders/{folder}`
- [ ] `useTags()` — query: GET `/api/v1/vault/tags`
- [ ] `useCreateTag()` — mutation: POST `/api/v1/vault/tags`
- [ ] `useUpdateTag(id)` — mutation: PUT `/api/v1/vault/tags/{tag}`
- [ ] `useDeleteTag()` — mutation: DELETE `/api/v1/vault/tags/{tag}`

---

## API Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/v1/vault/folders` | List folders |
| `POST` | `/api/v1/vault/folders` | Create folder |
| `PUT` | `/api/v1/vault/folders/{folder}` | Rename folder |
| `DELETE` | `/api/v1/vault/folders/{folder}` | Delete folder |
| `GET` | `/api/v1/vault/tags` | List tags |
| `POST` | `/api/v1/vault/tags` | Create tag |
| `PUT` | `/api/v1/vault/tags/{tag}` | Rename tag |
| `DELETE` | `/api/v1/vault/tags/{tag}` | Delete tag |

---

## Acceptance Criteria

- [ ] User can create nested folders
- [ ] Folder tree displays correctly with expand/collapse
- [ ] Clicking a folder filters the vault list
- [ ] User can rename and delete folders
- [ ] Deleting a folder moves its items to root
- [ ] User can create tags and assign them to items
- [ ] User can filter items by tag
- [ ] User can rename and delete tags
- [ ] Create/edit forms include folder and tag selectors

---

## What This Module Does NOT Include

- Trash management (Module F07)
- Bulk operations (Module F07)
- Drag-and-drop (can be added later)
