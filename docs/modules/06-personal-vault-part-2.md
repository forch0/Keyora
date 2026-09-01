# Module 06 — Personal Vault: Part 2 (Organization)

| Field | Value |
|---|---|
| **Module** | 06 |
| **Name** | Personal Vault — Organization (Folders, Tags, Favorites, Search) |
| **Dependencies** | Module 05 |
| **Status** | ✅ Complete |

---

## Objective

Add organization features to the personal vault: nested folders, tags, favorites toggle, archive/restore, search, and recently accessed/added views.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| PV-08 | Upload files (max 10 MB) | P1 → Deferred to Module 11 |
| PV-09 | Organize items into nested folders | P1 |
| PV-10 | Tag items with custom labels | P1 |
| PV-11 | Mark items as favorites | P1 |
| PV-12 | Search across all vault items | P0 |
| PV-13 | View recently accessed and recently added items | P1 |
| PV-14 | Archive and restore items | P2 |
| PS-09 | Favorite, tag, and folder-organize secrets | P1 |

---

## Tasks

### 6.1 Folders Table & Model

- [ ] Create `personal_vault_folders` migration:

```
personal_vault_folders
  id              -- bigIncrements
  user_id         -- foreignId (constrained, cascadeOnDelete)
  name            -- string
  parent_id       -- foreignId, nullable (self-referencing for nesting)
  icon            -- string, nullable
  color           -- string, nullable
  sort_order      -- integer, default 0
  created_at
  updated_at

  index(user_id)
  index(user_id, parent_id)
```

- [ ] Create `PersonalVaultFolder` model:
  - `$fillable`: `name`, `parent_id`, `icon`, `color`, `sort_order`
  - Relationship: `user()` → `belongsTo(User::class)`
  - Relationship: `parent()` → `belongsTo(PersonalVaultFolder::class, 'parent_id')`
  - Relationship: `children()` → `hasMany(PersonalVaultFolder::class, 'parent_id')`
  - Relationship: `items()` → `hasMany(PersonalVaultItem::class, 'folder_id')`

### 6.2 Add `folder_id` to Vault Items

- [ ] Add `folder_id` column to `personal_vault_items`:
  - `foreignId('folder_id')->nullable()->constrained('personal_vault_folders')->nullOnDelete()`
  - Add to index: `index(user_id, folder_id)`

### 6.3 Tags Table & Model

- [ ] Create `personal_vault_tags` migration:

```
personal_vault_tags
  id              -- bigIncrements
  user_id         -- foreignId (constrained, cascadeOnDelete)
  name            -- string
  color           -- string, nullable
  created_at
  updated_at

  unique(user_id, name)
  index(user_id)
```

- [ ] Create `personal_vault_tag` pivot:

```
personal_vault_item_tag
  item_id         -- foreignId
  tag_id          -- foreignId
  created_at

  primary(item_id, tag_id)
```

- [ ] Create `PersonalVaultTag` model:
  - `$fillable`: `name`, `color`
  - Relationship: `user()` → `belongsTo(User::class)`
  - Relationship: `items()` → `belongsToMany(PersonalVaultItem::class)`

### 6.4 Add Tags Relationship to Vault Item

- [ ] Add `tags()` → `belongsToMany(PersonalVaultTag::class, 'personal_vault_item_tag')` to `PersonalVaultItem` model

### 6.5 Recently Accessed Tracking

- [ ] Add `last_accessed_at` timestamp (nullable) to `personal_vault_items`
- [ ] Update this timestamp when user views an item via `GET /vault/items/{item}`
- [ ] Add index: `index(user_id, last_accessed_at)`

### 6.6 API Endpoints — Folders

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/vault/folders` | List folders (tree structure) |
| `POST` | `/api/v1/vault/folders` | Create folder |
| `GET` | `/api/v1/vault/folders/{folder}` | Get folder with items |
| `PUT` | `/api/v1/vault/folders/{folder}` | Update folder |
| `DELETE` | `/api/v1/vault/folders/{folder}` | Delete folder (items moved to root) |

### 6.7 API Endpoints — Tags

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/vault/tags` | List all tags |
| `POST` | `/api/v1/vault/tags` | Create tag |
| `PUT` | `/api/v1/vault/tags/{tag}` | Update tag |
| `DELETE` | `/api/v1/vault/tags/{tag}` | Delete tag (removes from all items) |

