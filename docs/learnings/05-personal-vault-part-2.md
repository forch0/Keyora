# 06 — How Module 06 (Personal Vault Part 2) Was Built

| Field | Value |
|---|---|
| **Module** | 06 — Personal Vault: Organization (Folders, Tags, Favorites, Search) |
| **Date** | 2026-09-01 |
| **Spec** | `docs/modules/06-personal-vault-part-2.md` |
| **Architecture ref** | `docs/ARCHITECTURE.md` §1.3 (ADR-003: Encryption Strategy) |

---

## Goal

Add organization features to the personal vault: nested folders, tags, favorites toggle, archive/restore, search, and recently accessed views.

---

## Decisions Made Before Writing Code

### 1. Explicit pivot keys

Laravel's `belongsToMany` default foreign key naming would use `personal_vault_item_id` but the pivot table uses `item_id`. Specified explicit keys in both directions:

```php
// PersonalVaultItem
return $this->belongsToMany(PersonalVaultTag::class, 'personal_vault_item_tag', 'item_id', 'tag_id');

// PersonalVaultTag
return $this->belongsToMany(PersonalVaultItem::class, 'personal_vault_item_tag', 'tag_id', 'item_id');
```

### 2. Delete folder moves items to root

Spec requires items survive folder deletion. Controller sets `folder_id = null` on items before deleting folder. Also moves child folders to root (`parent_id = null`).

### 3. Search only on plaintext columns

Per ADR-003: encrypted fields cannot be searched. Search queries `name` and `url` only using `LIKE`. The test `test_search_cannot_find_encrypted_fields` explicitly verifies this.

### 4. `last_accessed_at` updated on show()

The `show()` method updates the timestamp every time an item is viewed. The `recent()` endpoint sorts by this field descending, limit 20.

---

## Files Created / Modified

### Migrations

| File | Purpose |
|---|---|
| `2026_09_01_170000_create_personal_vault_folders_table.php` | Folders with self-referencing parent_id for nesting |
| `2026_09_01_170001_create_personal_vault_tags_table.php` | Tags table + item_tag pivot |
| `2026_09_01_170002_add_folder_and_access_tracking_to_personal_vault_items.php` | Adds folder_id FK and last_accessed_at timestamp |

### Models

| File | What changed |
|---|---|
| `PersonalVaultFolder.php` | Created — user/parent/children/items relationships |
| `PersonalVaultTag.php` | Created — user/items relationships |
| `PersonalVaultItem.php` | Modified — folder(), tags() relationships, fillable, casts |

### HTTP Layer

| File | Purpose |
|---|---|
| `CreateFolderRequest.php` | Validates name, parent_id (exists + user-scoped), icon, color |
| `UpdateFolderRequest.php` | Same with `sometimes` |
| `CreateTagRequest.php` | Validates name (unique per user), color |
| `UpdateTagRequest.php` | Same with `sometimes`, ignores current tag on unique check |
| `PersonalVaultFolderResource.php` | Tree structure with recursive children |
| `PersonalVaultTagResource.php` | Tag with items_count |
| `PersonalVaultItemResource.php` | Modified — added folder_id, tags, last_accessed_at |
| `PersonalVaultFolderPolicy.php` | Owner-only access |
| `PersonalVaultTagPolicy.php` | Owner-only access |
| `PersonalVaultFolderController.php` | 5 CRUD methods, tree listing, delete moves items to root |
| `PersonalVaultTagController.php` | 4 CRUD methods |
| `PersonalVaultItemController.php` | Modified — 7 new methods (toggleFavorite, archive, restore, recent, favorites, archived, search), tag support, last_accessed_at on show() |

### Routes

| File | What changed |
|---|---|
| `routes/api.php` | 22 vault routes (items CRUD + organization + folders + tags + search) |

---

## All New Endpoints

