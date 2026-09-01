# Module 11 — Secure Files: Part 1 (Storage & Management)

| Field | Value |
|---|---|
| **Module** | 11 |
| **Name** | Secure Files — Storage & Management |
| **Dependencies** | Module 01, Module 03 |
| **Status** | Not Started |

---

## Objective

Build the secure file storage system — upload, list, download, replace, archive, restore, and delete files. Files are stored in private storage and served through permission-checked controllers. Max 10 MB per file.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| SF-01 | Upload files (max 10 MB per file) | P0 |
| SF-02 | Upload multiple files at once | P1 |
| SF-03 | Files can be organized into folders | P1 |
| SF-04 | Files can be searched by name and metadata | P1 |
| SF-11 | Archive, restore, and delete files | P1 |
| SF-12 | Full file access history | P0 (structure only — logging in Module 20) |

---

## Tasks

### 11.1 Secure Files Table & Model

- [ ] Create `secure_files` migration:

```
secure_files
  id              -- bigIncrements
  tenant_id       -- foreignId (constrained, cascadeOnDelete)
  team_id         -- foreignId, nullable (constrained, nullOnDelete)
  user_id         -- foreignId (constrained, cascadeOnDelete) — uploader
  folder_id       -- foreignId, nullable (constrained, nullOnDelete)
  name            -- string (original filename)
  file_path       -- string (storage path: {tenant_id}/{uuid}/{filename})
  mime_type       -- string
  size            -- bigInteger (bytes)
  checksum        -- string (sha256)
  description     -- text, nullable
  metadata        -- json, nullable
  download_enabled -- boolean, default true
  expires_at      -- timestamp, nullable (auto-delete after date)
  archived_at     -- timestamp, nullable
  created_at
  updated_at
  deleted_at      -- soft deletes

  index(tenant_id, team_id)
  index(tenant_id, user_id)
  index(tenant_id, folder_id)
  index(checksum)
```

- [ ] Create `SecureFile` model:
  - `use BelongsToTenant` trait
  - `use SoftDeletes`
  - `$fillable`: `name`, `file_path`, `mime_type`, `size`, `checksum`, `description`, `metadata`, `download_enabled`, `expires_at`, `folder_id`, `team_id`
  - `$casts`: `metadata` → `array`, `download_enabled` → `boolean`, `expires_at` → `datetime`, `archived_at` → `datetime`, `size` → `integer`
  - Relationships: `tenant()`, `team()`, `user()` (uploader), `folder()`

### 11.2 File Folders Table & Model

- [ ] Create `file_folders` migration:

```
file_folders
  id              -- bigIncrements
  tenant_id       -- foreignId (constrained, cascadeOnDelete)
  team_id         -- foreignId, nullable (constrained, nullOnDelete)
  name            -- string
  parent_id       -- foreignId, nullable (self-referencing)
  created_by      -- foreignId (users)
  created_at
  updated_at

  index(tenant_id, team_id)
  index(tenant_id, parent_id)
```

- [ ] Create `FileFolder` model with `BelongsToTenant` trait, self-referencing parent/children

### 11.3 File Storage Configuration

- [ ] Ensure private disk is configured in `config/filesystems.php` (from Module 01)
- [ ] Files stored at: `storage/app/private/{tenant_id}/{uuid}/{filename}`
- [ ] Files are NOT web-accessible — only served through controller

### 11.4 API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/files` | List files (paginated, filterable) |
| `POST` | `/api/v1/files` | Upload single file |
| `POST` | `/api/v1/files/bulk` | Upload multiple files |
| `GET` | `/api/v1/files/{file}` | Get file metadata |
| `GET` | `/api/v1/files/{file}/download` | Download file (stream) |
| `PUT` | `/api/v1/files/{file}` | Update file metadata (name, description, folder) |
| `POST` | `/api/v1/files/{file}/replace` | Replace file content (keep metadata) |
| `DELETE` | `/api/v1/files/{file}` | Delete file |
| `POST` | `/api/v1/files/{file}/archive` | Archive file |
| `POST` | `/api/v1/files/{file}/restore` | Restore archived file |
| `GET` | `/api/v1/files/folders` | List file folders (tree) |
| `POST` | `/api/v1/files/folders` | Create folder |
| `PUT` | `/api/v1/files/folders/{folder}` | Update folder |
| `DELETE` | `/api/v1/files/folders/{folder}` | Delete folder (files moved to root) |

### 11.5 Controller

- [ ] `SecureFileController.php`:
  - `index()` — list files with filters (team, folder, archived, type)
  - `store()` — handle file upload, validate size/mime, store to private disk, create record
  - `bulkStore()` — handle multiple files in one request
  - `show()` — return file metadata (not the file content)
  - `download()` — check download permission, stream file from private storage
  - `update()` — update metadata (name, description, folder, download_enabled)
  - `replace()` — upload new file content, update file_path and checksum, keep metadata
  - `destroy()` — delete file from storage + DB (soft delete)
  - `archive()` — set `archived_at`
  - `restore()` — clear `archived_at`
- [ ] `FileFolderController.php` — CRUD for file folders

### 11.6 Actions

- [ ] `app/Actions/UploadFileAction.php`:
  - Validate file size (max 10 MB)
  - Generate UUID for file path
  - Store file on private disk at `{tenant_id}/{uuid}/{filename}`
  - Calculate SHA-256 checksum
  - Create `SecureFile` record
  - Dispatch `FileUploaded` event
  - Return file model

