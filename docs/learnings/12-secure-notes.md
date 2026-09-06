# 13 — How Module 13 (Secure Notes) Was Built

| Field | Value |
|---|---|
| **Module** | 13 — Secure Notes |
| **Date** | 2026-09-02 |
| **Spec** | `docs/modules/13-secure-notes.md` |

---

## Goal

Build secure notes — rich-text notes that can be personal, team-scoped, or company-scoped. Notes support sharing with view/edit permissions, organization via folders/tags/search, and content is encrypted at rest.

---

## Decisions Made Before Writing Code

### 1. Content encrypted, title plaintext

Note `content` is encrypted using the `Encryptable` trait (AES-256-CBC via Laravel's Crypt facade). Note `title` is stored in plaintext so it can be searched. This is the right tradeoff — titles are metadata, content is sensitive.

### 2. No BelongsToTenant trait on SecureNote

Personal notes have `tenant_id = null`. The `BelongsToTenant` trait throws when no tenant context is set, which would break personal notes. Instead, the controller manually scopes queries based on `user_id`, `team_id`, `tenant_id`, and access grants.

### 3. Reuse Module 09 actions for sharing

`NoteAccessController` delegates to `GrantAccessAction`, `UpdateAccessAction`, and `RevokeAccessAction` — same pattern as `FileAccessController` in Module 12. The polymorphic `AccessGrant` supports `SecureNote::class` as `grantable_type` with zero schema changes.

### 4. Three note scopes

- **Personal**: `tenant_id = null`, `team_id = null` — only the creator can see it
- **Team**: `team_id` set, `tenant_id` inherited — team members can see it
- **Org-wide**: `tenant_id` set, `team_id = null` — all tenant members can see it

### 5. Search only on title

Search queries `WHERE title LIKE '%query%'`. Since content is encrypted, searching content text returns nothing — verified by `test_search_cannot_find_encrypted_content`.

---

## Files Created

| File | Purpose |
|---|---|
| `app/Models/SecureNote.php` | Note model (Encryptable, SoftDeletes, no BelongsToTenant) |
| `app/Models/NoteFolder.php` | Folder model (self-referencing parent/children) |
| `app/Models/NoteTag.php` | Tag model (tenant or user scoped) |
| `app/Actions/CreateNoteAction.php` | Create note with scope resolution |
| `app/Actions/UpdateNoteAction.php` | Update note metadata and content |
| `app/Http/Requests/Notes/CreateNoteRequest.php` | Validate creation |
| `app/Http/Requests/Notes/UpdateNoteRequest.php` | Validate update |
| `app/Http/Requests/Notes/CreateNoteFolderRequest.php` | Validate folder creation |
| `app/Http/Requests/Notes/UpdateNoteFolderRequest.php` | Validate folder update |
| `app/Http/Resources/V1/SecureNoteResource.php` | Note API resource (decrypted content) |
| `app/Http/Resources/V1/NoteFolderResource.php` | Folder API resource |
| `app/Policies/SecureNotePolicy.php` | view/update/delete authorization |
| `app/Http/Controllers/Api/V1/SecureNoteController.php` | Notes CRUD + togglePin + search |
| `app/Http/Controllers/Api/V1/NoteAccessController.php` | Sharing (reuses Module 09 actions) |
| `app/Http/Controllers/Api/V1/NoteFolderController.php` | Folder CRUD |
| `database/factories/SecureNoteFactory.php` | Factory |
| `database/factories/NoteFolderFactory.php` | Factory |
| `database/factories/NoteTagFactory.php` | Factory |
| `tests/Feature/Api/V1/Notes/SecureNoteTest.php` | 16 feature tests |

---

## New Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/notes` | List notes (personal + shared + team) |
| POST | `/api/v1/notes` | Create note |
| GET | `/api/v1/notes/search` | Search by title |
| GET | `/api/v1/notes/{note}` | Get note (decrypted) |
| PUT | `/api/v1/notes/{note}` | Update note |
| DELETE | `/api/v1/notes/{note}` | Delete note |
| POST | `/api/v1/notes/{note}/pin` | Toggle pin |
| GET | `/api/v1/notes/{note}/access` | List access grants |
| POST | `/api/v1/notes/{note}/access` | Grant access |
| PUT | `/api/v1/notes/{note}/access/{grant}` | Update access |
| DELETE | `/api/v1/notes/{note}/access/{grant}` | Revoke access |
| GET | `/api/v1/notes/folders` | List folders |
| POST | `/api/v1/notes/folders` | Create folder |
| PUT | `/api/v1/notes/folders/{folder}` | Update folder |
| DELETE | `/api/v1/notes/folders/{folder}` | Delete folder (notes moved to root) |

---

## Bugs Found and Fixed During Implementation

### 1. Migration order — pivot table referencing non-existent table

**Cause:** The `note_tags` migration (timestamp 210001) created the `secure_note_tag` pivot with a foreign key to `secure_notes`, but `secure_notes` was created by migration 210002 which ran later.

**Fix:** Renamed the note_tags migration from `210001` to `210003` so it runs after `secure_notes` is created.

### 2. `whatDoesUserHaveAccessTo` returns AccessGrant collection, not IDs

**Cause:** The controller called `whatDoesUserHaveAccessTo($user, SecureNote::class)` expecting IDs, but the method signature is `whatDoesUserHaveAccessTo(User $user): Collection` returning AccessGrant objects.

**Fix:** Filter the collection by `grantable_type === SecureNote::class` and pluck `grantable_id`.

### 3. Tags nested under `data` in resource response

**Cause:** Test checked `$response->json('tags')` but the JsonResource wraps everything in a `data` key.

**Fix:** Changed to `$response->json('data.tags')`.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 190 tests, 527 assertions (16 new + 174 from Modules 02-12) |

---

## Key Takeaways

1. **Encrypt content, not title** — encrypting the title would make search impossible. The tradeoff is that titles are visible in DB but content is protected. This is the standard approach for encrypted note systems.

2. **Conditional tenant scoping** — when a model can have `tenant_id = null` (personal notes), the `BelongsToTenant` trait's global scope doesn't work because it throws without a tenant context. Manual scoping in the controller is the right approach.

3. **Polymorphic access grants scale** — the same `AccessGrant` infrastructure from Module 08 now supports vault items, secure files, and secure notes with zero schema changes. This validates the original polymorphic design.

4. **Migration ordering matters** — pivot tables must be created after both parent tables exist. Always check migration timestamps when creating pivot tables that reference tables in the same batch.
