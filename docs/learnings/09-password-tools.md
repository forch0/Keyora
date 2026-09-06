# 10 — How Module 10 (Password Tools) Was Built

| Field | Value |
|---|---|
| **Module** | 10 — Password Generator & Strength Checker |
| **Date** | 2026-09-02 |
| **Spec** | `docs/modules/10-password-tools.md` |

---

## Goal

Build a password generator with customizable criteria and a password strength checker based on entropy scoring. These are utility endpoints usable when creating or updating vault items.

---

## Decisions Made Before Writing Code

### 1. Cryptographic randomness via `random_int()`

All random character selection uses `random_int()` (not `rand()` or `mt_rand()`) for cryptographic security. The shuffle also uses `random_int()`.

### 2. Minimum character counts enforced before fill

The generator first fills the minimum count per category (e.g., 3 uppercase, 3 numbers), then fills the remaining length from the combined pool, then shuffles. This guarantees minimums are met.

### 3. Common passwords list from SecLists

Used the Mozilla/Firefox password strength checker's top-1000 common passwords list (sourced from the SecLists project). Stored in `resources/data/common-passwords.txt` and loaded lazily.

### 4. Entropy-based scoring with penalties

Score combines length (0-80), character variety (10 each = 40 max), then applies penalties for common passwords (capped at 10), sequential characters (-15), and repeated patterns (-10).

---

## Files Created

| File | Purpose |
|---|---|
| `app/Services/PasswordGenerator.php` | Generate passwords with customizable options |
| `app/Services/PasswordStrengthChecker.php` | Score passwords with entropy, common detection, penalties |
| `app/Http/Requests/Tools/GeneratePasswordRequest.php` | Validate generation options |
| `app/Http/Requests/Tools/CheckPasswordStrengthRequest.php` | Validate strength check input |
| `app/Http/Controllers/Api/V1/PasswordToolController.php` | generate() and checkStrength() endpoints |
| `resources/data/common-passwords.txt` | Top 1000 common passwords |
| `tests/Feature/Api/V1/Tools/PasswordToolTest.php` | 12 feature tests |

### Modified

| File | What changed |
|---|---|
| `routes/api.php` | 2 new routes under `/tools/password` |

---

## All New Endpoints

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/v1/tools/password/generate` | Generate a password with options |
| POST | `/api/v1/tools/password/strength` | Check password strength |

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 144 tests, 415 assertions (12 new + 132 from Modules 02-09) |

---

## Key Takeaways

1. **`random_int()` for security** — always use `random_int()` for password generation, not `rand()` or `mt_rand()`. Even the shuffle must use it.

2. **Fill minimums first, then fill, then shuffle** — to guarantee minimum character counts per category, fill minimums first, fill the rest from the combined pool, then shuffle the entire string.

3. **Common password detection** — loading a word list from `resources/data/` and checking case-insensitively catches the most common weak passwords. The list is loaded lazily (only when needed) and cached in a property.

4. **Entropy = length × log2(charset_size)** — the entropy formula gives bits of entropy. A 16-character password with all 4 character types has ~94 bits of entropy.

5. **Penalties improve scoring accuracy** — sequential characters (abc, 123, qwerty) and repeated patterns (aaaa, abab) reduce the score even if the password is long, because they reduce the effective entropy.
