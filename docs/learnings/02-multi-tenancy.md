# 03 — How Module 03 (Multi-Tenancy) Was Built

| Field | Value |
|---|---|
| **Module** | 03 — Multi-Tenancy |
| **Date** | 2026-09-01 |
| **Spec** | `docs/modules/03-multi-tenancy.md` |
| **Architecture ref** | `docs/ARCHITECTURE.md` §3 (Multi-Tenancy Implementation) |

---

## Goal

Implement single-database multi-tenant architecture using `tenant_id` scoping. Create the `Tenant` model, `BelongsToTenant` trait (fail-closed global scope), `TenantManager` singleton service, tenant resolution middleware, and the tenant CRUD API. This is the foundation for all company-scoped features.

---

## Decisions Made Before Writing Code

### 1. Build in-house, don't use a package

Researched 6 Laravel multi-tenancy packages. The well-known ones (`stancl/tenancy`, `spatie/laravel-multitenancy`) are built for database-per-tenant — wrong fit for ADR-002 (single database). The single-DB packages (`ubayedtanvir/laravel-tenancy`, `rylxes/laravel-multitenancy`) are new with low adoption — risky for a security-focused product. The architecture doc provides exact code for the trait, service, and middleware. The implementation is ~100 lines total. A dependency would reduce control over security-critical behavior and create friction with future modules that depend on the exact `TenantManager`/`BelongsToTenant` design.

### 2. Fail-closed trait (throws without tenant context)

The spec requires: "Querying tenant-scoped models without a tenant context throws exception." This is a security decision — a forgotten scope in dev/test should fail loudly, not silently leak cross-tenant data. The `withoutTenant()` scope provides an explicit opt-out for admin operations.

### 3. Soft deletes on tenants

The spec says `destroy()` = soft delete. This allows recovery of accidentally deleted workspaces and preserves audit trail integrity.

### 4. `status` column on tenant_user pivot

The `TenantHelper` from Module 01 already used `status: 'active'` when attaching users. Added the column to the pivot migration beyond what the spec listed. This supports future states (invited, inactive, suspended) needed by Module 04 (Company Workspace) and Module 22 (Employee Lifecycle).

---

## Files Created / Modified

### Migrations

| File | Purpose |
|---|---|
| `2026_09_01_140000_create_tenants_table.php` | tenants table (id, name, slug, plan, settings JSON, trial_ends_at, soft deletes) |
| `2026_09_01_140001_create_tenant_user_table.php` | Pivot table (tenant_id, user_id, role enum, status, joined_at, left_at, unique + index) |
| `2026_09_01_140002_add_tenant_id_to_personal_access_tokens.php` | Adds nullable tenant_id FK to tokens (for token-tenant association per §3.6 Priority 1) |
| `2026_09_01_140003_create_tenant_scoped_models_table.php` | Test-only table for verifying BelongsToTenant trait |

### Models

| File | What changed |
|---|---|
| `Tenant.php` | Created — fillable, casts (settings→array, trial_ends_at→datetime), SoftDeletes, users() belongsToMany with pivot |
| `User.php` | Modified — added tenants() belongsToMany, isMemberOf(), roleIn(), ownsTenant() helpers |
| `TenantScopedModel.php` | Created (test-only) — uses BelongsToTenant trait for trait behavior tests |

### Business Logic

| File | Purpose |
|---|---|
| `BelongsToTenant.php` (Trait) | Global scope (fail-closed), auto-sets tenant_id on create, tenant() relation, withoutTenant() scope |
| `TenantManager.php` (Service) | Singleton — holds current tenant ID for request lifecycle |
| `CreateTenantAction.php` (Action) | Creates tenant + generates unique slug + attaches user as owner |
| `ResolveTenant.php` (Middleware) | Resolves tenant from token (Priority 1) or X-Tenant-ID header (Priority 2, verifies membership) |
| `TenantPolicy.php` (Policy) | view (member), update (owner/admin), delete (owner) |