### 6.8 API Endpoints — Vault Items (Extended)

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/vault/items/{item}/favorite` | Toggle favorite |
| `POST` | `/api/v1/vault/items/{item}/archive` | Archive item |
| `POST` | `/api/v1/vault/items/{item}/restore` | Restore archived item |
| `GET` | `/api/v1/vault/items/recent` | Recently accessed items |
| `GET` | `/api/v1/vault/items/favorites` | Favorite items |
| `GET` | `/api/v1/vault/items/archived` | Archived items |
| `GET` | `/api/v1/vault/search?q={query}` | Search vault items |

### 6.9 Controllers

- [ ] `PersonalVaultFolderController.php` — CRUD for folders
- [ ] `PersonalVaultTagController.php` — CRUD for tags
- [ ] Extend `PersonalVaultItemController.php`:
  - `toggleFavorite()` — toggle `favorite` boolean
  - `archive()` — set `archived_at` timestamp
  - `restore()` — clear `archived_at`
  - `recent()` — list items sorted by `last_accessed_at` desc, limit 20
  - `favorites()` — list items where `favorite = true`
  - `archived()` — list items where `archived_at IS NOT NULL`
  - `search()` — search by `name` and `url` (plaintext columns) using `LIKE`

### 6.10 Form Requests

- [ ] `CreateFolderRequest`: `name` (required, string, max:100), `parent_id` (nullable, exists), `icon` (nullable, string, max:50), `color` (nullable, string, max:20)
- [ ] `CreateTagRequest`: `name` (required, string, max:50, unique per user), `color` (nullable, string, max:20)

### 6.11 API Resources

- [ ] `PersonalVaultFolderResource.php` — `id`, `name`, `parent_id`, `icon`, `color`, `sort_order`, `children` (recursive), `items_count`
- [ ] `PersonalVaultTagResource.php` — `id`, `name`, `color`, `items_count`

### 6.12 Policies

- [ ] `PersonalVaultFolderPolicy.php` — owner check (`user_id`)
- [ ] `PersonalVaultTagPolicy.php` — owner check (`user_id`)

### 6.13 Search Implementation

- [ ] Search queries `name` and `url` columns (plaintext) using `WHERE name LIKE ? OR url LIKE ?`
- [ ] Cannot search encrypted columns
- [ ] Return matching items with highlighting (optional)
- [ ] Support type filter in search: `?q=github&type=password`
- [ ] Limit results to 50

---

## Acceptance Criteria

- [ ] User can create a folder
- [ ] User can create nested folders (folder with `parent_id`)
- [ ] User can list folders as a tree structure
- [ ] User can assign items to folders
- [ ] Deleting a folder moves its items to root (doesn't delete items)
- [ ] User can create tags
- [ ] User can assign multiple tags to an item
- [ ] User can filter items by tag
- [ ] Deleting a tag removes it from all items
- [ ] User can toggle favorite on an item
- [ ] User can view favorites list
- [ ] User can archive an item (soft hide from main list)
- [ ] User can restore an archived item
- [ ] User can view archived items separately
- [ ] User can search vault items by name and URL
- [ ] User can view recently accessed items
- [ ] Viewing an item updates `last_accessed_at`
- [ ] All organization features are scoped to the authenticated user only

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_user_can_create_folder` | POST creates folder |
| `test_user_can_create_nested_folder` | Folder with parent_id |
| `test_user_can_list_folders_as_tree` | GET returns hierarchical structure |
| `test_deleting_folder_moves_items_to_root` | Items survive folder deletion |
| `test_user_can_create_tag` | POST creates tag |
| `test_user_can_assign_tags_to_item` | Tags attached to item |
| `test_user_can_filter_by_tag` | GET ?tag=X returns tagged items |
| `test_deleting_tag_removes_from_items` | Tag deletion cleans pivot |
| `test_user_can_toggle_favorite` | POST toggles favorite boolean |
| `test_user_can_view_favorites` | GET returns only favorites |
| `test_user_can_archive_item` | POST sets archived_at |
| `test_user_can_restore_item` | POST clears archived_at |
| `test_user_can_search_by_name` | GET ?q= returns matching items |
| `test_user_can_search_by_url` | GET ?q= returns matching URL items |
| `test_search_cannot_find_encrypted_fields` | Searching for password value returns nothing |
| `test_recently_accessed_updates_timestamp` | Viewing item updates last_accessed_at |
| `test_user_can_view_recent_items` | GET /recent returns sorted by last_accessed_at |
| `test_cannot_access_other_users_folders` | Other user's folder → 404 |
| `test_cannot_access_other_users_tags` | Other user's tag → 404 |

---

## What This Module Does NOT Include

- Company/team vault items (Module 07)
- Sharing (Module 08, 09)
- Global search across all resource types (Module 19)
- File uploads (Module 11)
