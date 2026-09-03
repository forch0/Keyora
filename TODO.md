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

---

## Priority 3 — Quality of Life

These are nice-to-haves that improve maintainability but aren't urgent.

### 3.1 CI Pipeline
- [ ] No CI exists — no automated test/static analysis enforcement on push
- [ ] Add GitHub Actions workflow running `php artisan test`, `vendor/bin/phpstan analyse`, `vendor/bin/pint --test`
- [ ] Run on push to `main` and on PRs
- [ ] Cache Composer dependencies in CI for speed
- [ ] Consider adding `composer audit` for vulnerability scanning

### 3.2 Pin Composer Dependencies
- [ ] `composer.json` uses floating `^` ranges for all deps (framework, sanctum, scramble, larastan, pint, etc.)
- [ ] Pin to exact versions or tight ranges to prevent unvetted releases being pulled
- [ ] Run `composer update` deliberately, not as a side effect of `composer install`
- [ ] Commit `composer.lock` (verify it's not gitignored)

### 3.3 Structured Logging
- [ ] All log calls use `Log::info()` / `Log::error()` with string messages
- [ ] Switch to structured logging: `Log::info('event', ['user_id' => $id, 'action' => $action])`
- [ ] Add a logging channel config for JSON output in production (easier to parse/ship to log aggregator)
- [ ] Ensure decryption failures (from `Encryptable` trait) include enough context for debugging

### 3.4 Trash Pagination
- [ ] `PersonalVaultItemController::trash()` returns all trashed items without pagination
- [ ] `ListTrash` action likely returns a collection, not a paginator
- [ ] Add `->paginate(20)` like the other list endpoints
- [ ] Same for `SecureFileController::trash()` and `SecureNoteController::trash()`

### 3.5 Controller Consistency
- [ ] Some controllers use `$request->user()` directly, others use `$this->authenticatedUser($request)`
- [ ] Standardize on one pattern (prefer the helper since it handles null and aborts 401)
- [ ] Audit all controllers in `app/Http/Controllers/Api/V1/` for this inconsistency

### 3.6 PHPStan Ignore Cleanup
- [ ] 3 `@phpstan-ignore-next-line` annotations exist in `ForceDeleteModelAction`, `RestoreModelAction`, `EmptyTrashAction`
- [ ] All ignore `staticMethod.notFound` for `onlyTrashed()` on SoftDeletes trait
- [ ] Investigate whether a phpstan.neon config tweak or a docblock can resolve these without ignores

### 3.7 Database Engine Documentation Consistency
- [ ] `.env.example` uses MySQL (`DB_CONNECTION=mysql`)
- [ ] `BUILD_LOG.md` and `docs/ARCHITECTURE.md` say PostgreSQL
- [ ] `docker-compose.yml` uses PostgreSQL
- [ ] `phpunit.xml` uses SQLite in-memory
- [ ] Pick one primary engine (PostgreSQL is the documented choice) and update `.env.example` to match
- [ ] Document that SQLite is for tests only

### 3.8 README Encryption Claims
- [ ] README says "AES-256 (via Laravel Crypt)" which is accurate
- [ ] But "private, encrypted vault" could be misread as zero-knowledge (like Bitwarden/1Password)
- [ ] Add a note clarifying this is server-side encryption keyed by `APP_KEY`, not client-side zero-knowledge
- [ ] State the threat model explicitly: internal tool, behind VPN, authenticated users

### 3.9 BUILD_LOG.md Size
- [ ] BUILD_LOG.md is very large with detailed per-module logs
- [ ] Consider moving detailed logs to `docs/learnings/` and keeping BUILD_LOG as a summary table only
- [ ] Or archive old module logs (01–24) into a separate file

### 3.10 API Versioning Strategy
- [ ] Routes are under `/api/v1` but there's no plan for `v2` or deprecation
- [ ] Document the versioning policy: when does `v2` happen? How long is `v1` supported?
- [ ] Consider adding a `Accept: application/vnd.keyora.v1+json` header strategy or just keep URL-based

---

## Priority 4 — Future Modules / Features

These are larger efforts that would extend the platform beyond its current scope.

### 4.1 Import / Export
- [ ] Bulk import vault items from CSV/JSON (password managers, spreadsheets)
- [ ] Export all items for a user or tenant (encrypted bundle)
- [ ] Support Bitwarden/1Password/LastPass import formats

### 4.2 Webhooks
- [ ] Outgoing webhooks for key events (access granted, file uploaded, employee offboarded)
- [ ] Configurable per-tenant webhook endpoints
- [ ] Signed payloads with retry logic

### 4.3 SSO / SAML
- [ ] Single sign-on integration (SAML 2.0 or OIDC)
- [ ] Map IdP groups to tenant roles
- [ ] JIT provisioning on first login

### 4.4 Mobile API Considerations
- [ ] Audit API responses for mobile-friendliness (payload size, nested includes)
- [ ] Consider GraphQL or a lighter resource format for mobile clients
- [ ] Push notification support for access requests / expirations

### 4.5 Audit Log Export & Retention
- [ ] Export audit logs to external SIEM (via webhook or scheduled dump)
- [ ] Configurable retention period per tenant
- [ ] Immutable log storage option (write-once)

### 4.6 Zero-Knowledge Encryption (Future)
- [ ] Current model: server-side encryption keyed by `APP_KEY`
- [ ] If repositioned as a public product, redesign for client-side encryption
- [ ] User-derived key from master password (PBKDF2/Argon2 → symmetric key)
- [ ] Server never sees plaintext or decryption key
- [ ] Key rotation and recovery flow
- [ ] This is a major architectural change — not a patch, a rewrite of the encryption layer

---

## Notes

- Priority 1 and 2 items are complete (branches `feature/KEY-32-production-readiness` and `feature/KEY-33-priority-2-fixes`)
- The project is suitable for internal deployment with real credentials after Priority 1
- Priority 3 items improve maintainability and should be done within the first quarter
- Priority 4 items are scope expansions, not corrections