### HTTP Layer

| File | Purpose |
|---|---|
| `CreateTenantRequest.php` | Validates name (required), slug (nullable, unique) |
| `UpdateTenantRequest.php` | Validates name, slug, settings (all sometimes) |
| `TenantResource.php` | Transforms Tenant → JSON with role from pivot |
| `TenantController.php` | Thin controller — index, store, show, update, destroy |

### Other

| File | What changed |
|---|---|
| `AppServiceProvider.php` | Registered TenantManager as singleton |
| `routes/api.php` | Added 5 tenant endpoints with auth, tenant.resolve, and can: middleware |
| `TenantFactory.php` | Factory for test data — generates unique slugs |

---

## Request Flow (how tenant resolution works)

```
1. Client sends GET /api/v1/tenants/5
   Header: Authorization: Bearer 1|abc...
   Header: X-Tenant-ID: 5
        │
        ▼
2. Middleware stack:
   auth:sanctum → authenticates user via bearer token
        │
        ▼
3. tenant.resolve middleware:
   Priority 1: Check token's tenant_id (null → skip)
   Priority 2: Check X-Tenant-ID header (5)
     → Verify user belongs to tenant 5 (query tenant_user pivot)
     → If not member → abort(403)
     → If member → TenantManager::setCurrentTenant(5)
        │
        ▼
4. can:view,tenant middleware:
   → TenantPolicy::view(user, tenant)
   → Checks user->isMemberOf(tenant)
   → If false → abort(403)
        │
        ▼
5. TenantController::show(tenant)
   → Returns TenantResource (200)
```

---

## How BelongsToTenant Trait Works

```
Model using BelongsToTenant trait
    │
    ├── bootBelongsToTenant() called on model boot
    │
    ├── Global scope 'tenant':
    │   Every query gets WHERE tenant_id = {current_tenant_id}
    │   If no current tenant → throws RuntimeException (fail-closed)
    │
    ├── creating event:
    │   Auto-sets tenant_id from TenantManager if not already set
    │
    ├── tenant() relationship:
    │   belongsTo(Tenant::class)
    │
    └── withoutTenant() scope:
        Explicit bypass for admin/cross-tenant queries
        → withoutGlobalScope('tenant')
```

---

## All Endpoints

| Method | Endpoint | Auth | Tenant | Status | Description |
|---|---|---|---|---|---|
| GET | `/api/v1/tenants` | Yes | No | 200 | List user's workspaces |
| POST | `/api/v1/tenants` | Yes | No | 201 | Create new workspace (user becomes owner) |
| GET | `/api/v1/tenants/{tenant}` | Yes | Yes | 200 | Get workspace details (member only) |
| PUT | `/api/v1/tenants/{tenant}` | Yes | Yes | 200 | Update workspace (owner/admin only) |
| DELETE | `/api/v1/tenants/{tenant}` | Yes | Yes | 204 | Soft-delete workspace (owner only) |

---

## Bugs Found and Fixed During Implementation

### 1. `tenant_user` has no column named `status`

**Cause:** The `TenantHelper` trait (created in Module 01) attaches users with `status: 'active'`, but the pivot migration didn't include a `status` column.

**Fix:** Added `status` column to the pivot migration. This was needed for Module 04 (employee lifecycle) anyway, so it's not wasted work.

### 2. ResolveTenant return type error

**Cause:** The middleware's return type was `Illuminate\Http\Response`, but `abort(403)` returns a `JsonResponse`, which is not a subtype of `Response`.

**Fix:** Changed return type to `Symfony\Component\HttpFoundation\Response` (the parent class of both `Response` and `JsonResponse`).

### 3. `$builder->getTable()` undefined

**Cause:** In the BelongsToTenant trait's global scope, I called `$builder->getTable()`. But `Builder` doesn't have a `getTable()` method — that's on the `Model`.

**Fix:** Changed to `$builder->getModel()->getTable()`.

### 4. Owner delete test returns 403

