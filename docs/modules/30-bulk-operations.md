# Module 30 — Bulk Operations

| Field | Value |
|---|---|
| **Module** | 30 |
| **Name** | Bulk Operations |
| **Dependencies** | Module 05, Module 08, Module 11, Module 13 |
| **Status** | Not Started |

---

## Objective

Add bulk operation endpoints for common multi-resource actions. Currently every endpoint operates on a single resource. This module adds batch create, update, delete, move, share, and tag operations to improve efficiency for users managing many items.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| BO-01 | Bulk create vault items | P1 |
| BO-02 | Bulk delete vault items | P1 |
| BO-03 | Bulk move items to folder | P1 |
| BO-04 | Bulk tag/untag items | P1 |
| BO-05 | Bulk share items | P1 |
| BO-06 | Bulk archive/restore items | P2 |
| BO-07 | Bulk import (CSV/JSON) | P2 |

---

## Tasks

### 30.1 Bulk Actions Service

- [ ] Create `app/Services/BulkOperationService.php`:
  - `bulkDelete(string $modelType, array $ids, User $actor): int` — returns count deleted
  - `bulkMove(string $modelType, array $ids, ?int $folderId, User $actor): int`
  - `bulkArchive(string $modelType, array $ids, User $actor): int`
  - `bulkRestore(string $modelType, array $ids, User $actor): int`
  - `bulkTag(string $modelType, array $ids, array $tagIds, User $actor): int`
  - `bulkShare(string $modelType, array $ids, int $subjectId, string $permission, User $actor): int`
  - All methods validate ownership/access per item before acting
  - All methods run in a database transaction
  - All methods log a single `resource.bulk_*` activity entry with item count

### 30.2 Bulk Delete

- [ ] `POST /api/v1/personal-vault/items/bulk-delete`

```json
{
  "ids": [1, 2, 3, 4, 5]
}
```

Response:
```json
{
  "data": {
    "deleted": 5,
    "failed": 0
  }
}
```

- [ ] Apply to: personal vault items, org vault items, secure files, secure notes
- [ ] Validates that the actor owns or has delete permission on each item
- [ ] Items the actor can't access are counted as `failed` (not errored)

### 30.3 Bulk Move to Folder

- [ ] `POST /api/v1/personal-vault/items/bulk-move`

```json
{
  "ids": [1, 2, 3],
  "folder_id": 10
}
```

- [ ] Validates folder ownership
- [ ] Applies to: personal vault items, org vault items, secure files, secure notes

### 30.4 Bulk Archive/Restore

- [ ] `POST /api/v1/personal-vault/items/bulk-archive`
- [ ] `POST /api/v1/personal-vault/items/bulk-restore`

```json
{
  "ids": [1, 2, 3]
}
```

### 30.5 Bulk Tag

- [ ] `POST /api/v1/personal-vault/items/bulk-tag`

```json
{
  "ids": [1, 2, 3],
  "tag_ids": [5, 6],
  "action": "attach"
}
```

- `action`: `attach` (add tags) or `detach` (remove tags) or `sync` (replace tags)
- [ ] Validates tag ownership

### 30.6 Bulk Share

- [ ] `POST /api/v1/vault/items/bulk-share`

```json
{
  "ids": [1, 2, 3],
  "subject_type": "App\\Models\\User",
  "subject_id": 10,
  "permission": "view",
  "expires_at": "2026-12-01T00:00:00Z"
}
```

- [ ] Validates share permission on each item
- [ ] Creates access grants for each item
- [ ] Items without share permission are counted as `failed`
- [ ] Requires re-authentication

### 30.7 Bulk Create (Import)

- [ ] `POST /api/v1/personal-vault/items/bulk-create`

```json
{
  "items": [
    { "name": "Gmail", "type": "password", "username": "me@gmail.com", "password": "secret" },
    { "name": "AWS", "type": "api_key", "username": "AKIA...", "password": "..." }
  ]
}
```

- [ ] Maximum 50 items per request
- [ ] Each item validated individually
- [ ] Valid items created, invalid items returned with errors
- [ ] Runs in a transaction — all or nothing

```json
{
  "data": {
    "created": 48,
    "errors": [
      { "index": 2, "field": "type", "message": "Invalid type" },
      { "index": 49, "field": "name", "message": "Name is required" }
    ]
  }
}
```

### 30.8 Form Requests

- [ ] `BulkDeleteRequest`: `ids` (required, array, max:100), `ids.*` (integer)
- [ ] `BulkMoveRequest`: `ids` (required, array, max:100), `folder_id` (nullable, integer)
- [ ] `BulkArchiveRequest`: `ids` (required, array, max:100)
- [ ] `BulkTagRequest`: `ids`, `tag_ids`, `action` (in: attach, detach, sync)
- [ ] `BulkShareRequest`: `ids`, `subject_type`, `subject_id`, `permission`, `expires_at` (nullable)
- [ ] `BulkCreateRequest`: `items` (required, array, max:50), with per-item validation

### 30.9 Controller

- [ ] Create `app/Http/Controllers/Api/V1/BulkOperationController.php`:
  - Thin controller — delegates to `BulkOperationService`
  - Each method: validate, authorize, delegate, format response

---

## Acceptance Criteria

- [ ] Bulk delete works on personal vault items, org vault items, files, notes
- [ ] Bulk move moves items to specified folder
- [ ] Bulk archive/restore sets/clears archived_at
- [ ] Bulk tag supports attach, detach, and sync
- [ ] Bulk share creates access grants for multiple items
- [ ] Bulk create imports up to 50 items at once
- [ ] All bulk operations run in transactions
- [ ] Per-item authorization is enforced (failed items counted, not errored)
- [ ] Bulk operations log activity
- [ ] Bulk share requires re-authentication
- [ ] Maximum limits enforced (100 for operations, 50 for create)

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_bulk_delete_personal_vault_items` | 5 items deleted, count returned |
| `test_bulk_delete_skips_unauthorized` | Items without permission counted as failed |
| `test_bulk_move_to_folder` | Items moved to new folder |
| `test_bulk_move_validates_folder_ownership` | Can't move to another user's folder |
| `test_bulk_archive_items` | Items archived |
| `test_bulk_restore_items` | Items restored |
| `test_bulk_tag_attach` | Tags attached to multiple items |
| `test_bulk_tag_detach` | Tags removed from multiple items |
| `test_bulk_tag_sync` | Tags replaced on multiple items |
| `test_bulk_share_creates_grants` | Access grants created for all items |
| `test_bulk_share_skips_unauthorized` | Items without share permission failed |
| `test_bulk_create_imports_items` | Multiple items created from array |
| `test_bulk_create_max_50` | 51 items → 422 |
| `test_bulk_create_validation_errors` | Invalid items returned with errors |
| `test_bulk_operation_logs_activity` | Single activity log entry with count |
| `test_bulk_share_requires_reauth` | Without re-auth → 423 |

---

## What This Module Does NOT Include

- Bulk CSV file upload import (post-MVP — parse CSV server-side)
- Bulk export to CSV/JSON (post-MVP)
- Bulk operations on teams/members (post-MVP)
- Undo for bulk operations (post-MVP)
