# Module F16 — Secure Files

| Field | Value |
|---|---|
| **Module** | F16 |
| **Name** | Secure Files |
| **Dependencies** | F03, F10, F13 |
| **Status** | Complete |

---

## Objective

Build the secure files management UI: upload, download, replace, delete files. File folders. File access management. File trash.

---

## Tasks

### F16.1 File list page

- [ ] Route: `/files`
- [ ] GET `/api/v1/files` (paginated, tenant-scoped)
- [ ] Grid/list view of files: name, size, type, owner, folder
- [ ] Upload button + drag-and-drop zone
- [ ] Bulk upload: POST `/api/v1/files/bulk`
- [ ] File folder sidebar

### F16.2 File detail / actions

- [ ] Download: GET `/api/v1/files/{file}/download` (triggers file download)
- [ ] Update metadata: PUT `/api/v1/files/{file}`
- [ ] Replace file: POST `/api/v1/files/{file}/replace`
- [ ] Delete: DELETE `/api/v1/files/{file}`
- [ ] Archive: POST `/api/v1/files/{file}/archive`
- [ ] Restore: POST `/api/v1/files/{file}/restore`

### F16.3 File folders

- [ ] List: GET `/api/v1/files/folders`
- [ ] Create: POST `/api/v1/files/folders`
- [ ] Update: PUT `/api/v1/files/folders/{folder}`
- [ ] Delete: DELETE `/api/v1/files/folders/{folder}`

### F16.4 File trash

- [ ] GET `/api/v1/files/trash`
- [ ] Restore: POST `/api/v1/files/trash/{file}/restore`
- [ ] Force delete: DELETE `/api/v1/files/trash/{file}/force`
- [ ] Empty trash: DELETE `/api/v1/files/trash`

### F16.5 File access management

- [ ] Reuse `AccessManagementPanel` from Module F13
- [ ] GET `/api/v1/files/{file}/access`
- [ ] POST `/api/v1/files/{file}/access`
- [ ] PUT `/api/v1/files/{file}/access/{grant}`
- [ ] DELETE `/api/v1/files/{file}/access/{grant}`
- [ ] POST `/api/v1/files/{file}/access/revoke-all`

### F16.6 Hooks

- [ ] `useFiles(params)` — query
- [ ] `useUploadFile()` — mutation (multipart form data)
- [ ] `useBulkUploadFiles()` — mutation
- [ ] `useDownloadFile(id)` — query (blob response)
- [ ] `useUpdateFile(id)` — mutation
- [ ] `useReplaceFile(id)` — mutation
- [ ] `useDeleteFile()` — mutation
- [ ] `useArchiveFile()` — mutation
- [ ] `useRestoreFile()` — mutation
- [ ] `useFileFolders()` — query
- [ ] `useFileTrash(params)` — query

---

## API Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/v1/files` | List files |
| `POST` | `/api/v1/files` | Upload file |
| `POST` | `/api/v1/files/bulk` | Bulk upload |
| `GET` | `/api/v1/files/{file}` | File details |
| `GET` | `/api/v1/files/{file}/download` | Download |
| `PUT` | `/api/v1/files/{file}` | Update |
| `POST` | `/api/v1/files/{file}/replace` | Replace |
| `DELETE` | `/api/v1/files/{file}` | Delete |
| `POST` | `/api/v1/files/{file}/archive` | Archive |
| `POST` | `/api/v1/files/{file}/restore` | Restore |
| `GET` | `/api/v1/files/trash` | Trash list |
| `POST` | `/api/v1/files/trash/{file}/restore` | Restore from trash |
| `DELETE` | `/api/v1/files/trash/{file}/force` | Force delete |
| `DELETE` | `/api/v1/files/trash` | Empty trash |
| `GET/POST` | `/api/v1/files/folders` | Folder CRUD |
| `PUT/DELETE` | `/api/v1/files/folders/{folder}` | Folder update/delete |
| `GET/POST` | `/api/v1/files/{file}/access` | Access management |
| `PUT/DELETE` | `/api/v1/files/{file}/access/{grant}` | Update/revoke grant |
| `POST` | `/api/v1/files/{file}/access/revoke-all` | Revoke all |

---

## Acceptance Criteria

- [ ] User can upload files (single and bulk)
- [ ] User can download files
- [ ] User can replace, update, delete, and archive files
- [ ] User can organize files in folders
- [ ] Deleted files go to trash and can be restored or force-deleted
- [ ] User can manage access to files (grant, revoke, revoke-all)
- [ ] Large file uploads show progress indicator

---

## What This Module Does NOT Include

- Secure links for files (Module F15)
- File activity logs (Module F19)
