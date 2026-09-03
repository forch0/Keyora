# Keyora — Tidy-Up Checklist

> Context: Internal tool for a single company with subsidiaries, max ~100 staff.
> Threat model: behind VPN/firewall, authenticated employees, no public attack surface.

---

## Priority 1 — Before Deploying

These are the items that should be resolved before putting real credentials in this system.

### 1.1 Database Backups
- [x] Document automated backup strategy (daily snapshot + pre-migration backup)
- [x] Add backup command to scheduled tasks (`db:backup` or similar)
- [x] Document restore procedure in deployment runbook
- [ ] Test a restore from backup at least once

### 1.2 Bulk Share subject_type Validation
- [x] `BulkOperationService::bulkShare` trusts `subject_type` from user input
- [x] Validate `subject_type` against a whitelist: `User`, `Team`, `Tenant`
- [x] Add test: invalid `subject_type` returns 422
- [x] File: `src/app/Services/BulkOperationService.php` line ~190

### 1.3 Queue Configuration
- [x] Verify notifications are queued, not synchronous
- [x] Document queue driver in deployment runbook (Redis recommended)
- [x] Add failed-job retry configuration
- [x] Test: email notification doesn't block HTTP response

### 1.4 Deployment Runbook
- [x] Create `docs/DEPLOYMENT.md` with:
  - Server requirements (PHP version, extensions, Redis optional)
  - Environment variables (`.env.example` with all required vars)
  - Install steps (composer install, migrate, seed, storage:link)
  - Backup procedure
  - Restore procedure
  - Update procedure (pull, migrate, clear cache)
  - Troubleshooting common issues

### 1.5 Health Check Endpoint
- [x] Add `GET /api/v1/health` returning JSON status
- [x] Check: database connection, cache connection, storage writable
- [x] No authentication required (for load balancer / monitoring)
- [x] Add test

