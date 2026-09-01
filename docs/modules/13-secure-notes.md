# Module 13 — Secure Notes

| Field | Value |
|---|---|
| **Module** | 13 |
| **Name** | Secure Notes |
| **Dependencies** | Module 05, Module 08, Module 09 |
| **Status** | Not Started |

---

## Objective

Build secure notes — rich-text notes that can be personal, team-scoped, or company-scoped. Notes support sharing with view/edit permissions, temporary access, and organization via folders/tags/search.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| SN-01 | Create personal, company, and team notes | P1 |
| SN-02 | Rich-text editing (bold, italic, lists, code blocks) | P1 |
| SN-04 | Notes can be shared with view-only or edit permissions | P1 |
| SN-05 | Temporary note access with expiration | P1 → Module 14 |
| SN-07 | Note search, folders, and tags | P1 |

---

## Tasks

### 13.1 Secure Notes Table & Model

- [ ] Create `secure_notes` migration:

```
secure_notes
  id              -- bigIncrements
  tenant_id       -- foreignId, nullable (null = personal note)
  team_id         -- foreignId, nullable (null = personal or org-wide)
  user_id         -- foreignId (constrained, cascadeOnDelete) — creator
  title           -- string
  content         -- longText (ENCRYPTED — rich-text HTML/Markdown)
  content_format  -- enum: 'markdown', 'html' (default 'markdown')
  folder_id       -- foreignId, nullable
  is_pinned       -- boolean, default false
  created_at
  updated_at
  deleted_at      -- soft deletes

  index(tenant_id, team_id)
  index(user_id)
  index(tenant_id, folder_id)
```

- [ ] Create `SecureNote` model:
  - `use Encryptable` trait — `$encryptable = ['content']`
  - `use SoftDeletes`
  - `use BelongsToTenant` trait (only when `tenant_id` is not null — conditional)
  - `$fillable`: `title`, `content`, `content_format`, `folder_id`, `is_pinned`, `team_id`, `tenant_id`
  - `$casts`: `is_pinned` → `boolean`
  - Relationships: `tenant()`, `team()`, `user()` (creator), `folder()`

### 13.2 Note Folders

- [ ] Create `note_folders` migration (same pattern as file folders):

```
note_folders
  id              -- bigIncrements
  tenant_id       -- foreignId, nullable
  team_id         -- foreignId, nullable
  user_id         -- foreignId, nullable (for personal note folders)
  name            -- string
  parent_id       -- foreignId, nullable (self-referencing)
  created_by      -- foreignId (users)
  created_at
  updated_at
```

### 13.3 Note Tags

- [ ] Create `secure_note_tag` pivot (same pattern as vault item tags)
- [ ] Create `note_tags` table (tenant-scoped or user-scoped depending on note type)

### 13.4 API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/notes` | List notes (personal + shared with me) |
| `POST` | `/api/v1/notes` | Create note |
| `GET` | `/api/v1/notes/{note}` | Get note (decrypts content) |
| `PUT` | `/api/v1/notes/{note}` | Update note |
| `DELETE` | `/api/v1/notes/{note}` | Delete note |
| `POST` | `/api/v1/notes/{note}/pin` | Toggle pin |
| `GET` | `/api/v1/notes/{note}/access` | List who has access |
| `POST` | `/api/v1/notes/{note}/access` | Grant access |
| `PUT` | `/api/v1/notes/{note}/access/{grant}` | Update access |
| `DELETE` | `/api/v1/notes/{note}/access/{grant}` | Revoke access |
| `GET` | `/api/v1/notes/folders` | List note folders |
| `POST` | `/api/v1/notes/folders` | Create folder |
| `PUT` | `/api/v1/notes/folders/{folder}` | Update folder |
| `DELETE` | `/api/v1/notes/folders/{folder}` | Delete folder |
| `GET` | `/api/v1/notes/search?q={query}` | Search notes by title |

### 13.5 Controller

- [ ] `SecureNoteController.php`:
  - `index()` — list notes: personal notes (user_id) + shared notes (via access grants) + team notes (if team member)
  - `store()` — create note (personal, team, or org-wide based on `team_id`/`tenant_id`)
  - `show()` — return note with decrypted content
  - `update()` — update title, content, folder
  - `destroy()` — soft delete
  - `togglePin()` — toggle `is_pinned`
  - `search()` — search by `title` (plaintext, not encrypted content)

### 13.6 Actions

