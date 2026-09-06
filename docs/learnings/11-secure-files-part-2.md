# 12 — How Module 12 (Secure Files Part 2) Was Built

| Field | Value |
|---|---|
| **Module** | 12 — Secure Files Part 2 (Sharing & Access Control) |
| **Date** | 2026-09-02 |
| **Spec** | `docs/modules/12-secure-files-part-2.md` |

---

## Goal

Connect the permission system (Modules 08-09) to secure files. Enable sharing files with individuals, teams, or company. Enforce view-only vs. downloadable permissions. Handle file expiration.

---

## Decisions Made Before Writing Code

### 1. Reuse existing AccessGrant infrastructure

The `AccessGrant` model from Module 08 is polymorphic — it already supports any `grantable_type`. Secure files simply use `SecureFile::class` as the `grantable_type`. No schema changes needed.

### 2. Reuse existing Actions

`GrantAccessAction`, `UpdateAccessAction`, and `RevokeAccessAction` from Module 09 are reused. The `FileAccessController` delegates to these actions, keeping controllers thin.

### 3. 410 Gone for expired files

When a file's `expires_at` is in the past, the download endpoint returns `410 Gone` (not 403 or 404). This semantically indicates the resource existed but is no longer available.

### 4. Scheduled command for expiration

`files:expire` runs daily at midnight, soft-deletes expired files, dispatches `FileExpired` event, and notifies the file owner. This is registered in `routes/console.php` using Laravel 11's `Schedule` facade.

### 5. download_enabled = false blocks everyone

When `download_enabled` is false, the policy's `download()` method returns false immediately — even the uploader cannot download. This is a hard kill switch for sensitive files.

---

## Files Created

| File | Purpose |
|---|---|
| `app/Http/Controllers/Api/V1/FileAccessController.php` | File sharing CRUD (index, store, update, destroy) |
| `app/Events/FileExpired.php` | Event dispatched when a file expires |
| `app/Notifications/FileExpiredNotification.php` | Email + database notification to file owner |
| `app/Console/Commands/ExpireFiles.php` | Scheduled command to soft-delete expired files |
| `tests/Feature/Api/V1/Files/FileAccessTest.php` | 12 feature tests |

## Files Modified

| File | Change |
|---|---|
| `app/Http/Controllers/Api/V1/SecureFileController.php` | Added 410 Gone check for expired files in download() |
| `routes/api.php` | Added 4 file access routes |
| `routes/console.php` | Registered `files:expire` scheduled command |

---

## New Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/files/{file}/access` | List who has access to this file |
| POST | `/api/v1/files/{file}/access` | Grant access to a user/team/company |
| PUT | `/api/v1/files/{file}/access/{grant}` | Update access permission |
| DELETE | `/api/v1/files/{file}/access/{grant}` | Revoke access |

---

## Bugs Found and Fixed During Implementation

### 1. Sanctum auth state leakage in sequential HTTP requests

**Cause:** Same issue as Module 11 — when making sequential HTTP requests with different bearer tokens in the same test, the first request's authenticated user persists.

**Fix:** For tests requiring a second user (viewer, non-shared member), create the file directly via `SecureFile::factory()` instead of via HTTP upload. This avoids the auth state from the upload request leaking into the download/view request.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 174 tests, 489 assertions (12 new + 162 from Modules 02-11) |

---

## Key Takeaways

1. **Polymorphic access grants are powerful** — the Module 08 design with `grantable_type`/`grantable_id` morphic columns meant zero schema changes were needed to support file sharing. The same `AccessGrant` model, `AccessResolver`, and Actions work for vault items, secure files, and any future resource type.

2. **410 Gone is the right status for expired resources** — it distinguishes "you don't have permission" (403) from "this resource is no longer available" (410). The client can handle these differently.

3. **Scheduled commands in Laravel 11** — use `Schedule::command()` in `routes/console.php` instead of the old `Console\Kernel.php` approach.

4. **download_enabled is a hard switch** — when false, the policy returns false before any other checks. This ensures sensitive files can be locked down immediately regardless of existing grants.