**Cause:** Same `RequestGuard` caching bug from Module 02. The test makes two authenticated requests (member delete → 403, then owner delete → should be 204). The `RequestGuard` caches the first user in memory and doesn't reset between requests in the same test.

**Fix:** Added `Auth::forgetGuards()` between the two requests. This is now a known pattern — any test with multiple authenticated requests from different users needs this call.

### 5. PHPStan: 9 errors

Multiple PHPStan level 8 issues:

| Issue | Fix |
|---|---|
| `instanceof` always true on `PersonalAccessToken` | Removed redundant check, used `@var` assertion for nullable type |
| `$this->route('tenant')?->id` on mixed | Used `instanceof Tenant` check before accessing `->id` |
| `$this->pivot` undefined on Resource | Used `$this->resource->getRelation('pivot')` + `instanceof Pivot` check |
| `$pivot?->role` on string | Used `getRelation('pivot')` + `instanceof Pivot` + `getAttribute('role')` |
| `$model->tenant_id` undefined on `Model` | Used `getAttribute()` / `setAttribute()` |
| `BelongsToMany<User>` generic mismatch | Used full 4-param generic: `BelongsToMany<User, $this, Pivot, 'pivot'>` |

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — 72 files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 41 tests, 125 assertions (10 new + 31 from Module 02) |

---

## What This Module Does NOT Include

Per the module spec, these are deferred to later modules:

- Team management → Module 07
- Employee/member management → Module 04
- Plan enforcement and limits → Module 25
- Billing integration → Module 26
- Token-to-tenant association in production flows → Module 04+ (schema is ready, not yet used)

---

## Key Takeaways

1. **Read the architecture doc before researching packages** — the architecture doc was prescriptive about the exact implementation. No package would have matched it better than writing it ourselves.

2. **Fail-closed is a security feature** — the trait throwing without tenant context feels annoying during development, but it's the right default for a password manager. A forgotten scope should fail loudly, not silently leak data.

3. **PHPStan level 8 with Eloquent is hard but worth it** — accessing pivot data, dynamic properties, and generic relationship types all require careful type assertions. The effort pays off in catching bugs at static analysis time rather than runtime.

4. **The `Auth::forgetGuards()` pattern recurs** — any test with multiple authenticated requests from different users hits this. It's now documented twice (Module 02 and 03). Future modules should expect it.

5. **Test-only models are valid** — creating a `TenantScopedModel` solely for testing the `BelongsToTenant` trait is cleaner than testing the trait through a real model that has other concerns. It isolates the trait behavior.

---

## Build Order — Files Created A to Z

Following the bottom-up pattern established in Module 02:

```
 1. Migration          → 2026_09_01_140000_create_tenants_table.php
 2. Migration          → 2026_09_01_140001_create_tenant_user_table.php
 3. Migration          → 2026_09_01_140002_add_tenant_id_to_personal_access_tokens.php
 4. Migration          → 2026_09_01_140003_create_tenant_scoped_models_table.php (test-only)
    → php artisan migrate:fresh
 5. Factory            → TenantFactory.php
 6. Model              → Tenant.php
 7. Model              → User.php (modified — added relationships + helpers)
 8. Model              → TenantScopedModel.php (test-only)
 9. Trait              → BelongsToTenant.php
10. Service            → TenantManager.php
11. Provider           → AppServiceProvider.php (modified — register singleton)
12. Middleware         → ResolveTenant.php
13. Action             → CreateTenantAction.php
14. Form Request       → CreateTenantRequest.php
15. Form Request       → UpdateTenantRequest.php
16. API Resource       → TenantResource.php
17. Policy             → TenantPolicy.php
18. Controller         → TenantController.php
19. Routes             → routes/api.php (modified — added 5 tenant endpoints)
20. Test               → TenantCrudTest.php (7 tests)
21. Test               → BelongsToTenantTest.php (3 tests)
22. Verification       → pint → phpstan → php artisan test
```
