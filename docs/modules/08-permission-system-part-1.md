# Module 08 — Permission System: Part 1 (Core)

| Field | Value |
|---|---|
| **Module** | 08 |
| **Name** | Permission System — Core (Access Grants Model & AccessResolver) |
| **Dependencies** | Module 02, Module 03, Module 07 |
| **Status** | Not Started |

---

## Objective

Build the polymorphic access grant system and the `AccessResolver` service. This is the foundation for all sharing, temporary access, and permission checks across the platform.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| TP-05 | Permissions: View (read-only) | P0 |
| TP-06 | Permissions: Edit (modify content) | P0 |
| TP-07 | Permissions: Share (can re-share) | P1 |
| TP-08 | Permissions: Download (can download files) | P1 |
| TP-09 | Permissions: Manage (full control) | P1 |
| TP-10 | User can view who has access to a specific resource | P0 |
| TP-11 | User can view how someone received access | P1 |

---

## Tasks

### 8.1 Access Grants Table & Model

- [ ] Create `access_grants` migration:

```
access_grants
  id                  -- bigIncrements
  tenant_id           -- foreignId (constrained, cascadeOnDelete)
  grantable_type      -- string (morphs: 'VaultItem', 'SecureFile', 'SecureNote', 'Folder')
  grantable_id        -- unsignedBigInteger
  subject_type        -- string (morphs: 'User', 'Team', 'Tenant')
  subject_id          -- unsignedBigInteger
  permission          -- enum: 'view', 'download', 'edit', 'share', 'manage'
  expires_at          -- timestamp, nullable (null = permanent)
  max_views           -- integer, nullable (null = unlimited)
  views_count         -- integer, default 0
  starts_at           -- timestamp, nullable (null = immediate)
  start_on_first_view -- boolean, default false
  first_viewed_at     -- timestamp, nullable
  granted_by          -- foreignId (users)
  revoked_at          -- timestamp, nullable
  revoked_by          -- foreignId, nullable (users)
  revoke_reason       -- string, nullable
  created_at
  updated_at

  index(tenant_id)
  index(grantable_type, grantable_id)
  index(subject_type, subject_id)
  index(expires_at)
  index(revoked_at)
```

- [ ] Create `AccessGrant` model:
  - `use BelongsToTenant` trait
  - `$fillable`: all columns above
  - `$casts`: `expires_at` → `datetime`, `starts_at` → `datetime`, `first_viewed_at` → `datetime`, `revoked_at` → `datetime`, `start_on_first_view` → `boolean`, `max_views` → `integer`, `views_count` → `integer`
  - Relationship: `grantable()` → `morphTo()`
  - Relationship: `subject()` → `morphTo()`
  - Relationship: `grantedBy()` → `belongsTo(User::class, 'granted_by')`
  - Scope: `scopeActive(Builder)` — where `revoked_at IS NULL` and not expired
  - Scope: `scopeExpired(Builder)` — where `expires_at <= now()` and `revoked_at IS NULL`

### 8.2 Permission Enum

- [ ] Create `app/Enums/Permission.php`:

```php
enum Permission: string
{
    case View = 'view';
    case Download = 'download';
    case Edit = 'edit';
    case Share = 'share';
    case Manage = 'manage';

    public function satisfies(Permission $required): bool
    {
        return $this->rank() >= $required->rank();
    }

    public function rank(): int
    {
        return match($this) {
            self::View => 1,
            self::Download => 2,
            self::Edit => 2,
            self::Share => 3,
            self::Manage => 4,
        };
    }

    public static function hierarchy(): array
    {
        return ['view', 'download', 'edit', 'share', 'manage'];
    }
}
```

Note: `download` and `edit` are both rank 2 — they're parallel, not cumulative with each other. `manage` satisfies all. `share` satisfies `view`. `edit` satisfies `view`. `download` satisfies `view`.

### 8.3 AccessResolver Service

- [ ] Create `app/Services/AccessResolver.php`:

```php
class AccessResolver
{
    public function can(User $user, Permission $required, Model $resource): bool
    public function getPermission(User $user, Model $resource): ?Permission
    public function whoHasAccess(Model $resource): Collection
    public function whatDoesUserHaveAccessTo(User $user): Collection
}
```

**`can()` algorithm:**
1. Check if user is the owner/creator of the resource (`user_id` column) → return `true`
2. Get all active (non-revoked, non-expired) grants for this resource
3. Filter grants where subject is:
   - This user directly (`subject_type = 'User'`, `subject_id = user.id`)
   - A team the user belongs to (`subject_type = 'Team'`, `subject_id IN user.team_ids`)
   - The tenant itself (`subject_type = 'Tenant'`, `subject_id = current_tenant_id`)
