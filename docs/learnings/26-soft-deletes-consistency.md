# 29 — How Module 29 (Soft Deletes Consistency) Was Built

| Field | Value |
|---|---|
| **Module** | 29 — Soft Deletes Consistency |
| **Date** | 2026-09-02 |
| **Spec** | `docs/modules/29-soft-deletes-consistency.md` |

---

## Goal

Add soft deletes to all 7 user-managed content models that were missing them, plus restore, force-delete, trash listing, and empty-trash endpoints. Keep controllers thin — all business logic in Actions.

---

## Decisions Made Before Writing Code

### 1. Actions over Service class

Initially created a `SoftDeleteService` but the user reminded: "no business rules in controllers, use actions or services." Refactored into 4 single-purpose Action classes:

- `RestoreModelAction` — restores a trashed model, logs activity
- `ForceDeleteModelAction` — permanently deletes, cleans up related data (physical files, access grants, access requests), logs activity
- `ListTrashAction` — paginated list of trashed items scoped by user or tenant
- `EmptyTrashAction` — permanently deletes all trashed items for a model class

### 2. Generic Actions work with any soft-deletable model

Each Action accepts a `class-string<Model>` parameter, so the same Action handles PersonalVaultItem, SecureFile, SecureNote, and Team. The `ownerColumn` parameter ('user_id' vs 'tenant_id') controls scoping.

### 3. Force-delete cleanup

`ForceDeleteModelAction::cleanupRelatedData()` handles:
- **SecureFile**: deletes the physical file from private storage
- **AccessGrant**: force-deletes all grants where `grantable_type/id` matches
- **AccessRequest**: force-deletes all requests where `requestable_type/id` matches

### 4. Reauth required for destructive operations

Force-delete and empty-trash routes use `reauth` + `rate.limit:sensitive` middleware. Restore uses `rate.limit:write` (less destructive).

### 5. Models that should NOT have soft deletes

ActivityLog (append-only audit), User (uses status=left), TenantInvitation (transient), ResourceView (analytics), SecureLinkAccess (access tracking), Folders/Tags (reference data with cascade delete).

---

## Files Created/Modified

| File | Purpose |
|---|---|
| `database/migrations/2026_09_03_290000_add_soft_deletes_to_content_models.php` | Adds `deleted_at` to 7 tables |
| `app/Actions/RestoreModelAction.php` | Restores a trashed model |
| `app/Actions/ForceDeleteModelAction.php` | Permanently deletes + cleanup |
| `app/Actions/ListTrashAction.php` | Paginated trash listing |
| `app/Actions/EmptyTrashAction.php` | Empty all trashed items |
| `app/Models/PersonalVaultItem.php` | Added SoftDeletes trait |
| `app/Models/Team.php` | Added SoftDeletes trait |
| `app/Models/AccessGrant.php` | Added SoftDeletes trait |
| `app/Models/AccessRequest.php` | Added SoftDeletes trait |
| `app/Models/SecureLink.php` | Added SoftDeletes trait |
| `app/Models/SecurityAlert.php` | Added SoftDeletes trait |
| `app/Models/UserDevice.php` | Added SoftDeletes trait |
| `app/Http/Controllers/Api/V1/PersonalVaultItemController.php` | Added trash/restoreFromTrash/forceDelete/emptyTrash |
| `app/Http/Controllers/Api/V1/SecureFileController.php` | Added trash/restoreFromTrash/forceDelete/emptyTrash |
| `app/Http/Controllers/Api/V1/SecureNoteController.php` | Added trash/restoreFromTrash/forceDelete/emptyTrash |
| `app/Http/Controllers/Api/V1/TeamController.php` | Added trash/restore/forceDelete |
| `routes/api.php` | Added trash/restore/force/empty routes for vault, files, notes, teams |
| `tests/Feature/Api/V1/SoftDeletes/SoftDeletesTest.php` | 12 feature tests |

---

## Bugs Found and Fixed During Implementation

### 1. Script inserted `use SoftDeletes;` in import section

The sed script matched `use.*HasFactory;` in the import section (not just the trait use inside the class body), inserting `use SoftDeletes;` as an import statement. This caused "Cannot use SoftDeletes as SoftDeletes because the name is already in use" errors.

**Fix:** Used awk to only add `use SoftDeletes;` after the first `use.*HasFactory;` line that appears inside the class body (after `class ...`).

### 2. Existing tests used `assertDatabaseMissing` after delete

4 existing tests expected rows to be completely gone after delete. With soft deletes, the row still exists (with `deleted_at` set).

**Fix:** Changed `assertDatabaseMissing` to `assertSoftDeleted` for Team, PersonalVaultItem, and UserDevice tests. For SecurityAlert test, changed `delete()` to `forceDelete()` since the test was clearing alerts between assertions.

### 3. Route name conflict — `restore` method name

PersonalVaultItem and SecureFile already had a `restore` method for unarchiving (archive restore). The new soft-delete restore needed a different name.

**Fix:** Named the new method `restoreFromTrash` and used `/trash/{id}/restore` route path to distinguish from `/items/{id}/restore` (archive restore).

### 4. AccessGrant missing `subject_type` in test

The test created an AccessGrant without `subject_type`/`subject_id`, which are NOT NULL columns.

**Fix:** Added the required fields to the test fixture.

### 5. ForceDeleteModelAction checked for `accessGrants` method

The cleanup logic used `method_exists($model, 'accessGrants')` but no model defines that relationship method. Access grants use polymorphic columns (`grantable_type/id`) without a back-reference.

**Fix:** Changed to always clean up access grants and access requests by querying the morph columns directly.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 391 tests, 1148 assertions (12 new + 379 existing) |
