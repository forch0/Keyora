# 11 — How Module 11 (Secure Files Part 1) Was Built

| Field | Value |
|---|---|
| **Module** | 11 — Secure Files Part 1 (Storage & Management) |
| **Date** | 2026-09-02 |
| **Spec** | `docs/modules/11-secure-files-part-1.md` |

---

## Goal

Build the secure file storage system — upload, list, download, replace, archive, restore, and delete files. Files are stored in private storage and served through permission-checked controllers. Max 10 MB per file.

---

## Decisions Made Before Writing Code

### 1. Private disk storage

Files stored at `storage/app/private/{tenant_id}/{uuid}/{filename}` — NOT web-accessible. Only served through the controller after permission checks.

### 2. SHA-256 checksums

Each file gets a SHA-256 checksum calculated at upload time. Used for deduplication detection and integrity verification.

### 3. Soft deletes in DB, hard delete from storage

When deleting a file, the physical file is removed from storage immediately, but the DB record is soft-deleted (`deleted_at`) to preserve audit history.

### 4. Folder deletion moves files to root

Deleting a folder doesn't delete its files — files are moved to root (`folder_id = null`). Child folders are also moved to root.

### 5. Policy checks uploader first, then admin, then team, then grants

The `SecureFilePolicy` checks in order: uploader → tenant admin → team member → access grants. This is consistent with `VaultItemPolicy` from Module 08.

---

## Files Created

| File | Purpose |
|---|---|
| `app/Models/SecureFile.php` | File model (BelongsToTenant, SoftDeletes, scopes, human size) |
| `app/Models/FileFolder.php` | Folder model (BelongsToTenant, self-referencing parent/children) |
| `app/Actions/UploadFileAction.php` | Validate, store to private disk, calculate checksum, dispatch event |
| `app/Events/FileUploaded.php` | Event for audit logging (Module 20) |
| `app/Http/Requests/Files/UploadFileRequest.php` | Validate file upload (max 10MB) |
| `app/Http/Requests/Files/UpdateFileRequest.php` | Validate metadata update |
| `app/Http/Requests/Files/ReplaceFileRequest.php` | Validate file replacement |
| `app/Http/Requests/Files/CreateFileFolderRequest.php` | Validate folder creation |
| `app/Http/Requests/Files/UpdateFileFolderRequest.php` | Validate folder update |
| `app/Http/Resources/V1/SecureFileResource.php` | File API resource |
| `app/Http/Resources/V1/FileFolderResource.php` | Folder API resource |
| `app/Policies/SecureFilePolicy.php` | view/download/update/delete/replace authorization |
| `app/Http/Controllers/Api/V1/SecureFileController.php` | Full file CRUD + archive/restore |
| `app/Http/Controllers/Api/V1/FileFolderController.php` | Folder CRUD |
| `database/factories/SecureFileFactory.php` | Factory for tests |
| `database/factories/FileFolderFactory.php` | Factory for tests |
| `tests/Feature/Api/V1/Files/SecureFileTest.php` | 18 feature tests |

---

## All New Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/files` | List files (paginated, filterable) |
| POST | `/api/v1/files` | Upload single file |
| POST | `/api/v1/files/bulk` | Upload multiple files |
| GET | `/api/v1/files/{file}` | Get file metadata |
| GET | `/api/v1/files/{file}/download` | Download file (stream) |
| PUT | `/api/v1/files/{file}` | Update file metadata |
| POST | `/api/v1/files/{file}/replace` | Replace file content |
| DELETE | `/api/v1/files/{file}` | Delete file |
| POST | `/api/v1/files/{file}/archive` | Archive file |
| POST | `/api/v1/files/{file}/restore` | Restore archived file |
| GET | `/api/v1/files/folders` | List file folders (tree) |
| POST | `/api/v1/files/folders` | Create folder |
| PUT | `/api/v1/files/folders/{folder}` | Update folder |
| DELETE | `/api/v1/files/folders/{folder}` | Delete folder (files moved to root) |

---

## Bugs Found and Fixed During Implementation

### 1. Sanctum auth state leakage between sequential HTTP requests in tests

**Cause:** When making sequential HTTP requests with different bearer tokens in the same test method, the first request's authenticated user persisted into the second request.

**Fix:** For tests that need a different user for the second request, create the file directly via the model factory instead of via HTTP upload, avoiding the auth state from the first request.

### 2. PHPStan: `isAdminOf()` receives `Tenant|null`

**Cause:** `$file->tenant` can return null (relationship not loaded or record missing).

**Fix:** Assign `$tenant = $file->tenant` first, then check `$tenant !== null && $user->isAdminOf($tenant)`.

### 3. Pagination structure mismatch in test

**Cause:** Test expected `meta.pagination` but Laravel 11's default pagination uses `meta` with `current_page`, `from`, etc. directly.

**Fix:** Updated test to assert `['data', 'links', 'meta']` structure.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 162 tests, 466 assertions (18 new + 144 from Modules 02-10) |

---

## Key Takeaways

1. **Private storage is critical** — files must never be web-accessible. The `private` disk in Laravel ensures files are stored outside the public directory and can only be served through controller logic after permission checks.

2. **Soft delete DB + hard delete storage** — the DB record is soft-deleted for audit history, but the physical file is removed immediately to free disk space. This is the right tradeoff for file storage.

3. **Folder deletion moves files, not deletes them** — deleting a folder moves its files to root rather than deleting them. This prevents accidental data loss when reorganizing.

4. **Auth state leakage in tests** — when making sequential HTTP requests with different users in the same test, the first request's auth state can persist. For tests requiring a second user, create data directly via factories rather than HTTP requests.

5. **File path format: `{tenant_id}/{uuid}/{filename}`** — this ensures tenant isolation at the filesystem level and prevents filename collisions.
