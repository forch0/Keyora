# 30 — How Module 30 (Bulk Operations) Was Built

| Field | Value |
|---|---|
| **Module** | 30 — Bulk Operations |
| **Date** | 2026-09-02 |
| **Spec** | `docs/modules/30-bulk-operations.md` |

---

## Goal

Add bulk operation endpoints for common multi-resource actions: batch delete, move, archive, restore, tag, share, and create. All business logic in a service, controllers thin.

---

## Decisions Made Before Writing Code

### 1. Service over Actions

Used a `BulkOperationService` class (per spec) rather than individual Action classes. Bulk operations share common patterns (iterate, check ownership, act, count) so a service with multiple methods is cleaner than 7 separate Action classes.

### 2. Per-item authorization with failed counting

Each bulk method iterates over IDs, checks ownership per item, and counts failures rather than erroring. This matches the spec: "Items the actor can't access are counted as failed (not errored)."

### 3. All operations in DB transactions

Every bulk method wraps its iteration in `DB::transaction()` for atomicity.

### 4. Single activity log entry per bulk operation

Each bulk operation logs one `bulk_*` activity entry with the count, not one per item.

### 5. Reauth on bulk share only

Bulk share requires re-authentication (sensitive — creates access grants). Other bulk operations use `rate.limit:write`.

### 6. Max limits enforced via Form Request validation

- 100 items for bulk operations (delete, move, archive, restore, tag, share)
- 50 items for bulk create

---

## Files Created

| File | Purpose |
|---|---|
| `app/Services/BulkOperationService.php` | 7 bulk methods, all transactional |
| `app/Http/Controllers/Api/V1/BulkOperationController.php` | Thin controller, delegates to service |
| `app/Http/Requests/Bulk/BulkDeleteRequest.php` | ids (max:100) |
| `app/Http/Requests/Bulk/BulkMoveRequest.php` | ids + folder_id |
| `app/Http/Requests/Bulk/BulkArchiveRequest.php` | ids (max:100) |
| `app/Http/Requests/Bulk/BulkTagRequest.php` | ids + tag_ids + action (attach/detach/sync) |
| `app/Http/Requests/Bulk/BulkShareRequest.php` | ids + subject_type/id + permission + expires_at |
| `app/Http/Requests/Bulk/BulkCreateRequest.php` | items (max:50) with per-item validation |
| `tests/Feature/Api/V1/BulkOperations/BulkOperationsTest.php` | 16 feature tests |

---

## Endpoints

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/v1/personal-vault/items/bulk/delete` | Bulk delete |
| POST | `/api/v1/personal-vault/items/bulk/move` | Bulk move to folder |
| POST | `/api/v1/personal-vault/items/bulk/archive` | Bulk archive |
| POST | `/api/v1/personal-vault/items/bulk/restore` | Bulk restore from archive |
| POST | `/api/v1/personal-vault/items/bulk/tag` | Bulk tag (attach/detach/sync) |
| POST | `/api/v1/personal-vault/items/bulk/share` | Bulk share (reauth required) |
| POST | `/api/v1/personal-vault/items/bulk/create` | Bulk create (max 50) |

---

## Bugs Found and Fixed During Implementation

### 1. PHPStan: `Model::find()` returns `Model|Collection|null`

PHPStan flagged that `$modelClass::find($id)` can return a Collection (for multi-key finds). Created a `findItem()` helper that checks `instanceof Model` and returns null otherwise.

### 2. PHPStan: `tags()` method not found on generic Model

The `bulkTag` method calls `$item->tags()` but PHPStan can't resolve methods on a generic `Model`. Wrapped in `method_exists($item, 'tags')` check.

### 3. PHPStan: `tenant_id` property not found on generic Model

Used `$item->getAttribute('tenant_id')` instead of direct property access.

### 4. Foreign key constraint on access_grants.tenant_id

PersonalVaultItem doesn't have a `tenant_id`. The initial code used `user_id` as `tenant_id`, which failed the FK constraint. Fixed by getting the actor's tenant from `$actor->tenants()->first()?->id`.

### 5. Pivot table column names

The `personal_vault_item_tag` pivot uses `item_id` and `tag_id` (not the default Eloquent naming). Tests were updated to use the correct column names.

### 6. `withArchived()` method doesn't exist

Used `PersonalVaultItem::find($id)` directly (archived items are still queryable, just excluded by scope).

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 407 tests, 1225 assertions (16 new + 391 existing) |
