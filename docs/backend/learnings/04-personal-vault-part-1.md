# 05 — How Module 05 (Personal Vault Part 1) Was Built

| Field | Value |
|---|---|
| **Module** | 05 — Personal Vault: Core CRUD & Encryption |
| **Date** | 2026-09-01 |
| **Spec** | `docs/modules/05-personal-vault-part-1.md` |
| **Architecture ref** | `docs/ARCHITECTURE.md` §1.3 (ADR-003: Encryption Strategy) |

---

## Goal

Build the personal vault — the user's private encrypted storage for credentials, API keys, server credentials, and database credentials. This module covers core CRUD operations and the encryption layer. Personal vault items are NOT tenant-scoped — they belong to the user directly via `user_id`.

---

## Decisions Made Before Writing Code

### 1. Hard delete instead of soft delete

The spec said "soft delete or hard delete — decide". I chose hard delete because:
- Soft deletes add complexity (deleted_at column, global scope, need to exclude from queries)
- Archive/restore is handled separately via `archived_at` (Module 06)
- Hard delete is simpler and appropriate for MVP

### 2. `custom_fields` encrypted as JSON string

The spec says custom_fields is ENCRYPTED JSON. The `Encryptable` trait handles string encryption via `Crypt::encryptString()`. So custom_fields is:
- On set: `json_encode($value)` → `Crypt::encryptString($json)` → stored as TEXT
- On get: `Crypt::decryptString($value)` → `json_decode($json, true)` → returned as array

This requires custom `getAttribute`/`setAttribute` overrides in the model.

### 3. 404 for non-owner access (not 403)

The spec explicitly says "don't leak existence". Returning 404 for other users' items prevents information leakage about which items exist. The policy returns `false` (not an exception), and the controller calls `abort(404)`.

### 4. `metadata` stored in plaintext

Per ADR-003: metadata is display data (host, port, provider, db_type) — no secrets. Stored as plaintext JSON for searchability. Sensitive values go in encrypted columns.

---

## The Big Challenge: Encryptable Trait Method Shadowing

### The Problem

The `Encryptable` trait overrides `getAttribute()` and `setAttribute()` to handle encryption/decryption. The `PersonalVaultItem` model ALSO needs to override these methods for `custom_fields` (JSON + encryption).

In PHP, when a class uses a trait AND defines the same method, **the class's method wins** — the trait's method is completely shadowed. So:
- `PersonalVaultItem::setAttribute()` handles `custom_fields` but calls `parent::setAttribute()` which goes to `Model::setAttribute` — bypassing the `Encryptable` trait's encryption logic
- Result: `username`, `password`, `notes` were stored as plaintext

### The Fix

The model's `setAttribute()` now handles BOTH:
1. `custom_fields` → JSON-encode then encrypt
2. Encryptable fields (`username`, `password`, `notes`) → encrypt
3. Everything else → delegate to `parent::setAttribute()`

Similarly, `getAttribute()` handles:
1. `custom_fields` → decrypt then JSON-decode
2. Encryptable fields → decrypt
3. Everything else → delegate to `parent::getAttribute()`

### Lesson Learned

When a trait overrides a method and the using class also overrides the same method, the trait's version is shadowed. If you need both behaviors, the class's method must explicitly handle all cases — you can't rely on `parent::` to reach the trait's method.

---

## Files Created / Modified

### Traits

| File | Purpose |
|---|---|
| `Encryptable.php` | Transparent AES-256 encryption/decryption via `Crypt::encryptString()`/`decryptString()`, handles nulls gracefully |

### Migrations

| File | Purpose |
|---|---|
| `2026_09_01_160000_create_personal_vault_items_table.php` | personal_vault_items table with encrypted TEXT columns and plaintext searchable columns |

### Models

| File | What changed |
|---|---|
| `PersonalVaultItem.php` | Created — uses Encryptable trait, fillable, casts, user() relationship, scopes (ofType, favorite, active), custom getAttribute/setAttribute for encrypted + JSON custom_fields |
| `PersonalVaultItemFactory.php` | Created — default states for all 4 types |

### HTTP Layer

| File | Purpose |
|---|---|
| `CreateItemRequest.php` | Validates name, type enum, nullable encrypted fields, metadata sub-fields, custom_fields array validation |
| `UpdateItemRequest.php` | Same rules with `sometimes` for partial updates |
| `PersonalVaultItemResource.php` | Serializes all fields including decrypted sensitive fields |
| `PersonalVaultItemPolicy.php` | view/update/delete check user_id ownership, returns false (not 403) |
| `PersonalVaultItemController.php` | 5 RESTful methods with filters (type, favorite, archived) |

### Actions

| File | Purpose |
|---|---|
| `CreateVaultItemAction.php` | Creates vault item with user_id, encryption handled by model |
| `UpdateVaultItemAction.php` | Updates vault item, returns fresh model |
| `DeleteVaultItemAction.php` | Hard deletes vault item |

### Routes

| File | What changed |
|---|---|
| `routes/api.php` | Added `Route::apiResource('items', PersonalVaultItemController::class)` under `/vault` prefix |

---

## Request Flow (how encryption works)

```
1. User sends POST /api/v1/vault/items
   Body: { "name": "GitHub", "type": "password", "username": "me", "password": "secret" }
        │
        ▼
2. Controller: PersonalVaultItemController::store()
   → Delegates to CreateVaultItemAction
        │
        ▼
3. CreateVaultItemAction:
   → PersonalVaultItem::create([...])
   → Model's setAttribute() intercepts:
     - 'username' is in $encryptable → Crypt::encryptString('me')
     - 'password' is in $encryptable → Crypt::encryptString('secret')
     - 'name' is NOT in $encryptable → stored as plaintext
   → INSERT into database with encrypted values
        │
        ▼
4. Response: 201 with PersonalVaultItemResource
   → Resource calls $item->password
   → Model's getAttribute() intercepts:
     - 'password' is in $encryptable → Crypt::decryptString(encrypted_value)
     - Returns 'secret' (plaintext)
   → JSON response contains decrypted values
```

