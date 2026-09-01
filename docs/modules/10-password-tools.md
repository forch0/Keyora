# Module 10 — Password Tools

| Field | Value |
|---|---|
| **Module** | 10 |
| **Name** | Password Generator & Strength Checker |
| **Dependencies** | Module 05 |
| **Status** | Not Started |

---

## Objective

Build a password generator with customizable criteria and a password strength checker based on entropy scoring. These are utility endpoints that can be used when creating or updating vault items.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| PS-01 | Generate passwords with customizable criteria | P0 |
| PS-02 | Check password strength (entropy-based scoring) | P0 |

---

## Tasks

### 10.1 PasswordGenerator Service

- [ ] Create `app/Services/PasswordGenerator.php`:

```php
class PasswordGenerator
{
    public function generate(array $options = []): string
    {
        // Options:
        // - length: int (default 16, min 8, max 128)
        // - uppercase: bool (default true)
        // - lowercase: bool (default true)
        // - numbers: bool (default true)
        // - symbols: bool (default true)
        // - exclude_similar: bool (default false) — removes 0,O,1,l,I
        // - exclude_ambiguous: bool (default false) — removes {}[]()/\'"`~,;.<>
        // - min_uppercase: int (default 1)
        // - min_lowercase: int (default 1)
        // - min_numbers: int (default 1)
        // - min_symbols: int (default 1)
    }
}
```

- [ ] Use `random_int()` for cryptographically secure random character selection
- [ ] Ensure minimum character counts per category are met
- [ ] Shuffle the final password

### 10.2 PasswordStrengthChecker Service

- [ ] Create `app/Services/PasswordStrengthChecker.php`:

```php
class PasswordStrengthChecker
{
    public function check(string $password): array
    {
        // Returns:
        // - score: int (0-100)
        // - strength: string ('very_weak', 'weak', 'fair', 'strong', 'very_strong')
        // - entropy: float (bits of entropy)
        // - suggestions: array (improvement suggestions)
        // - criteria: array (which checks passed/failed)
    }
}
```

**Scoring criteria:**
- Length: <8 = 0, 8-11 = 20, 12-15 = 40, 16-19 = 60, 20+ = 80
- Character variety: uppercase, lowercase, numbers, symbols (10 points each)
- Entropy calculation: `log2(charset_size ^ length)`
- Common password check: compare against a list of top 1000 common passwords
- Sequential characters penalty (abc, 123, qwerty)
- Repeated characters penalty (aaaa, 1111)

**Strength labels:**
| Score | Strength |
|---|---|
| 0-20 | very_weak |
| 21-40 | weak |
| 41-60 | fair |
| 61-80 | strong |
| 81-100 | very_strong |

### 10.3 API Endpoints

| Method | Endpoint | Description | Auth |
|---|---|---|---|
| `POST` | `/api/v1/tools/password/generate` | Generate a password | Yes |
| `POST` | `/api/v1/tools/password/strength` | Check password strength | Yes |

### 10.4 Controller

- [ ] `app/Http/Controllers/Api/V1/PasswordToolController.php`:
  - `generate()` — accepts options, returns generated password
  - `checkStrength()` — accepts password, returns strength analysis

### 10.5 Form Requests

- [ ] `GeneratePasswordRequest`:
  - `length`: nullable, integer, min:8, max:128
  - `uppercase`: nullable, boolean
  - `lowercase`: nullable, boolean
  - `numbers`: nullable, boolean
  - `symbols`: nullable, boolean
  - `exclude_similar`: nullable, boolean
  - `exclude_ambiguous`: nullable, boolean
  - `min_uppercase`: nullable, integer, min:0
  - `min_lowercase`: nullable, integer, min:0
  - `min_numbers`: nullable, integer, min:0
  - `min_symbols`: nullable, integer, min:0
  - Validation: at least one character type must be enabled

- [ ] `CheckPasswordStrengthRequest`:
  - `password`: required, string, max:1000

### 10.6 Response Format

**Generate:**
```json
{
  "data": {
    "password": "K7#mQ9$vL2&pX5nR",
    "options": {
      "length": 16,
      "uppercase": true,
      "lowercase": true,
      "numbers": true,
      "symbols": true
    }
  }
}
```

**Strength check:**
```json
{
  "data": {
    "score": 85,
    "strength": "very_strong",
    "entropy": 103.7,
    "criteria": {
      "length": true,
      "uppercase": true,
      "lowercase": true,
      "numbers": true,
      "symbols": true,
      "no_common_patterns": true
    },
    "suggestions": []
  }
}
```

### 10.7 Routes

```php
Route::middleware('auth:sanctum')->prefix('v1/tools/password')->group(function () {
    Route::post('generate', [PasswordToolController::class, 'generate']);
    Route::post('strength', [PasswordToolController::class, 'checkStrength']);
});
```

---

## Acceptance Criteria

- [ ] `POST /tools/password/generate` returns a random password with default options
- [ ] Generated password respects length option
- [ ] Generated password includes/excludes character types based on options
- [ ] `exclude_similar` removes ambiguous characters (0, O, 1, l, I)
- [ ] Minimum character counts per category are enforced
- [ ] Generated password is cryptographically random (uses `random_int()`)
- [ ] `POST /tools/password/strength` returns score, strength label, entropy, and suggestions
- [ ] Common passwords (password, 123456, etc.) score very_weak
- [ ] Long random passwords score very_strong
- [ ] Suggestions are actionable (e.g., "Add uppercase letters", "Use 12+ characters")
- [ ] At least one character type must be enabled → validation error otherwise

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_generate_default_password` | Returns 16-char password with all character types |
| `test_generate_custom_length` | Respects length option |
| `test_generate_exclude_symbols` | No symbols in output |
| `test_generate_exclude_similar` | No 0, O, 1, l, I in output |
| `test_generate_min_character_counts` | Minimum counts per category met |
| `test_generate_requires_one_type` | All types disabled → 422 |
| `test_strength_weak_password` | "password" → very_weak |
| `test_strength_strong_password` | 20-char random → very_strong |
| `test_strength_returns_entropy` | Entropy is calculated and returned |
| `test_strength_returns_suggestions` | Weak password has suggestions |
| `test_strength_common_password_detected` | Top 1000 password detected |
| `test_strength_sequential_penalty` | "abcdefgh" scores lower than random |

---

## What This Module Does NOT Include

- Password expiration and rotation reminders (post-MVP)
- Password history tracking (post-MVP)
- Integration with vault item creation (frontend will call these endpoints separately)
