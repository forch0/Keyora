# Keyora — TODO

> Context: Internal tool for a single company with subsidiaries, max ~100 staff.
> Threat model: behind VPN/firewall, authenticated employees, no public attack surface.

---

## Done

- [x] Modules 01–24 (core platform)
- [x] Modules 25–26 (SaaS billing — intentionally skipped, open source)
- [x] Module 27 — Rate limiting
- [x] Module 28 — API documentation (Scramble)
- [x] Module 29 — Soft deletes consistency
- [x] Module 30 — Bulk operations
- [x] Module 31 — Dashboard caching
- [x] KEY-32 — Priority 1 production readiness (1.1–1.8)
- [x] KEY-33 — Priority 2 fixes (2.1–2.8)
- [x] KEY-34 — Priority 3 quality of life (3.1–3.10)

---

## Priority 3 — Quality of Life (Complete)

### 3.1 CI Pipeline
- [x] GitHub Actions workflow running `php artisan test`, `vendor/bin/phpstan analyse`, `vendor/bin/pint --test`
- [x] Runs on push to `main`/`develop` and on PRs
- [x] Composer dependencies cached in CI
- [x] `composer audit` included (non-blocking)

### 3.2 Pin Composer Dependencies
- [x] All deps pinned to exact versions in `composer.json` (PHP kept at `^8.3`)
- [x] `composer.lock` committed and tracked
- [x] Replaced `*` and `@alpha` constraints with exact versions

### 3.3 Structured Logging
- [x] JSON logging channel added to `config/logging.php` (daily rotation, `JsonFormatter`)
- [x] `.env.example` documents `LOG_STACK=json` for production
- [x] Existing log calls already use structured context arrays (Encryptable, PersonalVaultItem, VaultItem)

### 3.4 Trash Pagination
- [x] `ListTrashAction` already returns `LengthAwarePaginator` — trash endpoints were already paginated
- [x] Added `per_page` query parameter support (default 20, capped at 100)
- [x] Updated all 4 trash controllers (vault items, notes, files, teams)
- [x] Added tests for pagination metadata, per_page, and cap

### 3.5 Controller Consistency
- [x] `authenticatedUser()` extracted to base `Controller` class (protected method)
- [x] Removed 27 duplicated private helper methods across controllers
- [x] Fixed direct `$request->user()` call in `PersonalVaultItemController::index()`

### 3.6 PHPStan Ignore Cleanup
- [x] Reduced from 3 `@phpstan-ignore-next-line` to 1
- [x] `onlyTrashed()` calls resolved via `@var Model&SoftDeletes` + `@var Builder<Model>` intersection types
- [x] Remaining 1 ignore: `restore()` instance method call in `RestoreModelAction` (unavoidable — PHPStan can't resolve trait methods on instance variables)

### 3.7 Database Engine Documentation Consistency
- [x] `.env.example` updated from MySQL to PostgreSQL (`DB_CONNECTION=pgsql`, port 5432)
- [x] `docs/DEPLOYMENT.md` updated — PostgreSQL 16 is the primary engine
- [x] `phpunit.xml` annotated — SQLite in-memory is for tests only
- [x] `docker-compose.yml` already used PostgreSQL 16

### 3.8 README Encryption Claims
- [x] Added "Security Model" section clarifying server-side encryption (not zero-knowledge)
- [x] Updated tech table: "AES-256-CBC server-side (via Laravel Crypt, keyed by `APP_KEY`)"
- [x] Added threat model: internal tool, behind VPN/firewall, ~100 authenticated staff

### 3.9 BUILD_LOG.md Size
- [x] BUILD_LOG.md was already trimmed to 64 lines (summary table only) — no action needed

### 3.10 API Versioning Strategy
- [x] Created `docs/API_VERSIONING.md` with full versioning/deprecation policy
- [x] URL-based versioning (`/api/v1/`, `/api/v2/`)
- [x] Documents breaking vs non-breaking changes
- [x] v1 supported indefinitely; v2 gets 6-month overlap with deprecation headers

---

## Notes

- Priority 1, 2, and 3 items are complete
- Branches: `feature/KEY-32-production-readiness`, `feature/KEY-33-priority-2-fixes`, `feature/KEY-34-priority-3-quality-of-life`
- The project is suitable for internal deployment with real credentials
- All priorities complete — no remaining TODO items
