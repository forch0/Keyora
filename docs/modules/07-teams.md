# Module 07 — Teams & Team Vaults

| Field | Value |
|---|---|
| **Module** | 07 |
| **Name** | Teams & Team Vaults |
| **Dependencies** | Module 03, Module 04 |
| **Status** | Not Started |

---

## Objective

Create teams within a company workspace (tenant). Teams have their own shared vault. Employees can belong to multiple teams. This module establishes the team model and team-scoped vault items.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| CW-07 | Owner can create departments/teams | P0 |
| CW-09 | Employees can belong to multiple teams | P0 |
| CW-10 | Teams have their own shared vault | P0 |
| CW-11 | Organization-wide shared resources accessible to all members | P1 |

---

## Tasks

### 7.1 Teams Table & Model

- [ ] Create `teams` migration:

```
teams
  id              -- bigIncrements
  tenant_id       -- foreignId (constrained, cascadeOnDelete)
  name            -- string
  description     -- text, nullable
  color           -- string, nullable
  created_by      -- foreignId (users)
  created_at
  updated_at

  index(tenant_id)
  unique(tenant_id, name)
```

- [ ] Create `Team` model:
  - `use BelongsToTenant` trait
  - `$fillable`: `name`, `description`, `color`, `created_by`
  - Relationship: `tenant()` → `belongsTo(Tenant::class)`
  - Relationship: `members()` → `belongsToMany(User::class, 'team_user')` with pivot (`role`, `joined_at`)
  - Relationship: `vaultItems()` → `hasMany(VaultItem::class)` (team vault items — see 7.3)

### 7.2 Team-User Pivot Table

- [ ] Create `team_user` migration:

```
team_user
  team_id         -- foreignId (constrained, cascadeOnDelete)
  user_id         -- foreignId (constrained, cascadeOnDelete)
  role            -- enum: 'lead', 'member'
  joined_at       -- timestamp
  created_at
  updated_at

  unique(team_id, user_id)
  index(team_id, role)
  index(user_id)
```

### 7.3 Team Vault Items Table & Model

- [ ] Create `vault_items` migration (company/team-scoped, separate from personal):

```
vault_items
  id              -- bigIncrements
  tenant_id       -- foreignId (constrained, cascadeOnDelete)
  team_id         -- foreignId, nullable (constrained, nullOnDelete) — null = org-wide
  user_id         -- foreignId (constrained, cascadeOnDelete) — creator/owner
  name            -- string (plaintext, searchable)
  type            -- enum: 'password', 'api_key', 'server', 'database', 'note'
  username        -- text, nullable (ENCRYPTED)
  password        -- text, nullable (ENCRYPTED)
  url             -- string, nullable (plaintext, searchable)
  notes           -- text, nullable (ENCRYPTED)
  metadata        -- json, nullable
  custom_fields   -- json, nullable (ENCRYPTED)
  favorite        -- boolean, default false (per-user favorites handled separately)
  folder_id       -- foreignId, nullable
  created_at
  updated_at
  deleted_at      -- soft deletes

  index(tenant_id, team_id)
  index(tenant_id, team_id, type)
  index(tenant_id, user_id)
```

- [ ] Create `VaultItem` model:
  - `use BelongsToTenant` trait
  - `use Encryptable` trait
  - `use SoftDeletes`
  - `$encryptable = ['username', 'password', 'notes', 'custom_fields']`
  - Relationships: `tenant()`, `team()`, `user()` (creator), `folder()`

### 7.4 Team Vault Folders

- [ ] Create `vault_folders` migration (same structure as personal folders but tenant-scoped):

```
vault_folders
  id              -- bigIncrements
  tenant_id       -- foreignId (constrained, cascadeOnDelete)
  team_id         -- foreignId, nullable (constrained, nullOnDelete)
  name            -- string
  parent_id       -- foreignId, nullable (self-referencing)
  created_by      -- foreignId (users)
  created_at
  updated_at

  index(tenant_id, team_id)
  index(tenant_id, parent_id)
```

### 7.5 Team Vault Tags

- [ ] Create `vault_tags` migration (tenant-scoped):

```
vault_tags
  id              -- bigIncrements
  tenant_id       -- foreignId (constrained, cascadeOnDelete)
  name            -- string
  color           -- string, nullable
  created_at
  updated_at

  unique(tenant_id, name)
  index(tenant_id)
```

- [ ] Create `vault_item_tag` pivot

### 7.6 API Endpoints — Teams

| Method | Endpoint | Description | Role |
|---|---|---|---|
| `GET` | `/api/v1/tenants/{tenant}/teams` | List teams | Member |
| `POST` | `/api/v1/tenants/{tenant}/teams` | Create team | Admin/Owner |
| `GET` | `/api/v1/tenants/{tenant}/teams/{team}` | Get team details | Member |
| `PUT` | `/api/v1/tenants/{tenant}/teams/{team}` | Update team | Admin/Owner/TeamLead |
| `DELETE` | `/api/v1/tenants/{tenant}/teams/{team}` | Delete team | Admin/Owner |

### 7.7 API Endpoints — Team Members

