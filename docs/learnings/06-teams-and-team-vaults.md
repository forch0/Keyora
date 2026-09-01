# 07 — How Module 07 (Teams & Team Vaults) Was Built

| Field | Value |
|---|---|
| **Module** | 07 — Teams & Team Vaults |
| **Date** | 2026-09-01 |
| **Spec** | `docs/modules/07-teams.md` |
| **Architecture ref** | `docs/ARCHITECTURE.md` §1.2 (Multi-Tenancy), §1.3 (Encryption) |

---

## Goal

Create teams within a company workspace (tenant). Teams have their own shared vault. Employees can belong to multiple teams. This module establishes the team model, team-scoped vault items, and org-wide vault items.

---

## Decisions Made Before Writing Code

### 1. All business logic in Actions

Per the architecture spec, controllers are thin and delegate to Actions. Created 10 Actions:
- `CreateTeamAction`, `UpdateTeamAction`, `DeleteTeamAction`
- `AddTeamMemberAction`, `UpdateTeamMemberAction`, `RemoveTeamMemberAction`
- `CreateTeamVaultItemAction`, `CreateOrgVaultItemAction`
- `UpdateTeamVaultItemAction`, `DeleteTeamVaultItemAction`

### 2. Route binding for tenant-scoped models

The `BelongsToTenant` trait's global scope throws when there's no tenant context. During route model binding, the tenant context isn't set yet (middleware runs after binding). Added `resolveRouteBinding()` to the trait that bypasses the scope. The tenant check is enforced in the controller.

### 3. Parent parameter required for nested route binding

Laravel's implicit route binding requires the parent parameter (`Tenant $tenant`) to be in the controller method signature for nested parameters (`Team $team`) to resolve correctly. Without it, the child parameter is passed as a raw string.

### 4. Delete team moves items to org-wide

Spec: "vault items moved to org-wide or deleted". Chose to preserve data by setting `team_id = null` on all vault items before deleting the team.

### 5. Org-wide items accessible to all tenant members

`team_id = null` means the item is shared across the entire tenant. Any active tenant member can view org-wide items.

---

## Files Created / Modified

### Migrations

| File | Purpose |
|---|---|
| `2026_09_01_180000_create_teams_table.php` | Teams with tenant_id, name, description, color, created_by |
| `2026_09_01_180001_create_team_user_table.php` | Pivot with role (lead/member), joined_at |
| `2026_09_01_180002_create_vault_folders_table.php` | Tenant-scoped folders for team/org vault |
| `2026_09_01_180003_create_vault_items_table.php` | Team/org vault items with encrypted fields, soft deletes |
| `2026_09_01_180004_create_vault_tags_table.php` | Tenant-scoped tags + vault_item_tag pivot |

### Models

| File | What changed |
|---|---|
| `Team.php` | Created — BelongsToTenant, user/creator/members/vaultItems |
| `VaultItem.php` | Created — BelongsToTenant + Encryptable + SoftDeletes, orgWide scope |
| `VaultFolder.php` | Created — BelongsToTenant, tenant/team/creator/parent/children/items |
| `VaultTag.php` | Created — BelongsToTenant, tenant/items |
| `Tenant.php` | Modified — teams(), vaultItems(), resolveChildRouteBinding() |
| `User.php` | Modified — teams(), isTeamMember(), teamRole(), isTeamLead() |
| `BelongsToTenant.php` | Modified — resolveRouteBinding() bypasses tenant scope |
| `Encryptable.php` | Modified — simplified isEncryptable() (removed property_exists check) |

### Actions (10)

| File | Purpose |
|---|---|
| `CreateTeamAction.php` | Create team, attach creator as lead |
| `UpdateTeamAction.php` | Update team attributes |
| `DeleteTeamAction.php` | Move items to org-wide, delete team |
| `AddTeamMemberAction.php` | Validate tenant membership, attach to team |
| `UpdateTeamMemberAction.php` | Update pivot role |
| `RemoveTeamMemberAction.php` | Detach from team |
| `CreateTeamVaultItemAction.php` | Create vault item in team context |
| `CreateOrgVaultItemAction.php` | Create org-wide vault item (team_id = null) |
| `UpdateTeamVaultItemAction.php` | Update vault item attributes |
| `DeleteTeamVaultItemAction.php` | Soft delete vault item |

### HTTP Layer

| File | Purpose |
|---|---|
| 6 Form Requests | Team CRUD, member management, vault item CRUD |
| 3 API Resources | TeamResource, TeamMemberResource, VaultItemResource |
| 2 Policies | TeamPolicy, VaultItemPolicy |
| 4 Controllers | TeamController, TeamMemberController, TeamVaultItemController, OrgVaultItemController |

### Routes

19 new routes under `tenants/{tenant}/teams/*` and `tenants/{tenant}/vault/*`.

---

## All New Endpoints

19 endpoints covering:
- Team CRUD (5)
- Team member management (4)
- Team vault item CRUD (5)
- Org-wide vault item CRUD (5)

---

## Bugs Found and Fixed During Implementation

### 1. Route binding returns string instead of Model

**Cause:** `BelongsToTenant` global scope throws during route binding (no tenant context). Laravel falls back to raw string.

**Fix:** Added `resolveRouteBinding()` to the trait that bypasses the scope. Also added `Tenant $tenant` parameter to controller methods (Laravel requires parent parameter for nested binding).

### 2. `resolveChildRouteBinding` fails on Tenant

**Cause:** `getRelated()` returns `class-string|object` in PHPStan.

**Fix:** Added `@var Model` annotation to narrow the type.

### 3. `User::find()` returns `User|Collection|null`

**Cause:** PHPStan sees `find()` as potentially returning a Collection.

**Fix:** Used `User::query()->find()` with `instanceof User` check.

### 4. `property_exists` always true in Encryptable

**Cause:** Both VaultItem and PersonalVaultItem declare `$encryptable`, so PHPStan knows it always exists.

**Fix:** Removed the `property_exists` check entirely — models using the trait must declare `$encryptable`.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 104 tests, 306 assertions (13 new + 91 from Modules 02-06) |

---

## Key Takeaways

1. **Route binding + global scopes** — tenant-scoped models need `resolveRouteBinding()` that bypasses the global scope, because route binding happens before middleware sets the tenant context.

2. **Parent parameter for nested binding** — Laravel's implicit route binding requires the parent parameter to be in the controller signature for child parameters to resolve.

3. **Actions pattern** — keeping all business logic in Actions makes controllers thin and testable. 10 Actions for this module.

4. **Same encryption pattern** — VaultItem uses the same Encryptable trait + custom_fields encryption as PersonalVaultItem. Consistency across personal and team vaults.

5. **Org-wide = team_id null** — org-wide vault items are simply vault items with `team_id = null`. Any tenant member can view them. This avoids a separate table or model.