4. Take the highest permission from matching grants
5. Check temporal constraints:
   - `starts_at` is null or in the past
   - If `start_on_first_view` is true, check `first_viewed_at` is set and time hasn't expired
   - `expires_at` is null or in the future
6. Check view constraints:
   - `max_views` is null or `views_count < max_views`
7. Return whether the resolved permission satisfies the required permission

**`getPermission()` returns the highest permission the user has, or null.**

**`whoHasAccess()` returns all active grants for a resource with subject details.**

**`whatDoesUserHaveAccessTo()` returns all resources the user has access to (union of direct, team, and tenant grants).**

### 8.4 Register AccessResolver

- [ ] Register as a scoped singleton in `AppServiceProvider` (resolves per request with tenant context)

### 8.5 Update Policies to Use AccessResolver

- [ ] Update `VaultItemPolicy.php` to use `AccessResolver::can()` for view/update/delete checks
- [ ] Any policy that needs permission checks should delegate to `AccessResolver`

### 8.6 API Endpoints — View Access

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/vault/items/{item}/access` | List all access grants for an item |
| `GET` | `/api/v1/vault/items/{item}/access/summary` | Summary of who has access (grouped by type) |

### 8.7 Controller

- [ ] `AccessGrantController.php` (read-only in this module):
  - `index()` — list all access grants for a resource (who has access)
  - `summary()` — grouped summary (direct users, teams, company-wide)

### 8.8 API Resource

- [ ] `AccessGrantResource.php`:
  - `id`, `subject_type`, `subject_id`, `subject_name` (resolved), `permission`, `expires_at`, `max_views`, `views_count`, `starts_at`, `start_on_first_view`, `granted_by` (name), `created_at`

---

## Acceptance Criteria

- [ ] `AccessGrant` model stores polymorphic grants with all temporal/view constraint fields
- [ ] `Permission` enum correctly implements the hierarchy and `satisfies()` method
- [ ] `AccessResolver::can()` returns `true` for resource owner
- [ ] `AccessResolver::can()` returns `true` when user has a direct grant with sufficient permission
- [ ] `AccessResolver::can()` returns `true` when user's team has a grant with sufficient permission
- [ ] `AccessResolver::can()` returns `true` when tenant-wide grant exists and user is a member
- [ ] `AccessResolver::can()` returns `false` when grant is expired
- [ ] `AccessResolver::can()` returns `false` when grant is revoked
- [ ] `AccessResolver::can()` returns `false` when view limit is reached
- [ ] `AccessResolver::can()` returns `false` when start time hasn't arrived yet
- [ ] `AccessResolver::getPermission()` returns the highest permission from all matching grants
- [ ] `AccessResolver::whoHasAccess()` returns all active grants for a resource
- [ ] User can view who has access to a resource via API
- [ ] User can see how access was granted (direct, team, company-wide)
- [ ] `AccessGrant` is tenant-scoped via `BelongsToTenant` trait

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_owner_has_full_access` | Owner can do anything without explicit grant |
| `test_direct_grant_works` | User with direct 'view' grant can view |
| `test_team_grant_works` | Team member can access team-granted resource |
| `test_tenant_grant_works` | Tenant member can access tenant-granted resource |
| `test_expired_grant_denied` | Expired grant → access denied |
| `test_revoked_grant_denied` | Revoked grant → access denied |
| `test_view_limit_enforced` | max_views reached → access denied |
| `test_start_time_respected` | Future starts_at → access denied |
| `test_start_on_first_view` | Access only starts after first view |
| `test_permission_hierarchy` | 'manage' satisfies 'view', 'edit', etc. |
| `test_download_and_edit_are_parallel` | 'download' doesn't satisfy 'edit' and vice versa |
| `test_highest_permission_wins` | Multiple grants → highest permission returned |
| `test_who_has_access_returns_all_grants` | All active grants listed |
| `test_revoked_grants_excluded_from_list` | Revoked grants not in whoHasAccess |
| `test_tenant_scope_isolates_grants` | Tenant B can't see Tenant A's grants |

---

## What This Module Does NOT Include

- Creating/granting access (Module 09)
- Revoking access (Module 16)
- Temporary access with durations (Module 14)
- Access requests workflow (Module 15)
- Secure external sharing links (Module 17, 18)