| Method | Endpoint | Status | Description |
|---|---|---|---|
| GET | `/api/v1/vault/folders` | 200 | List folders as tree |
| POST | `/api/v1/vault/folders` | 201 | Create folder |
| GET | `/api/v1/vault/folders/{folder}` | 200 | Get folder with items |
| PUT | `/api/v1/vault/folders/{folder}` | 200 | Update folder |
| DELETE | `/api/v1/vault/folders/{folder}` | 204 | Delete folder (items → root) |
| GET | `/api/v1/vault/tags` | 200 | List all tags |
| POST | `/api/v1/vault/tags` | 201 | Create tag |
| PUT | `/api/v1/vault/tags/{tag}` | 200 | Update tag |
| DELETE | `/api/v1/vault/tags/{tag}` | 204 | Delete tag |
| POST | `/api/v1/vault/items/{item}/favorite` | 200 | Toggle favorite |
| POST | `/api/v1/vault/items/{item}/archive` | 204 | Archive item |
| POST | `/api/v1/vault/items/{item}/restore` | 204 | Restore item |
| GET | `/api/v1/vault/items/recent` | 200 | Recently accessed |
| GET | `/api/v1/vault/items/favorites` | 200 | Favorites (paginated) |
| GET | `/api/v1/vault/items/archived` | 200 | Archived (paginated) |
| GET | `/api/v1/vault/search?q=` | 200 | Search by name/URL |

---

## Bugs Found and Fixed During Implementation

### 1. Tag pivot using wrong foreign key

**Cause:** `belongsToMany` default key naming uses `personal_vault_item_id` but pivot table uses `item_id`.

**Fix:** Specified explicit keys in both `PersonalVaultItem::tags()` and `PersonalVaultTag::items()`.

### 2. `archived_at` not persisting on update

**Cause:** `archived_at` was not in the `$fillable` array, so mass assignment via `update()` silently skipped it.

**Fix:** Added `archived_at` to the `#[Fillable]` attribute.

### 3. PHPStan: Rule objects in return type

**Cause:** `rules()` method return type was `array<string, array<int, string>>` but `Rule::exists()` and `Rule::unique()` return objects.

**Fix:** Changed return type to `array<string, list<mixed>>`.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 91 tests, 280 assertions (19 new + 72 from Modules 02-05) |

---

## Key Takeaways

1. **Explicit pivot keys** — when pivot table column names don't match Laravel's default naming convention, always specify the foreign key and related key explicitly in `belongsToMany()`.

2. **Fillable must include all mass-assignable fields** — `archived_at` was missing from `$fillable`, causing silent data loss on `update()`. Always check fillable when adding new columns that will be set via mass assignment.

3. **Search only plaintext columns** — encrypted fields cannot be searched with `LIKE`. The search endpoint explicitly queries only `name` and `url` (both plaintext per ADR-003).

4. **Folder deletion strategy** — move items to root rather than cascading deletes. This preserves user data even when they reorganize their folder structure.

---

## Build Order — Files Created A to Z

```
 1. Migration          → 2026_09_01_170000_create_personal_vault_folders_table.php
 2. Migration          → 2026_09_01_170001_create_personal_vault_tags_table.php
 3. Migration          → 2026_09_01_170002_add_folder_and_access_tracking_to_personal_vault_items.php
    → php artisan migrate:fresh
 4. Model              → PersonalVaultFolder.php
 5. Model              → PersonalVaultTag.php
 6. Model              → PersonalVaultItem.php (modified)
 7. Factory            → PersonalVaultFolderFactory.php
 8. Factory            → PersonalVaultTagFactory.php
 9. Form Request       → CreateFolderRequest.php
10. Form Request       → UpdateFolderRequest.php
11. Form Request       → CreateTagRequest.php
12. Form Request       → UpdateTagRequest.php
13. API Resource       → PersonalVaultFolderResource.php
14. API Resource       → PersonalVaultTagResource.php
15. API Resource       → PersonalVaultItemResource.php (modified)
16. Policy             → PersonalVaultFolderPolicy.php
17. Policy             → PersonalVaultTagPolicy.php
18. Controller         → PersonalVaultFolderController.php
19. Controller         → PersonalVaultTagController.php
20. Controller         → PersonalVaultItemController.php (modified — 7 new methods)
21. Routes             → routes/api.php (modified — 22 vault routes)
22. Test               → VaultOrganizationTest.php (19 tests)
23. Verification       → pint → phpstan → php artisan test
```
