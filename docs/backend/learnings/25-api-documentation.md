# 28 — How Module 28 (API Documentation) Was Built

| Field | Value |
|---|---|
| **Module** | 28 — API Documentation |
| **Date** | 2026-09-02 |
| **Spec** | `docs/modules/28-api-documentation.md` |

---

## Goal

Generate OpenAPI 3.1 documentation for all 117 API endpoints, organized by 28 functional groups, with bearer auth security scheme, accessible via HTML UI and JSON spec.

---

## Decisions Made Before Writing Code

### 1. Scramble over Scribe

The user explicitly requested Scramble (`dedoc/scramble`). Scramble is a zero-config API documentation generator for Laravel that analyzes code automatically — no manual annotations needed for basic docs. It generates OpenAPI 3.1 specs and provides an HTML UI.

**Why Scramble:**
- Automatic route analysis — no manual `@response` or `@bodyParam` annotations required
- PHP 8 attributes for grouping (`#[Group('name')]`)
- OpenAPI 3.1 compliant
- Built-in HTML UI (Stoplight Elements)
- Lightweight — no external dependencies for rendering

### 2. PHP 8 attributes for grouping, not PHPDoc

Scramble uses `#[Group('name')]` PHP 8 attributes, not `@group` PHPDoc annotations. This is cleaner and type-safe. Each of the 28 controllers received a `#[Group]` attribute.

### 3. Auto-documented bearer auth

Scramble's `MiddlewareAuthSecurityStrategy` automatically detects `auth:sanctum` middleware and documents bearer token authentication. Routes without `auth:sanctum` (like login, register, public link access) are marked as public (`security: []`).

### 4. viewApiDocs gate for non-local access

Scramble's `RestrictedDocsAccess` middleware allows access in local environment but blocks it elsewhere. Defined a `viewApiDocs` gate in `AppServiceProvider` that:
- Always allows access in `local` and `testing` environments
- In other environments, only allows emails listed in `SCRAMBLE_ALLOWED_EMAILS` env var

---

## Files Created/Modified

| File | Purpose |
|---|---|
| `config/scramble.php` | Published + customized: API path, title, description, security strategy, allowed emails |
| `app/Providers/AppServiceProvider.php` | Extended — `viewApiDocs` gate definition |
| `app/Http/Controllers/Api/V1/*.php` (28 files) | Added `#[Group('...')]` attribute + `use Dedoc\Scramble\Attributes\Group;` import |
| `tests/Feature/Api/V1/ApiDocs/ApiDocsTest.php` | 7 feature tests |
| `composer.json` | Added `dedoc/scramble` dev dependency |

---

## Bugs Found and Fixed During Implementation

### 1. Script inserted `use` statement inside method body

**Cause:** The script to add `use Dedoc\Scramble\Attributes\Group;` found the last `use` keyword in the file, but some controllers have `use ($variable)` inside closure callbacks. The script matched the closure's `use` and inserted the import statement inside the method body.

**Affected files:** `PersonalVaultItemController.php`, `SecureNoteController.php`, `NoteFolderController.php`

**Fix:** Manually moved the import to the top of each file with the other `use` statements and removed the misplaced line.

### 2. Empty docblocks left behind

**Cause:** The initial script added `@group` PHPDoc annotations (before switching to attributes). When switching to attributes, the `@group` line was removed but the empty `/** */` docblock remained.

**Fix:** Used `sed` to remove all empty docblocks (`/**` immediately followed by `*/`).

### 3. 403 on docs endpoints in testing

**Cause:** Scramble's `RestrictedDocsAccess` middleware only allows access in `local` environment. The testing environment was blocked.

**Fix:** Defined a `viewApiDocs` gate that allows access in `local` and `testing` environments, and via `SCRAMBLE_ALLOWED_EMAILS` in production.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 379 tests, 1119 assertions (7 new + 372 from Modules 02-27) |
| `php artisan scramble:export` | PASS — 117 paths, 28 groups, OpenAPI 3.1 |
