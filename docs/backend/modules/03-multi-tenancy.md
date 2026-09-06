# Module 03 — Multi-Tenancy

| Field | Value |
|---|---|
| **Module** | 03 |
| **Name** | Multi-Tenancy |
| **Dependencies** | Module 01, Module 02 |
| **Status** | ✅ Complete |

---

## Objective

Implement the multi-tenant architecture using single-database `tenant_id` scoping. Create the `Tenant` model, `BelongsToTenant` trait, `TenantManager` service, and tenant resolution middleware. This is the foundation for all company-scoped features.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| CW-01 | User can create a company workspace (tenant) | P0 |
| (architectural) | All company data isolated by tenant_id | P0 |

---

## Tasks

### 3.1 Tenant Model & Migration

- [ ] Create `tenants` table migration:

```
tenants
  id              -- bigIncrements
  name            -- string
  slug            -- string, unique
  plan            -- string, default 'free'
  settings        -- json, nullable
  trial_ends_at   -- timestamp, nullable
  created_at
  updated_at
```

- [ ] Create `Tenant` model:
  - `$fillable`: `name`, `slug`, `plan`, `settings`, `trial_ends_at`
  - `$casts`: `settings` → `array`, `trial_ends_at` → `datetime`
  - Relationship: `users()` → `belongsToMany(User::class)` with pivot (`role`, `joined_at`, `left_at`)
  - Relationship: `teams()` → `hasMany(Team::class)` (added in Module 07)

### 3.2 Tenant-User Pivot Table

- [ ] Create `tenant_user` pivot migration:

```
tenant_user
  tenant_id       -- foreignId
  user_id         -- foreignId
  role            -- enum: 'owner', 'admin', 'member'
  joined_at       -- timestamp
  left_at         -- timestamp, nullable
  created_at
  updated_at

  unique(tenant_id, user_id)
  index(tenant_id, role)
```

### 3.3 Update User Model

- [ ] Add `tenants()` relationship: `belongsToMany(Tenant::class)` with pivot
- [ ] Add helper method: `isMemberOf(Tenant $tenant): bool`
- [ ] Add helper method: `roleIn(Tenant $tenant): ?string`
- [ ] Add helper method: `ownsTenant(Tenant $tenant): bool`

### 3.4 BelongsToTenant Trait

- [ ] Create `app/Traits/BelongsToTenant.php`:
  - Adds global scope filtering by `tenant_id` (from `TenantManager`)
  - Auto-sets `tenant_id` on model creation
  - Adds `tenant()` relationship method
  - Prevents querying without tenant context (throws exception if no tenant set)

### 3.5 TenantManager Service

- [ ] Create `app/Services/TenantManager.php`:
  - `setCurrentTenant(int $tenantId): void`
  - `currentTenantId(): ?int`
  - `hasCurrentTenant(): bool`
  - `forgetCurrentTenant(): void`
  - Register as singleton in `AppServiceProvider`

### 3.6 Tenant Resolution Middleware

- [ ] Create `app/Http/Middleware/ResolveTenant.php`:
  - Priority 1: Token's associated tenant (if token has `tenant_id`)
  - Priority 2: `X-Tenant-ID` header (verify user belongs to tenant)
  - If no tenant resolved, continue without (some endpoints are tenant-agnostic)
  - If tenant ID provided but user doesn't belong → `403`
- [ ] Register middleware in `app/Http/Kernel.php` as `tenant.resolve`

### 3.7 API Endpoints

| Method | Endpoint | Description | Auth | Tenant |
|---|---|---|---|---|
| `GET` | `/api/v1/tenants` | List user's workspaces | Yes | No |
| `POST` | `/api/v1/tenants` | Create new workspace | Yes | No |
| `GET` | `/api/v1/tenants/{tenant}` | Get workspace details | Yes | Yes |
| `PUT` | `/api/v1/tenants/{tenant}` | Update workspace | Yes | Yes (owner) |
| `DELETE` | `/api/v1/tenants/{tenant}` | Delete workspace | Yes | Yes (owner) |

### 3.8 Controller

- [ ] `app/Http/Controllers/Api/V1/TenantController.php`
  - `index()` — list tenants where user is a member
  - `store()` — create tenant, attach user as owner
  - `show()` — show tenant details (must be member)
  - `update()` — update name/settings (must be owner/admin)
  - `destroy()` — soft delete tenant (must be owner)

### 3.9 Form Requests

- [ ] `CreateTenantRequest`: `name` (required, string, max:255), `slug` (nullable, string, unique)
- [ ] `UpdateTenantRequest`: `name` (sometimes, string, max:255), `settings` (sometimes, array)

### 3.10 API Resource

- [ ] `app/Http/Resources/V1/TenantResource.php`
  - Fields: `id`, `name`, `slug`, `plan`, `settings`, `trial_ends_at`, `role` (current user's role), `created_at`

### 3.11 Policy

- [ ] `app/Policies/TenantPolicy.php`
  - `view()` — user must be a member of the tenant
  - `update()` — user must be owner or admin
  - `delete()` — user must be owner

### 3.12 Routes

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('tenants', TenantController::class);
});
```

---

## Acceptance Criteria

- [ ] User can create a tenant via `POST /api/v1/tenants` and becomes owner
- [ ] User can list their tenants via `GET /api/v1/tenants`
- [ ] User can view tenant details via `GET /api/v1/tenants/{id}` if member
- [ ] Non-member cannot view tenant → `403`
- [ ] Only owner/admin can update tenant
- [ ] Only owner can delete tenant
- [ ] `BelongsToTenant` trait auto-sets `tenant_id` on model creation
- [ ] `BelongsToTenant` global scope filters queries by current tenant
- [ ] Querying tenant-scoped models without a tenant context throws exception
- [ ] `X-Tenant-ID` header resolves tenant for the request
- [ ] Invalid `X-Tenant-ID` (user not a member) returns `403`
- [ ] `TenantManager` singleton holds current tenant for request lifecycle

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_user_can_create_tenant` | POST creates tenant, user is owner |
| `test_user_can_list_their_tenants` | GET returns only tenants user belongs to |
| `test_user_can_view_tenant_as_member` | GET returns tenant details |
| `test_non_member_cannot_view_tenant` | GET returns 403 |
| `test_only_owner_can_delete_tenant` | Member cannot delete, owner can |
| `test_belongs_to_tenant_auto_sets_tenant_id` | Creating model sets tenant_id |
| `test_tenant_scope_filters_by_current_tenant` | Queries only return current tenant's data |
| `test_querying_without_tenant_throws_exception` | No tenant context → exception |
| `test_tenant_resolution_via_header` | X-Tenant-ID header sets tenant |
| `test_invalid_tenant_id_returns_403` | Non-member tenant ID → 403 |

---

## What This Module Does NOT Include

- Team management (Module 07)
- Employee/member management (Module 04)
- Plan enforcement and limits (Module 25)
- Billing integration (Module 26)
