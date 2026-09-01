# Module 12 — Secure Files: Part 2 (Sharing & Access)

| Field | Value |
|---|---|
| **Module** | 12 |
| **Name** | Secure Files — Sharing & Access Control |
| **Dependencies** | Module 08, Module 09, Module 11 |
| **Status** | Not Started |

---

## Objective

Connect the permission system (Module 08-09) to secure files. Enable sharing files with individuals, teams, or company. Enforce view-only vs. downloadable permissions. Handle file expiration.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| SF-06 | Files can be shared with permissions (view-only or downloadable) | P0 |
| SF-07 | Downloads can be disabled on shared files | P0 |
| SF-08 | File expiration (auto-delete after date) | P1 |
| SF-09 | Temporary and one-time file access | P0 → Module 14 |
| SF-12 | Full file access history (who viewed/downloaded and when) | P0 → Module 20 |

---

## Tasks

### 12.1 Extend AccessGrant for Files

- [ ] Ensure `AccessGrant` supports `SecureFile` as `grantable_type` (polymorphic — already supported by design in Module 08)
- [ ] Add file-specific sharing endpoints (same pattern as vault items)

### 12.2 API Endpoints — File Access

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/files/{file}/access` | List who has access to this file |
| `POST` | `/api/v1/files/{file}/access` | Grant access to a user/team/company |
| `PUT` | `/api/v1/files/{file}/access/{grant}` | Update access permission |
| `DELETE` | `/api/v1/files/{file}/access/{grant}` | Revoke access |

### 12.3 Controller

- [ ] `FileAccessController.php`:
  - `index()` — list access grants for a file
  - `store()` — grant access (uses `GrantAccessAction` from Module 09)
  - `update()` — update permission (uses `UpdateAccessAction`)
  - `destroy()` — revoke access (uses `RevokeAccessAction`)

### 12.4 Update SecureFilePolicy

- [ ] Integrate with `AccessResolver` from Module 08:
  - `view()` — uploader, team member, or `AccessResolver::can(user, Permission::View, file)`
  - `download()` — must have `Permission::Download` AND `file.download_enabled === true`
  - `update()` — uploader or `AccessResolver::can(user, Permission::Edit, file)`
  - `delete()` — uploader or `AccessResolver::can(user, Permission::Manage, file)`

### 12.5 Download Permission Enforcement

- [ ] Update `SecureFileController::download()`:
  - Check `AccessResolver::can(user, Permission::Download, file)`
  - Check `file.download_enabled === true`
  - If file has `expires_at` and it's in the past → `410 Gone`
  - Increment `views_count` on the access grant if applicable
  - Log download event (Module 20 — stub for now)

### 12.6 View-Only Files

- [ ] When `download_enabled = false`:
  - Download endpoint returns `403` with message "Downloads are disabled for this file"
  - File metadata is still accessible via `GET /files/{file}`
  - In future: in-platform viewer (post-MVP)

### 12.7 File Expiration

- [ ] Create `app/Console/Commands/ExpireFiles.php`:
  - Scheduled daily
  - Finds files where `expires_at <= now()` and `deleted_at IS NULL`
  - Soft-deletes expired files
  - Dispatches `FileExpired` event for each
  - Notifies file owner

### 12.8 Notification

- [ ] `app/Notifications/FileExpiredNotification.php`:
  - Sent to file owner when file expires
  - Includes file name and expiration time

### 12.9 Routes

```php
Route::middleware(['auth:sanctum', 'tenant.resolve'])->prefix('v1/files/{file}/access')->group(function () {
    Route::get('/', [FileAccessController::class, 'index']);
    Route::post('/', [FileAccessController::class, 'store']);
    Route::put('/{grant}', [FileAccessController::class, 'update']);
    Route::delete('/{grant}', [FileAccessController::class, 'destroy']);
});
```

### 12.10 Scheduled Commands

```php
// app/Console/Kernel.php
$schedule->command('files:expire')->dailyAt('00:00');
```

---

## Acceptance Criteria

- [ ] User can share a file with an individual with `view` permission
- [ ] User can share a file with an individual with `download` permission
- [ ] User can share a file with a team
- [ ] User can share a file company-wide
- [ ] User with `view` permission can see file metadata but cannot download → `403`
- [ ] User with `download` permission can download the file
- [ ] When `download_enabled = false`, no one can download (even with download permission) → `403`
- [ ] User can change someone's file access permission
- [ ] User can revoke file access
- [ ] User can view who has access to a file
- [ ] File with `expires_at` in the past cannot be downloaded → `410`
- [ ] Scheduled command soft-deletes expired files daily
- [ ] File owner is notified when their file expires

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_can_share_file_with_individual` | POST creates access grant |
| `test_can_share_file_with_team` | POST creates team grant |
| `test_view_permission_cannot_download` | View-only user → 403 on download |
| `test_download_permission_can_download` | Download user gets file |
| `test_download_disabled_blocks_all` | download_enabled=false → 403 for all |
| `test_can_change_file_permission` | PUT updates permission |
| `test_can_revoke_file_access` | DELETE revokes |
| `test_can_view_file_access_list` | GET returns grants |
| `test_expired_file_cannot_be_downloaded` | Expired file → 410 |
| `test_expire_command_deletes_files` | Command soft-deletes expired files |
| `test_file_owner_notified_on_expiry` | Notification sent |
| `test_non_shared_user_cannot_access_file` | No grant → 403 |

---

## What This Module Does NOT Include

- Temporary/one-time file access with durations (Module 14)
- File access history logging (Module 20)
- In-platform file preview (post-MVP)
- File version history (post-MVP)