---

## All Endpoints

| Method | Endpoint | Auth | Status | Description |
|---|---|---|---|---|
| GET | `/api/v1/vault/items` | Yes | 200 | List user's vault items (paginated, filterable) |
| POST | `/api/v1/vault/items` | Yes | 201 | Create vault item (encrypts sensitive fields) |
| GET | `/api/v1/vault/items/{item}` | Yes | 200 | Get single item with decrypted sensitive fields |
| PUT | `/api/v1/vault/items/{item}` | Yes | 200 | Update vault item |
| DELETE | `/api/v1/vault/items/{item}` | Yes | 204 | Delete vault item |

### Query Parameters for GET /items

| Param | Type | Description |
|---|---|---|
| `type` | string | Filter by type (password, api_key, server, database) |
| `favorite` | boolean | Only favorite items |
| `archived` | boolean | Only archived items (default: only active) |

---

## Encryption Strategy (ADR-003)

| Data Type | Method | Searchable? |
|---|---|---|
| User login passwords | bcrypt via `Hash` facade (never reversible) | No |
| Vault secrets (username, password, notes) | AES-256-CBC via `Crypt` facade | No |
| Vault custom_fields | JSON → AES-256-CBC via `Crypt` facade | No |
| Vault name, url | Plaintext | Yes |
| Vault metadata (host, port, provider) | Plaintext JSON | Yes |

---

## Bugs Found and Fixed During Implementation

### 1. `user_id` not in fillable

**Cause:** The `#[Fillable]` attribute didn't include `user_id`, so mass assignment skipped it → NOT NULL constraint violation.

**Fix:** Added `user_id` to the Fillable attribute.

### 2. Encrypted values stored as plaintext

**Cause:** The `Encryptable` trait's `setAttribute` was shadowed by the model's own `setAttribute` override (for custom_fields). PHP trait method resolution: class method wins over trait methods.

**Fix:** Model's `setAttribute` now handles both custom_fields encryption AND encryptable field encryption directly.

### 3. Decryption not working on read

**Cause:** Same shadowing issue with `getAttribute` — model's override for custom_fields shadowed the trait's decryption logic.

**Fix:** Model's `getAttribute` now handles both custom_fields decryption AND encryptable field decryption directly.

### 4. `deleted_at` column not found

**Cause:** Model used `SoftDeletes` trait but migration didn't have `deleted_at` column.

**Fix:** Removed `SoftDeletes` trait — using hard delete instead (archive/restore is Module 06).

### 5. PHPStan: `property_exists` always true

**Cause:** PHPStan analyzes the trait in context of the concrete class where the property is always declared.

**Fix:** Added `@phpstan-ignore-next-line` comment — the trait is designed to be reusable on models that may not declare the property.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 72 tests, 231 assertions (15 new + 57 from Modules 02/03/04) |

---

## What This Module Does NOT Include

Per the module spec, these are deferred to later modules:

- Folders, tags, favorites toggle, search → Module 06
- Archive/restore → Module 06
- Secure notes as a vault type → Module 13
- File uploads → Module 11
- Sharing and permissions → Module 08, 09
- Password generator and strength checker → Module 10
- Password history → post-MVP

---

## Key Takeaways

1. **PHP trait method shadowing** — when a class uses a trait AND defines the same method, the class's method wins. The trait's method is completely shadowed. If you need both behaviors, the class must handle all cases explicitly.

2. **Encrypted JSON fields need special handling** — the `Encryptable` trait handles string encryption, but JSON+encryption requires custom `getAttribute`/`setAttribute` overrides: JSON-encode then encrypt on set, decrypt then JSON-decode on get.

3. **404 not 403 for non-owners** — returning 404 for resources the user doesn't own prevents information leakage about which resources exist. This is a security best practice for sensitive data like password vaults.

4. **Plaintext searchable fields vs encrypted fields** — per ADR-003, fields that need searching (name, url, metadata) are stored in plaintext. Fields containing secrets (username, password, notes, custom_fields) are encrypted. You cannot query encrypted fields with WHERE clauses.

5. **`Crypt::encryptString()` handles nulls gracefully** — but only if you check for null BEFORE calling it. The trait checks `!== null && !== ''` before encrypting to avoid encrypting empty strings.

---

## Build Order — Files Created A to Z

```
 1. Trait              → Encryptable.php
 2. Migration          → 2026_09_01_160000_create_personal_vault_items_table.php
    → php artisan migrate:fresh
 3. Model              → PersonalVaultItem.php
 4. Factory            → PersonalVaultItemFactory.php
 5. Form Request       → CreateItemRequest.php
 6. Form Request       → UpdateItemRequest.php
 7. API Resource       → PersonalVaultItemResource.php
 8. Policy             → PersonalVaultItemPolicy.php
 9. Action             → CreateVaultItemAction.php
10. Action             → UpdateVaultItemAction.php
11. Action             → DeleteVaultItemAction.php
12. Controller         → PersonalVaultItemController.php
13. Routes             → routes/api.php (modified — added vault apiResource)
14. Test               → PersonalVaultItemTest.php (15 tests)
15. Verification       → pint → phpstan → php artisan test
```