### 1.6 Encryptable Trait Fails Open on Decryption Errors
- [x] `Encryptable::getAttribute()` catches decryption failures and returns the raw ciphertext as if it were plaintext
- [x] For a security product this is the wrong default — a silent failure means corrupted/key-rotated data is served as "the secret"
- [x] Should fail closed: throw or return null, and log loudly (not via Module 20 audit log — that's a DB write, this is a crypto failure)
- [x] Add a test: tampered ciphertext does not get returned as-is
- [x] File: `src/app/Traits/Encryptable.php` lines ~46-53
- [x] Note: the existing comment says "Module 20 will cover this" — it does not

### 1.7 GrantAccessAction Race Condition
- [x] `GrantAccessAction::__invoke()` does a lookup for an existing active grant, then either updates or creates — not wrapped in a transaction, no unique index
- [x] Two concurrent grants for the same subject + resource can create duplicate `AccessGrant` rows
- [x] Fix: wrap the lookup + create/update in `DB::transaction()` with `lockForUpdate()` on the lookup, OR add a unique index on `(tenant_id, grantable_type, grantable_id, subject_type, subject_id)` where `revoked_at IS NULL` (partial index)
- [x] Add a test: concurrent grant requests for the same subject do not produce duplicates
- [x] File: `src/app/Actions/GrantAccessAction.php` lines ~52-95

### 1.8 subject_type Whitelist — Wider Than bulkShare
- [x] Item 1.2 flags `BulkOperationService::bulkShare` — but the same `subject_type`-from-user-input pattern exists across the whole access-grant API
- [x] `AccessGrantController::store` accepts `subject_type` as a raw string (e.g. `User::class`) and passes it straight into `GrantAccessAction`, which uses it in queries
- [x] Apply the same whitelist (`User`, `Team`, `Tenant`) to every endpoint that accepts `subject_type`
- [x] Consider an enum (`SubjectType`) instead of raw class strings, both for validation and for storage
- [x] Add tests: invalid `subject_type` returns 422 on every accepting endpoint
- [x] Files: `src/app/Http/Controllers/Api/V1/AccessGrantController.php`, `src/app/Http/Requests/Access/GrantAccessRequest.php`, `src/app/Actions/GrantAccessAction.php`

---

## Priority 2 — Within First Month

These are real issues but won't block deployment. Fix them once the system is running.

### 2.1 Bulk Operations: Dispatch Domain Events
- [x] `BulkOperationService::bulkShare` creates `AccessGrant` without dispatching `AccessGranted` event
- [x] This means cache invalidation via event subscriber doesn't fire for bulk shares
- [x] Dispatch `AccessGranted` for each grant created in bulk share
- [x] Same for any other bulk methods that create domain objects
- [x] File: `src/app/Services/BulkOperationService.php`

### 2.2 Offboarding: Invalidate Dashboard Cache
- [x] Member offboarding updates a pivot record, not a model
- [x] `DashboardCacheObserver` doesn't fire for pivot updates
- [x] The offboard action/service should call `DashboardCacheService::invalidateCompanyDashboard()` explicitly
- [x] File: wherever the offboard logic lives (check `TenantMemberController` or related Action)

### 2.3 Database Indexes for Dashboard Queries
- [x] `DashboardService` runs multiple COUNT queries per dashboard request
- [x] Add indexes on:
  - `personal_vault_items`: `(user_id, archived_at)` — already existed
  - `personal_vault_items`: `(user_id, last_accessed_at)` — already existed
  - `secure_files`: `(tenant_id)` — covered by existing composite indexes
  - `secure_notes`: `(tenant_id)` — covered by existing composite indexes
  - `access_grants`: `(subject_type, subject_id, revoked_at)` — added in migration
  - `access_grants`: `(tenant_id, revoked_at, expires_at)` — added in migration
  - `activity_logs`: `(tenant_id, created_at)` — already existed
  - `security_alerts`: `(user_id, read_at)` — already existed
- [x] Create a migration for these indexes
- [ ] Verify query performance with `EXPLAIN` on key queries

### 2.4 Ownership Check in BulkOperationService
- [x] `actorOwns()` for tenant-scoped models checks only tenant membership, not actual ownership
- [x] Any tenant member can bulk-delete any tenant-scoped item, even if they don't own it
- [x] Should check `user_id` on the item if the model has one, or check a proper permission
- [x] File: `src/app/Services/BulkOperationService.php` line ~260

### 2.5 Route Path Consistency
- [x] Module 29 uses `/api/v1/vault/trash/{item}/restore`
- [x] Module 30 uses `/api/v1/personal-vault/items/bulk/delete`
- [x] Pick one convention and standardize:
  - Option A: `/api/v1/personal-vault/items/{item}/restore` (resource-based)
  - Option B: `/api/v1/vault/items/{item}/restore` (shorter)
- [x] Update routes, controllers, tests, and Scramble docs

### 2.6 AccessResolver::isOwner() Is Over-Permissive
- [x] `isOwner()` checks `user_id` OR `created_by` via `array_key_exists($resource->getAttributes())`
- [x] Any model with a `created_by` column grants `Permission::Manage` to the creator forever — even after they should have lost access (e.g. offboarded, removed from team, access revoked)
- [x] Owner status should be re-evaluated against current tenant membership / active grants, not just a static column
- [x] Also fragile: depends on the attribute being loaded in `$resource->getAttributes()` — lazy-loaded or projected models may not have it
- [x] File: `src/app/Services/AccessResolver.php` lines ~143-156

### 2.7 AccessResolver N+1 and In-Memory Filtering
- [x] `activeGrantsFor()` loads all grants for a resource into a collection, then `grantAppliesToUser()` runs `$user->teams()->where(...)->exists()` per grant inside a `filter()` loop
- [x] At ~100 users this is fine (matches the stated scope), but it's an N+1 and won't scale beyond that
- [x] Push the subject filtering into the query: build `whereIn('subject_id', $teamIds)` etc. in `activeGrantsFor()` so the DB does the work in one round-trip
- [x] File: `src/app/Services/AccessResolver.php` lines ~166-203

### 2.8 AccessGrantController Cleanup
- [x] `index` and `summary` duplicate the same authz block: `if (! can(Share) && user_id !== ... && ! isAdminOf)` — extract to a policy method (e.g. `viewAccessGrants` on `VaultItemPolicy`)
- [x] `bulkStore` uses inline `$request->validate([...])` instead of a Form Request like the rest of the codebase — create `BulkGrantAccessRequest`
- [x] `countdown` bypasses `AccessResolver` entirely and does its own raw `AccessGrant::withoutTenant()->where(...)` query — route through the resolver or a dedicated method on it
- [x] File: `src/app/Http/Controllers/Api/V1/AccessGrantController.php`

---

## Priority 3 — Quality of Life

These are nice-to-haves that improve maintainability but aren't urgent.

### 3.1 Clean Up PHPStan Ignore Comments
- [ ] `RestoreModelAction.php` has `@phpstan-ignore-next-line staticMethod.notFound`
- [ ] `EmptyTrashAction.php` has same
- [ ] Consider using a generic type parameter or interface instead of ignoring
- [ ] Or accept the ignores as documented trade-offs (they're for SoftDeletes trait methods on class-strings)

### 3.2 Controller Consistency
- [ ] Some controllers use `authenticatedUser($request)` helper
- [ ] Some use `$request->user()` directly with null checks
- [ ] Standardize on `authenticatedUser($request)` everywhere
- [ ] Files to check: all controllers in `src/app/Http/Controllers/Api/V1/`

### 3.3 Pagination on Trash Listings
- [ ] `ListTrashAction` returns all trashed items without pagination
- [ ] At 100 users this is fine, but add `paginate(50)` for good practice
- [ ] Add `per_page` query parameter (max 100)
- [ ] File: `src/app/Actions/ListTrashAction.php`

### 3.4 Structured Logging
- [ ] Current logging is activity logs (database) + default Laravel logs
- [ ] Add structured JSON logging for production debugging
- [ ] Log: API errors, failed auth attempts, queue failures, cache misses
- [ ] Configure in `config/logging.php`

### 3.5 API Versioning Strategy
- [ ] Currently `/api/v1/` but no documented plan for v2
- [ ] Document: when to bump version, how to handle deprecation
- [ ] Add `Accept: application/json` enforcement (reject non-JSON requests)

### 3.6 Database Engine Inconsistency Across Docs
- [ ] README says MySQL 8.0, `BUILD_LOG.md` says PostgreSQL 16, `phpunit.xml` uses sqlite `:memory:`
- [ ] Pick one engine for production, document it in README + deployment runbook, and align the test DB to match (or explicitly document that tests run on sqlite while prod runs on X)
- [ ] Files: `README.md`, `BUILD_LOG.md`, `src/phpunit.xml`, `src/.env.example`

### 3.7 BUILD_LOG.md Size in Repo
- [ ] `BUILD_LOG.md` is ~85 KB and 1074 lines — useful during the build, but shouldn't live in the repo long-term
- [ ] Either move to `docs/` as a build-history archive, or rely on git history and remove the file
- [ ] If kept, trim to a summary table and link to per-module docs

### 3.8 Floating Version Constraints in composer.json
- [ ] `laramint/laravel-brain: *`, `laravel-lang/lang: *`, `laravel/telescope: *` — unbounded ranges auto-resolve to whatever is newest, including brand-new releases
- [ ] Pin each to a stable version published at least 7 days ago (e.g. `^1.0`, `^15.0`)
- [ ] `sunchayn/nimbus: @alpha` is fine for dev-only but pin to a specific alpha if reproducibility matters
- [ ] File: `src/composer.json`

### 3.9 No CI Configuration
- [ ] No `.github/workflows/` or equivalent — 417 tests run only manually
- [ ] Add a CI workflow that runs: `composer install`, `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test`
- [ ] Run on push to main and on PRs
- [ ] This pays for itself fast given the test count

### 3.10 README Oversells the Encryption Model
- [ ] README positions Keyora as a "secure, full-featured password and secrets management platform"
- [ ] Actual encryption is server-side via `Crypt::encryptString()` keyed off `APP_KEY` — the server can decrypt every secret. This is fine for the stated internal-tool scope, but it is not zero-knowledge
- [ ] Either: (a) tone down the README to match the internal-tool scope already documented in this TODO, or (b) if a real product is the goal, plan a client-side-encryption rewrite (Priority 4.6)
- [ ] Be explicit in README about the threat model so future readers don't mistake this for Bitwarden/1Password

### 3.11 BUILD_LOG.md Claims PHP 8.5.6
- [ ] `BUILD_LOG.md` project-info table says "PHP 8.5.6 (local)"
- [ ] PHP 8.5 does not exist as of writing — 8.4 is current. Likely a typo or a pre-release/RC version
- [ ] Verify `php -v` and correct the entry; also align with `composer.json` which requires `^8.3`

---

## Priority 4 — Future Modules (Post-Deploy)

These are features that could be added later based on real usage.

### 4.1 CSV/JSON Import
- [ ] Module 30 spec mentioned bulk CSV import as post-MVP
- [ ] Add `POST /api/v1/personal-vault/items/import` accepting CSV/JSON file
- [ ] Parse server-side, validate each row, return success/errors

### 4.2 CSV/JSON Export
- [ ] `GET /api/v1/personal-vault/items/export?format=csv`
- [ ] Stream the response for large datasets
- [ ] Require reauth (exporting credentials is sensitive)

### 4.3 Webhook Notifications
- [ ] Notify external systems on events (access granted, alert triggered)
- [ ] `POST /api/v1/webhooks` to register webhook URLs
- [ ] Sign payloads with HMAC

### 4.4 SSO Integration
- [ ] SAML or OIDC for internal company SSO
- [ ] Map SSO groups to tenant roles
- [ ] Auto-provision users on first login

### 4.5 Mobile App API Considerations
- [ ] Review token expiration policies for mobile clients
- [ ] Consider refresh token flow
- [ ] Document mobile-specific endpoints if needed

### 4.6 Zero-Knowledge Encryption (If Repositioning as a Product)
- [ ] Current `Encryptable` trait does server-side encryption with `APP_KEY` — the server can decrypt every secret
- [ ] If Keyora is ever repositioned from internal tool to a public/zero-knowledge password manager, this is a foundational rewrite, not a patch
- [ ] Approach: derive a per-user key from a master password (PBKDF2/Argon2 + salt), encrypt vault items client-side, store only ciphertext server-side
- [ ] Requires a frontend (currently TBD) to do the crypto — server never sees plaintext keys
- [ ] Re-evaluate the whole `AccessResolver` / sharing model: sharing requires re-encrypting secrets with recipient public keys (asymmetric crypto) or a shared team key
- [ ] This is a multi-month effort; only worth it if the product direction changes

---

## Done Items (for reference)

- [x] Modules 01-24 (core platform)
- [x] Module 25-26 (SaaS billing — intentionally skipped, open source)
- [x] Module 27 — Rate limiting
- [x] Module 28 — API documentation (Scramble)
- [x] Module 29 — Soft deletes consistency
- [x] Module 30 — Bulk operations
- [x] Module 31 — Dashboard caching
- [x] KEY-32 — Production readiness (Priority 1: 1.1-1.8, except 1.1 restore test)

---

## Notes

- This TODO is scoped for **internal deployment at ~100 staff**
- Items marked Priority 1 should be done before going live
- Items marked Priority 2 should be done within the first month
- Items marked Priority 3 are quality-of-life improvements
- Items marked Priority 4 are future features based on real usage
- Re-evaluate priorities after 3 months of production use