- [ ] `CreateNoteAction.php` — create note, set scope (personal/team/org), encrypt content via trait
- [ ] `UpdateNoteAction.php` — update note, encrypt content via trait

### 13.7 Form Requests

- [ ] `CreateNoteRequest`:
  - `title`: required, string, max:255
  - `content`: required, string, max:50000
  - `content_format`: nullable, in:markdown,html
  - `team_id`: nullable, exists:teams,id
  - `folder_id`: nullable, exists:note_folders,id
  - `is_pinned`: nullable, boolean
- [ ] `UpdateNoteRequest`: same, all `sometimes`

### 13.8 API Resource

- [ ] `SecureNoteResource.php`:
  - `id`, `title`, `content` (decrypted), `content_format`, `is_pinned`, `folder`, `tags`, `team`, `created_by`, `created_at`, `updated_at`

### 13.9 Policy

- [ ] `SecureNotePolicy.php`:
  - `view()` — creator, team member, or `AccessResolver::can(user, Permission::View, note)`
  - `update()` — creator or `AccessResolver::can(user, Permission::Edit, note)`
  - `delete()` — creator or `AccessResolver::can(user, Permission::Manage, note)`

### 13.10 Routes

```php
Route::middleware(['auth:sanctum', 'tenant.resolve'])->prefix('v1/notes')->group(function () {
    Route::get('/', [SecureNoteController::class, 'index']);
    Route::post('/', [SecureNoteController::class, 'store']);
    Route::get('/search', [SecureNoteController::class, 'search']);
    Route::get('/{note}', [SecureNoteController::class, 'show']);
    Route::put('/{note}', [SecureNoteController::class, 'update']);
    Route::delete('/{note}', [SecureNoteController::class, 'destroy']);
    Route::post('/{note}/pin', [SecureNoteController::class, 'togglePin']);

    Route::get('/{note}/access', [NoteAccessController::class, 'index']);
    Route::post('/{note}/access', [NoteAccessController::class, 'store']);
    Route::put('/{note}/access/{grant}', [NoteAccessController::class, 'update']);
    Route::delete('/{note}/access/{grant}', [NoteAccessController::class, 'destroy']);

    Route::get('/folders', [NoteFolderController::class, 'index']);
    Route::post('/folders', [NoteFolderController::class, 'store']);
    Route::put('/folders/{folder}', [NoteFolderController::class, 'update']);
    Route::delete('/folders/{folder}', [NoteFolderController::class, 'destroy']);
});
```

---

## Acceptance Criteria

- [ ] User can create a personal note (no tenant_id/team_id)
- [ ] User can create a team note (team_id set)
- [ ] User can create an org-wide note (tenant_id set, team_id null)
- [ ] Note content is encrypted at rest
- [ ] Note title is stored in plaintext (searchable)
- [ ] User can list their notes (personal + shared + team)
- [ ] User can view a note with decrypted content
- [ ] User can update a note
- [ ] User can delete a note (soft delete)
- [ ] User can pin/unpin a note
- [ ] User can share a note with view or edit permission
- [ ] User can search notes by title
- [ ] Notes can be organized into folders (with nesting)
- [ ] Notes can be tagged
- [ ] Non-permitted user cannot view a note → `403`

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_user_can_create_personal_note` | POST creates note with no tenant/team |
| `test_user_can_create_team_note` | POST creates note with team_id |
| `test_note_content_is_encrypted` | DB content != plaintext input |
| `test_note_title_is_not_encrypted` | DB title == plaintext input |
| `test_user_can_list_notes` | GET returns personal + shared notes |
| `test_user_can_view_note` | GET returns note with decrypted content |
| `test_user_can_update_note` | PUT updates title and content |
| `test_user_can_delete_note` | DELETE soft-deletes |
| `test_user_can_pin_note` | POST /pin toggles is_pinned |
| `test_user_can_share_note` | POST /access creates grant |
| `test_shared_user_can_view_note` | Grantee can view |
| `test_non_shared_user_cannot_view` | No grant → 403 |
| `test_user_can_search_notes` | GET ?q= returns matching titles |
| `test_search_cannot_find_encrypted_content` | Searching content text returns nothing |
| `test_notes_can_be_organized_into_folders` | Folder assignment works |
| `test_notes_can_be_tagged` | Tags attached |

---

## What This Module Does NOT Include

- Note attachments (post-MVP)
- Note version history (post-MVP)
- Temporary note access with expiration (Module 14)
- Activity logging (Module 20)