| Method | Endpoint | Description | Role |
|---|---|---|---|
| `GET` | `/api/v1/tenants/{tenant}/teams/{team}/members` | List team members | Member |
| `POST` | `/api/v1/tenants/{tenant}/teams/{team}/members` | Add member to team | Admin/Owner/TeamLead |
| `PUT` | `/api/v1/tenants/{tenant}/teams/{team}/members/{user}` | Change team role | Admin/Owner/TeamLead |
| `DELETE` | `/api/v1/tenants/{tenant}/teams/{team}/members/{user}` | Remove from team | Admin/Owner/TeamLead |

### 7.8 API Endpoints — Team Vault Items

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/tenants/{tenant}/teams/{team}/vault/items` | List team vault items |
| `POST` | `/api/v1/tenants/{tenant}/teams/{team}/vault/items` | Create team vault item |
| `GET` | `/api/v1/tenants/{tenant}/teams/{team}/vault/items/{item}` | Get team vault item |
| `PUT` | `/api/v1/tenants/{tenant}/teams/{team}/vault/items/{item}` | Update team vault item |
| `DELETE` | `/api/v1/tenants/{tenant}/teams/{team}/vault/items/{item}` | Delete team vault item |

### 7.9 API Endpoints — Org-Wide Vault Items

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/tenants/{tenant}/vault/items` | List org-wide vault items (team_id = null) |
| `POST` | `/api/v1/tenants/{tenant}/vault/items` | Create org-wide vault item |
| `GET` | `/api/v1/tenants/{tenant}/vault/items/{item}` | Get org-wide vault item |
| `PUT` | `/api/v1/tenants/{tenant}/vault/items/{item}` | Update |
| `DELETE` | `/api/v1/tenants/{tenant}/vault/items/{item}` | Delete |

### 7.10 Controllers

- [ ] `TeamController.php` — CRUD for teams
- [ ] `TeamMemberController.php` — manage team membership
- [ ] `TeamVaultItemController.php` — CRUD for team vault items
- [ ] `OrgVaultItemController.php` — CRUD for org-wide vault items

### 7.11 Form Requests

- [ ] `CreateTeamRequest`: `name` (required, string, max:100), `description` (nullable, string, max:500), `color` (nullable, string, max:20)
- [ ] `AddTeamMemberRequest`: `user_id` (required, exists:users), `role` (required, in:lead,member)
- [ ] `CreateTeamVaultItemRequest`: same fields as personal vault item creation

### 7.12 API Resources

- [ ] `TeamResource.php` — `id`, `name`, `description`, `color`, `members_count`, `vault_items_count`, `created_at`
- [ ] `TeamMemberResource.php` — `id`, `name`, `email`, `role`, `joined_at`
- [ ] `VaultItemResource.php` — `id`, `name`, `type`, `username` (decrypted), `password` (decrypted), `url`, `notes` (decrypted), `metadata`, `custom_fields` (decrypted), `team`, `folder`, `tags`, `created_at`

### 7.13 Policies

- [ ] `TeamPolicy.php`:
  - `view()` — user must be member of tenant AND member of team (or admin/owner)
  - `create()` — user must be admin or owner of tenant
  - `update()` — admin, owner, or team lead
  - `delete()` — admin or owner
- [ ] `VaultItemPolicy.php`:
  - `view()` — user must be member of team (or have access grant — Module 08)
  - `create()` — user must be member of team
  - `update()` — creator or team lead or admin (or have edit permission — Module 08)
  - `delete()` — creator or admin (or have manage permission — Module 08)

---

## Acceptance Criteria

- [ ] Admin/owner can create a team within a tenant
- [ ] Admin/owner can add tenant members to a team
- [ ] A user can belong to multiple teams
- [ ] Team members can create vault items in the team vault
- [ ] Team members can view team vault items
- [ ] Non-team members cannot view team vault items → `403`
- [ ] Org-wide vault items (team_id = null) are accessible to all tenant members
- [ ] Team vault items are encrypted at rest (same as personal vault)
- [ ] Team lead can add/remove members from their team
- [ ] Admin/owner can delete a team (vault items moved to org-wide or deleted)
- [ ] All team data is tenant-scoped via `BelongsToTenant` trait

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_admin_can_create_team` | POST creates team |
| `test_member_cannot_create_team` | Non-admin → 403 |
| `test_can_add_member_to_team` | POST attaches user to team |
| `test_user_can_belong_to_multiple_teams` | User in 2+ teams |
| `test_team_member_can_create_vault_item` | POST creates item in team vault |
| `test_team_member_can_view_vault_item` | GET returns item |
| `test_non_team_member_cannot_view_vault_item` | Non-member → 403 |
| `test_org_wide_items_accessible_to_all_members` | All tenant members can view |
| `test_team_lead_can_add_members` | Team lead can add |
| `test_team_lead_can_remove_members` | Team lead can remove |
| `test_admin_can_delete_team` | DELETE removes team |
| `test_team_vault_items_are_encrypted` | DB values are encrypted |
| `test_tenant_scope_isolates_teams` | Tenant B cannot see Tenant A's teams |

---

## What This Module Does NOT Include

- Access grants and fine-grained permissions (Module 08, 09)
- Sharing with individuals outside the team (Module 09)
- Temporary access (Module 14)
- Activity logging (Module 20)
