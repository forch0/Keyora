# Module 29 — Soft Deletes Consistency

| Field | Value |
|---|---|
| **Module** | 29 |
| **Name** | Soft Deletes Consistency |
| **Dependencies** | All prior modules |
| **Status** | Not Started |

---

## Objective

Add soft deletes to all models that represent user-managed content. Currently `VaultItem`, `SecureFile`, `SecureNote`, and `Tenant` have soft deletes. Other content models (`PersonalVaultItem`, `Team`, `AccessGrant`, `AccessRequest`, `SecureLink`, `SecurityAlert`, `UserDevice`) do not. This module adds soft deletes where appropriate and ensures queries exclude trashed items by default.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| SD-01 | Soft deletes on all content models | P1 |
| SD-02 | Restore deleted items | P1 |
| SD-03 | Permanent deletion (force delete) | P1 |
| SD-04 | Trash listing endpoint | P2 |

---

## Tasks

### 29.1 Audit & Categorize Models

Models that SHOULD have soft deletes (user-managed content):

| Model | Currently Has | Action |
|---|---|---|
| `VaultItem` | Yes | No change |
| `SecureFile` | Yes | No change |
| `SecureNote` | Yes | No change |
| `Tenant` | Yes | No change |
| `PersonalVaultItem` | No | Add |
| `Team` | No | Add |
| `AccessGrant` | No | Add |
| `AccessRequest` | No | Add |
| `SecureLink` | No | Add |
| `SecurityAlert` | No | Add |
| `UserDevice` | No | Add |

Models that should NOT have soft deletes (system/audit data):

| Model | Reason |
|---|---|
| `ActivityLog` | Append-only audit trail — never deleted |
| `User` | Use `status=left` instead (Module 22) |
| `TenantInvitation` | Transient — deleted on acceptance/expiry |
| `ResourceView` | Analytics data — append-only |
| `SecureLinkAccess` | Access tracking — append-only |
| Folders/Tags | Reference data — cascade delete is fine |

### 29.2 Add Soft Deletes

- [ ] Create a single migration adding `deleted_at` to all models that need it
- [ ] Add `SoftDeletes` trait to each model
- [ ] Add `deleted_at` to `$casts` as `datetime`
- [ ] Ensure existing queries still work (Eloquent excludes trashed by default)

### 29.3 Restore Endpoints

- [ ] Add restore endpoints for models that support soft delete:

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/vault/items/{item}/restore` | Restore a trashed vault item |
| `POST` | `/api/v1/personal-vault/items/{item}/restore` | Restore a trashed personal vault item |
| `POST` | `/api/v1/teams/{team}/restore` | Restore a trashed team |
| `POST` | `/api/v1/files/{file}/restore` | Restore a trashed file |
| `POST` | `/api/v1/notes/{note}/restore` | Restore a trashed note |
| `POST` | `/api/v1/secure-links/{link}/restore` | Restore a trashed secure link |

### 29.4 Force Delete Endpoints

- [ ] Add permanent deletion endpoints (admin/owner only):

| Method | Endpoint | Description |
|---|---|---|
| `DELETE` | `/api/v1/vault/items/{item}/force` | Permanently delete vault item |
| `DELETE` | `/api/v1/personal-vault/items/{item}/force` | Permanently delete personal vault item |
| `DELETE` | `/api/v1/files/{file}/force` | Permanently delete file + storage |
| `DELETE` | `/api/v1/notes/{note}/force` | Permanently delete note |

### 29.5 Trash Listing

- [ ] Add trash listing endpoints:

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/personal-vault/trash` | List trashed personal vault items |
| `GET` | `/api/v1/vault/trash` | List trashed org vault items (admin) |
| `GET` | `/api/v1/files/trash` | List trashed files |
| `GET` | `/api/v1/notes/trash` | List trashed notes |

### 29.6 Force Delete Cleanup

- [ ] When force-deleting a `SecureFile`, also delete the physical file from storage
- [ ] When force-deleting a `Team`, also soft-delete its vault items, files, and notes
- [ ] When force-deleting a `VaultItem`, also delete related access grants and access requests
- [ ] Log `resource.force_deleted` activity for audit trail

### 29.7 Empty Trash Endpoint

- [ ] `DELETE /api/v1/personal-vault/trash` — permanently delete all trashed personal vault items
- [ ] `DELETE /api/v1/files/trash` — permanently delete all trashed files
- [ ] Requires re-authentication

---

## Acceptance Criteria

- [ ] Soft deletes added to PersonalVaultItem, Team, AccessGrant, AccessRequest, SecureLink, SecurityAlert, UserDevice
- [ ] Deleting a resource sets `deleted_at` instead of removing the row
- [ ] Trashed items are excluded from normal queries
- [ ] Restore endpoint brings items back
- [ ] Force delete permanently removes the record
- [ ] Force delete on SecureFile removes the physical file
- [ ] Trash listing endpoints return only trashed items
- [ ] Empty trash endpoint permanently deletes all trashed items
- [ ] Activity logs are never soft-deleted
- [ ] Existing tests still pass

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_personal_vault_item_soft_deleted` | Delete sets deleted_at, row still exists |
| `test_trashed_items_excluded_from_list` | Normal queries exclude trashed |
| `test_restore_personal_vault_item` | Restore brings item back |
| `test_force_delete_personal_vault_item` | Force delete removes row permanently |
| `test_team_soft_deleted` | Team soft delete works |
| `test_restore_team` | Team restore works |
| `test_secure_file_force_delete_removes_file` | Physical file deleted on force delete |
| `test_trash_listing_returns_only_trashed` | Trash endpoint returns trashed only |
| `test_empty_trash_deletes_all` | Empty trash removes all trashed items |
| `test_activity_log_not_soft_deletable` | ActivityLog has no deleted_at column |
| `test_force_delete_revokes_access_grants` | Force deleting item removes its grants |
| `test_force_delete_requires_reauth` | Force delete requires re-authentication |

---

## What This Module Does NOT Include

- Trash retention policies / auto-purge (post-MVP)
- Trash size limits (post-MVP)
- Per-tenant trash management (post-MVP)
