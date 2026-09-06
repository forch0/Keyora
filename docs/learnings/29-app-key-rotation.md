# KEY-34 — How APP_KEY Rotation Was Built

| Field | Value |
|---|---|
| **Module** | KEY-34 — Priority 3 Quality of Life (APP_KEY rotation) |
| **Date** | 2026-09-06 |
| **Branch** | `feature/KEY-34-priority-3-quality-of-life` |
| **Base branch** | `develop` |

---

## Goal

Provide a documented, tested procedure and tooling for rotating `APP_KEY` without losing encrypted data. Keyora uses server-side AES-256-CBC encryption keyed by `APP_KEY` — if the key is lost, all encrypted data is unrecoverable. If the key is rotated naively, all existing ciphertext becomes undecryptable.

---

## Decisions Made Before Writing Code

### 1. Use Laravel 13's built-in `APP_PREVIOUS_KEYS` support

Laravel 13's `Encrypter` has native support for previous keys via the `APP_PREVIOUS_KEYS` env var. The encrypter tries the current key first, then falls back to previous keys for decryption. This means the app stays functional during rotation — old data decrypts with the old key, new data encrypts with the new key.

No custom decrypt-with-old-key logic was needed. The rotation command just needs to read, decrypt (which auto-uses previous keys), and re-encrypt with the current key.

### 2. Direct DB queries instead of Eloquent models

The rotation command uses `DB::table()` instead of Eloquent models because:

- Eloquent's `Encryptable` trait intercepts `getAttribute`/`setAttribute` and would re-encrypt on save, but the trait's mutators would double-encrypt or interfere with raw value handling
- `VaultItem` is tenant-scoped (`BelongsToTenant`), which requires tenant context to query — the rotation command runs cross-tenant
- Direct DB updates bypass model events, observers, and mutators entirely, giving full control over the encrypted ciphertext

### 3. `--dry-run` and `--force` flags

- `--dry-run` lets operators preview what would be re-encrypted before committing
- `--force` skips the confirmation prompt (needed for automated/test environments)

### 4. Chunked processing

Rows are processed in chunks of 100 to avoid loading entire tables into memory. This matters for production databases with thousands of vault items.

---

## What Was Built

### `php artisan key:rotate` command

**File**: `src/app/Console/Commands/RotateEncryptionKey.php`

Re-encrypts all encrypted data with the current `APP_KEY`:

1. Reads every row from `personal_vault_items`, `vault_items`, `secure_notes`, and `users`
2. For each encrypted field, decrypts the raw ciphertext (auto-uses `APP_PREVIOUS_KEYS` if current key fails)
3. Re-encrypts the decrypted value with the current key
4. Writes the new ciphertext directly to the database via `DB::table()->update()`
5. Reports a summary: processed, skipped, failed

### Encrypted fields covered

| Model | Table | Encrypted fields |
|---|---|---|
| `PersonalVaultItem` | `personal_vault_items` | `username`, `password`, `notes`, `custom_fields` (JSON+encrypted) |
| `VaultItem` (team/org) | `vault_items` | `username`, `password`, `notes`, `custom_fields` (JSON+encrypted) |
| `SecureNote` | `secure_notes` | `content` |
| `User` | `users` | `two_factor_secret`, `two_factor_recovery_codes` |

### Tests

**File**: `src/tests/Feature/Console/RotateEncryptionKeyTest.php`

6 tests covering:
- Dry run reports without making changes
- Re-encrypts data with the current key (verifies ciphertext changes, plaintext stays the same)
- Handles secure notes
- Handles custom fields (JSON + encrypted)
- Skips null and empty fields
- Warns when `APP_PREVIOUS_KEYS` is not set

---

## Rotation Procedure

### Before rotating

1. **Back up `APP_KEY`** to a secure offline location (password manager, physical vault)
2. **Back up the database**: `php artisan db:backup`

### Rotation steps

```bash
# 1. Generate a new key (don't write to .env yet)
php artisan key:generate --show

# 2. Update .env
APP_KEY=base64:newKeyHere
APP_PREVIOUS_KEYS=base64:oldKeyHere

# 3. Clear and cache config
php artisan config:clear
php artisan config:cache

# 4. Dry run — verify what will be re-encrypted
php artisan key:rotate --dry-run --force

# 5. Re-encrypt all data
php artisan key:rotate --force

# 6. Verify — log in, view vault items, check 2FA, check notes

# 7. Remove the old key from .env
APP_PREVIOUS_KEYS=

# 8. Clear and cache config again
php artisan config:clear
php artisan config:cache

# 9. Update your offline APP_KEY backup with the new key
```

---

## Disaster Recovery: Lost APP_KEY

If `APP_KEY` is lost and no backup exists:

1. **All encrypted data is unrecoverable.** There is no backdoor.
2. Generate a new key: `php artisan key:generate`
3. All encrypted fields will return `null` (fail-closed behavior in the `Encryptable` trait)
4. Users must re-enter stored credentials manually
5. 2FA must be re-enabled by each user
6. Force a password reset for all users

### Partial recovery

A database backup without the corresponding `APP_KEY` is useless — both are needed for recovery.