### 11.7 Form Requests

- [ ] `UploadFileRequest`:
  - `file`: required, file, max:10240 (10 MB), mimes (allow common types)
  - `team_id`: nullable, exists:teams,id
  - `folder_id`: nullable, exists:file_folders,id
  - `description`: nullable, string, max:1000
- [ ] `UpdateFileRequest`:
  - `name`: sometimes, string, max:255
  - `description`: nullable, string, max:1000
  - `folder_id`: nullable, exists:file_folders,id
  - `download_enabled`: sometimes, boolean
  - `expires_at`: nullable, date, after:now

### 11.8 API Resource

- [ ] `SecureFileResource.php`:
  - `id`, `name`, `mime_type`, `size` (human-readable), `size_bytes`, `description`, `download_enabled`, `expires_at`, `archived_at`, `folder`, `uploaded_by`, `created_at`, `updated_at`
  - Include `download_url` (signed URL or API endpoint reference)

### 11.9 Policy

- [ ] `SecureFilePolicy.php`:
  - `view()` — user is uploader, team member, or has access grant (Module 08)
  - `download()` — same as view + `download_enabled` must be true + user must have download permission
  - `update()` — uploader or has edit/manage permission
  - `delete()` — uploader or has manage permission
  - `replace()` — uploader or has edit/manage permission

### 11.10 File Download Streaming

- [ ] Download endpoint streams file from private storage:
  - Check permissions first
  - Log the download event (Module 20 — stub for now)
  - Return `Storage::disk('private')->download($path, $name, $headers)`
  - Set appropriate `Content-Type` and `Content-Disposition` headers

### 11.11 Routes

```php
Route::middleware(['auth:sanctum', 'tenant.resolve'])->prefix('v1/files')->group(function () {
    Route::get('/', [SecureFileController::class, 'index']);
    Route::post('/', [SecureFileController::class, 'store']);
    Route::post('/bulk', [SecureFileController::class, 'bulkStore']);
    Route::get('/{file}', [SecureFileController::class, 'show']);
    Route::get('/{file}/download', [SecureFileController::class, 'download']);
    Route::put('/{file}', [SecureFileController::class, 'update']);
    Route::post('/{file}/replace', [SecureFileController::class, 'replace']);
    Route::delete('/{file}', [SecureFileController::class, 'destroy']);
    Route::post('/{file}/archive', [SecureFileController::class, 'archive']);
    Route::post('/{file}/restore', [SecureFileController::class, 'restore']);

    Route::get('/folders', [FileFolderController::class, 'index']);
    Route::post('/folders', [FileFolderController::class, 'store']);
    Route::put('/folders/{folder}', [FileFolderController::class, 'update']);
    Route::delete('/folders/{folder}', [FileFolderController::class, 'destroy']);
});
```

---

## Acceptance Criteria

- [ ] User can upload a file up to 10 MB → `201` with file metadata
- [ ] File larger than 10 MB → `422`
- [ ] File is stored in private storage (not web-accessible)
- [ ] File path follows `{tenant_id}/{uuid}/{filename}` pattern
- [ ] SHA-256 checksum is calculated and stored
- [ ] User can upload multiple files in one request
- [ ] User can list files with filters (team, folder, archived)
- [ ] User can download a file (streams from private storage)
- [ ] Download endpoint checks permissions before serving
- [ ] User can update file metadata (name, description, folder, download_enabled)
- [ ] User can replace file content (new file, same metadata record)
- [ ] User can archive a file
- [ ] User can restore an archived file
- [ ] User can delete a file (soft delete in DB, hard delete from storage)
- [ ] Files can be organized into folders
- [ ] Folders support nesting
- [ ] Deleting a folder moves files to root
- [ ] All file data is tenant-scoped

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_user_can_upload_file` | POST returns 201, file exists in storage |
| `test_file_over_10mb_rejected` | Large file → 422 |
| `test_file_stored_in_private_storage` | File not in public path |
| `test_file_path_includes_tenant_and_uuid` | Path format correct |
| `test_checksum_calculated` | SHA-256 stored |
| `test_user_can_upload_multiple_files` | POST /bulk creates multiple records |
| `test_user_can_list_files` | GET returns paginated files |
| `test_user_can_download_file` | GET /download streams file content |
| `test_download_checks_permissions` | Non-permitted user → 403 |
| `test_user_can_update_file_metadata` | PUT updates metadata |
| `test_user_can_replace_file` | POST /replace updates file content |
| `test_user_can_archive_file` | POST /archive sets archived_at |
| `test_user_can_restore_file` | POST /restore clears archived_at |
| `test_user_can_delete_file` | DELETE soft-deletes record |
| `test_user_can_create_file_folder` | POST creates folder |
| `test_user_can_organize_files_into_folders` | File assigned to folder |
| `test_deleting_folder_moves_files_to_root` | Files survive folder deletion |
| `test_files_are_tenant_scoped` | Tenant B can't see Tenant A's files |

---

## What This Module Does NOT Include

- File sharing and access grants (Module 12)
- Temporary/one-time file access (Module 14)
- File preview (post-MVP)
- File version history (post-MVP)
- Download activity logging (Module 20)
