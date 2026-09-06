# Module F08 — Password Tools

| Field | Value |
|---|---|
| **Module** | F08 |
| **Name** | Password Tools |
| **Dependencies** | F03 |
| **Status** | Not Started |

---

## Objective

Build the password generator and strength checker. These are used both as standalone tools and embedded in the vault item create/edit forms.

---

## Tasks

### F08.1 Password generator component

- [ ] Create `src/features/tools/PasswordGenerator.tsx`
- [ ] Length slider (8-64)
- [ ] Toggles: uppercase, lowercase, numbers, symbols
- [ ] Toggle: exclude similar characters
- [ ] Min character counts per type
- [ ] Generate button → POST `/api/v1/tools/password/generate`
- [ ] Output field with copy button
- [ ] Strength meter (calls strength endpoint)

### F08.2 Strength checker

- [ ] Create `src/features/tools/PasswordStrengthChecker.tsx`
- [ ] Input field for password
- [ ] POST `/api/v1/tools/password/strength`
- [ ] Show: strength bar, entropy, suggestions, common password warning
- [ ] Real-time check (debounced)

### F08.3 Standalone tools page

- [ ] Route: `/tools` (accessible from sidebar or settings)
- [ ] Tabs: Generator | Strength Checker
- [ ] Both components available standalone

### F08.4 Embed in vault forms

- [ ] Add "Generate" button next to password field in create/edit forms (F05)
- [ ] Opens generator in popover or inline
- [ ] "Use this password" button fills the form field

### F08.5 Hooks

- [ ] `useGeneratePassword()` — mutation: POST `/api/v1/tools/password/generate`
- [ ] `useCheckStrength()` — mutation: POST `/api/v1/tools/password/strength`

---

## API Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `POST` | `/api/v1/tools/password/generate` | Generate password |
| `POST` | `/api/v1/tools/password/strength` | Check strength |

---

## Acceptance Criteria

- [ ] User can generate passwords with custom length and character types
- [ ] Generated password can be copied to clipboard
- [ ] Password strength checker shows strength, entropy, and suggestions
- [ ] Generator is embedded in vault item create/edit forms
- [ ] Common passwords are flagged

---

## What This Module Does NOT Include

- Vault item CRUD (Module F05)
- Auto-fill into external sites (browser extension — not in scope)
