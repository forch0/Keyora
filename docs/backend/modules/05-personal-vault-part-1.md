# Module 05 — Personal Vault: Part 1 (Core CRUD & Encryption)

| Field | Value |
|---|---|
| **Module** | 05 |
| **Name** | Personal Vault — Core CRUD & Encryption |
| **Dependencies** | Module 01, Module 02 |
| **Status** | ✅ Complete |

---

## Objective

Build the personal vault — the user's private encrypted storage for credentials, API keys, server credentials, and database credentials. This module covers the core CRUD operations and the encryption layer. This is **not** tenant-scoped — personal vault items belong to the user directly (`user_id`).

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| PV-01 | Create, edit, delete password credentials | P0 |
| PV-02 | Store API keys with label, key value, metadata | P0 |
| PV-03 | Store server credentials | P1 |
| PV-04 | Store database credentials | P1 |
| PV-06 | Add custom secret fields to any item | P1 (PS-06) |
| PV-16 | All vault data encrypted at rest using AES-256 | P0 |

---

## Tasks

### 5.1 Encryptable Trait

- [ ] Create `app/Traits/Encryptable.php`:
  - Define `$encryptable` array property on models
  - Override `getAttribute()` — decrypt values from `$encryptable` list
  - Override `setAttribute()` — encrypt values in `$encryptable` list
  - Use `Crypt::encryptString()` and `Crypt::decryptString()`
  - Handle null values gracefully (don't encrypt nulls)

### 5.2 Personal Vault Items Table & Model

- [ ] Create `personal_vault_items` migration:

```
personal_vault_items
  id              -- bigIncrements
  user_id         -- foreignId (constrained, cascadeOnDelete)
  name            -- string (plaintext, searchable)
  type            -- enum: 'password', 'api_key', 'server', 'database'
  username        -- text, nullable (ENCRYPTED)
  password        -- text, nullable (ENCRYPTED)
  url             -- string, nullable (plaintext, searchable)
  notes           -- text, nullable (ENCRYPTED)
  metadata        -- json, nullable (plaintext metadata for search)
  custom_fields   -- json, nullable (ENCRYPTED — array of {key, value})
  favorite        -- boolean, default false
  archived_at     -- timestamp, nullable
  created_at
  updated_at

  index(user_id)
  index(user_id, type)
  index(user_id, favorite)
  index(user_id, archived_at)
```

- [ ] Create `PersonalVaultItem` model:
  - `use Encryptable` trait
  - `$encryptable = ['username', 'password', 'notes', 'custom_fields']`
  - `$fillable`: `name`, `type`, `username`, `password`, `url`, `notes`, `metadata`, `custom_fields`, `favorite`
  - `$casts`: `metadata` → `array`, `custom_fields` → `array`, `favorite` → `boolean`, `archived_at` → `datetime`
  - `SoftDeletes` (use `archived_at` as soft delete column OR use Laravel soft deletes — decide)
  - Relationship: `user()` → `belongsTo(User::class)`
  - Scope: `scopeOfType(Builder, string $type)`
  - Scope: `scopeFavorite(Builder)`

### 5.3 Type-Specific Metadata

Each `type` stores structured data in the `metadata` JSON column (plaintext, for display):

| Type | Metadata Fields |
|---|---|
| `password` | (none beyond username/password/url) |
| `api_key` | `provider`, `key_label` |
| `server` | `host`, `port`, `protocol` |
| `database` | `db_type`, `host`, `port`, `database_name` |

Sensitive values (actual keys, passwords) go in encrypted columns.

### 5.4 API Endpoints

| Method | Endpoint | Description | Auth |
|---|---|---|---|
| `GET` | `/api/v1/vault/items` | List user's vault items (paginated) | Yes |
| `POST` | `/api/v1/vault/items` | Create vault item | Yes |
| `GET` | `/api/v1/vault/items/{item}` | Get single vault item (decrypts sensitive fields) | Yes |
| `PUT` | `/api/v1/vault/items/{item}` | Update vault item | Yes |
| `DELETE` | `/api/v1/vault/items/{item}` | Delete vault item | Yes |

### 5.5 Controller

- [ ] `app/Http/Controllers/Api/V1/PersonalVaultItemController.php`
  - `index()` — list items for authenticated user (filter by type, favorite, archived)
  - `store()` — create new item, encrypt sensitive fields via model trait
  - `show()` — return item with decrypted sensitive fields
  - `update()` — update item
  - `destroy()` — delete item (soft delete or hard delete)

### 5.6 Actions

- [ ] `app/Actions/CreateVaultItemAction.php`
  - Accept validated attributes
  - Set `user_id` from authenticated user
  - Create model (encryption handled by trait)
  - Dispatch `VaultItemCreated` event
  - Return model
- [ ] `app/Actions/UpdateVaultItemAction.php`
  - Accept item, validated attributes
  - Update model
  - Dispatch `VaultItemUpdated` event
  - Return model
- [ ] `app/Actions/DeleteVaultItemAction.php`
  - Delete item
  - Dispatch `VaultItemDeleted` event

### 5.7 Form Requests

- [ ] `CreateItemRequest`:
  - `name`: required, string, max:255
  - `type`: required, in:password,api_key,server,database
  - `username`: nullable, string, max:255
  - `password`: nullable, string, max:1000
  - `url`: nullable, url, max:2048
  - `notes`: nullable, string, max:5000
  - `metadata`: nullable, array
  - `metadata.host`: nullable, string, max:255
  - `metadata.port`: nullable, integer, min:1, max:65535
  - `metadata.provider`: nullable, string, max:100
  - `metadata.db_type`: nullable, string, max:50
  - `metadata.database_name`: nullable, string, max:255
  - `custom_fields`: nullable, array
  - `custom_fields.*.key`: required, string, max:100
  - `custom_fields.*.value`: required, string, max:1000
- [ ] `UpdateItemRequest`: same rules but all fields `sometimes`

### 5.8 API Resource

- [ ] `app/Http/Resources/V1/PersonalVaultItemResource.php`
  - Fields: `id`, `name`, `type`, `username`, `password` (decrypted), `url`, `notes` (decrypted), `metadata`, `custom_fields` (decrypted), `favorite`, `archived_at`, `created_at`, `updated_at`
  - Sensitive fields only included when explicitly requested (e.g., `?include=secrets` query param) — or always include since this is the owner viewing their own data

### 5.9 Policy

- [ ] `app/Policies/PersonalVaultItemPolicy.php`
  - `view()`, `update()`, `delete()` — item `user_id` must match authenticated user's ID

### 5.10 Routes

```php
Route::middleware('auth:sanctum')->prefix('v1/vault')->group(function () {
    Route::apiResource('items', PersonalVaultItemController::class);
});
```

---

## Acceptance Criteria

- [ ] User can create a password credential item → `201` with decrypted response
- [ ] User can create an API key item with metadata
- [ ] User can create a server credential item with host/port metadata
- [ ] User can create a database credential item with db_type/host/port metadata
- [ ] User can add custom fields to any item
- [ ] User can list their vault items → paginated `200`
- [ ] User can view a single item with decrypted sensitive fields
- [ ] User can update an item
- [ ] User can delete an item
- [ ] User cannot access another user's vault items → `404` (not `403` — don't leak existence)
- [ ] Sensitive fields (`username`, `password`, `notes`, `custom_fields`) are encrypted in database
- [ ] `name` and `url` are stored in plaintext (searchable)
- [ ] `metadata` JSON is stored in plaintext (display data, no secrets)
- [ ] Encryption/decryption is automatic via `Encryptable` trait

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_user_can_create_password_item` | POST returns 201, item exists in DB |
| `test_user_can_create_api_key_item` | POST with metadata creates correctly |
| `test_user_can_create_server_credential` | POST with host/port metadata |
| `test_user_can_create_database_credential` | POST with db_type/host/port |
| `test_user_can_add_custom_fields` | Custom fields stored and retrieved |
| `test_user_can_list_vault_items` | GET returns paginated items |
| `test_user_can_view_single_item` | GET returns item with decrypted fields |
| `test_user_can_update_item` | PUT updates item |
| `test_user_can_delete_item` | DELETE removes item |
| `test_user_cannot_access_other_users_items` | Other user's item → 404 |
| `test_sensitive_fields_are_encrypted_in_db` | DB value != plaintext input |
| `test_name_and_url_are_not_encrypted` | DB value == plaintext input |
| `test_encryptable_trait_handles_nulls` | Null fields don't cause encryption errors |
| `test_filter_items_by_type` | GET ?type=password returns only passwords |
| `test_filter_favorite_items` | GET ?favorite=true returns only favorites |

---

## What This Module Does NOT Include

- Folders, tags, favorites toggle, search (Module 06)
- Secure notes as a vault type (Module 13)
- File uploads (Module 11)
- Sharing and permissions (Module 08, 09)
- Password generator and strength checker (Module 10)
- Archive/restore (Module 06)
- Password history (post-MVP)
