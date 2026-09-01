# Module 04 — Company Workspace

| Field | Value |
|---|---|
| **Module** | 04 |
| **Name** | Company Workspace |
| **Dependencies** | Module 02, Module 03 |
| **Status** | ✅ Complete |

---

## Objective

Build the company workspace member management system on top of the tenant model. This covers inviting employees, managing roles, suspending/removing members, and employee profiles.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| CW-01 | User can create a company workspace | P0 |
| CW-02 | Workspace owner can edit company profile | P1 |
| CW-03 | Owner can invite employees via email | P0 |
| CW-04 | Owner can remove employees from workspace | P0 |
| CW-05 | Owner can suspend employees | P1 |
| CW-06 | Each employee has a profile | P1 |
| CW-09 | Employees can belong to multiple teams | P0 |
| CW-11 | Organization-wide shared resources accessible to all members | P1 |

---

## Tasks

### 4.1 Invitations Table & Model

- [ ] Create `tenant_invitations` migration:

```
tenant_invitations
  id              -- bigIncrements
  tenant_id       -- foreignId
  email           -- string
  role            -- enum: 'admin', 'member'
  token           -- string, unique (uuid)
  invited_by      -- foreignId (users)
  accepted_at     -- timestamp, nullable
  expires_at      -- timestamp
  created_at
  updated_at

  index(tenant_id)
  index(email)
  unique(token)
```

- [ ] Create `TenantInvitation` model with `$fillable`, relationships

### 4.2 Update Tenant-User Pivot

- [ ] Add `status` column to `tenant_user` pivot: `enum('active', 'suspended', 'left')` default `active`
- [ ] Add `suspended_at` timestamp (nullable) to pivot

### 4.3 API Endpoints

| Method | Endpoint | Description | Auth | Role |
|---|---|---|---|---|
| `GET` | `/api/v1/tenants/{tenant}/members` | List all members | Yes | Member |
| `POST` | `/api/v1/tenants/{tenant}/members/invite` | Invite employee | Yes | Admin/Owner |
| `POST` | `/api/v1/tenants/{tenant}/members/accept` | Accept invitation | Yes | Any user |
| `GET` | `/api/v1/tenants/{tenant}/members/{user}` | View member profile | Yes | Member |
| `PUT` | `/api/v1/tenants/{tenant}/members/{user}` | Change member role | Yes | Admin/Owner |
| `POST` | `/api/v1/tenants/{tenant}/members/{user}/suspend` | Suspend member | Yes | Admin/Owner |
| `POST` | `/api/v1/tenants/{tenant}/members/{user}/restore` | Restore suspended member | Yes | Admin/Owner |
| `DELETE` | `/api/v1/tenants/{tenant}/members/{user}` | Remove member | Yes | Admin/Owner |
| `GET` | `/api/v1/tenants/{tenant}/invitations` | List pending invitations | Yes | Admin/Owner |
| `DELETE` | `/api/v1/tenants/{tenant}/invitations/{invitation}` | Cancel invitation | Yes | Admin/Owner |

### 4.4 Controller

- [ ] `app/Http/Controllers/Api/V1/TenantMemberController.php`
  - `index()` — list members with role, status, joined date
  - `invite()` — create invitation, send email
  - `accept()` — accept invitation using token, attach user to tenant
  - `show()` — view member profile (name, email, role, teams, joined date)
  - `update()` — change member role (member ↔ admin; owner transfer is special)
  - `suspend()` — set status to `suspended`, set `suspended_at`
  - `restore()` — set status back to `active`, clear `suspended_at`
  - `destroy()` — remove member from tenant (set `left_at`, status `left`)
  - `invitations()` — list pending invitations
  - `cancelInvitation()` — delete pending invitation

### 4.5 Actions

- [ ] `app/Actions/InviteEmployeeAction.php`
  - Generate invitation token
  - Create `TenantInvitation` record
  - Dispatch `SendInviteEmail` job
  - Return invitation model
- [ ] `app/Actions/AcceptInvitationAction.php`
  - Validate token is valid and not expired
  - Attach user to tenant with specified role
  - Mark invitation as accepted
  - Return tenant

### 4.6 Form Requests

- [ ] `InviteMemberRequest`: `email` (required, email), `role` (required, in:admin,member)
- [ ] `AcceptInvitationRequest`: `token` (required, uuid)
- [ ] `UpdateMemberRequest`: `role` (required, in:admin,member)

### 4.7 API Resources

- [ ] `app/Http/Resources/V1/TenantMemberResource.php`
  - Fields: `id`, `name`, `email`, `role`, `status`, `joined_at`, `teams` (count or list)
- [ ] `app/Http/Resources/V1/TenantInvitationResource.php`
  - Fields: `id`, `email`, `role`, `invited_by`, `accepted_at`, `expires_at`, `created_at`

### 4.8 Policy

- [ ] `app/Policies/TenantMemberPolicy.php`
  - `view()` — user must be a member of the tenant
  - `invite()` — user must be admin or owner
  - `update()` — user must be admin or owner; cannot change owner role
  - `suspend()` — user must be admin or owner; cannot suspend owner
  - `remove()` — user must be admin or owner; cannot remove owner

### 4.9 Notification

- [ ] `app/Notifications/EmployeeInvitation.php`
  - Sent to invitee's email
  - Contains invitation token / acceptance link
  - Includes tenant name and inviter name

### 4.10 Job

- [ ] `app/Jobs/SendInviteEmail.php` (implements `ShouldQueue`)
  - Sends the invitation notification email asynchronously

### 4.11 Routes

```php
Route::middleware(['auth:sanctum', 'tenant.resolve'])
    ->prefix('v1/tenants/{tenant}')
    ->group(function () {
        Route::get('members', [TenantMemberController::class, 'index']);
        Route::post('members/invite', [TenantMemberController::class, 'invite']);
        Route::post('members/accept', [TenantMemberController::class, 'accept']);
        Route::get('members/{user}', [TenantMemberController::class, 'show']);
        Route::put('members/{user}', [TenantMemberController::class, 'update']);
        Route::post('members/{user}/suspend', [TenantMemberController::class, 'suspend']);
        Route::post('members/{user}/restore', [TenantMemberController::class, 'restore']);
        Route::delete('members/{user}', [TenantMemberController::class, 'destroy']);
        Route::get('invitations', [TenantMemberController::class, 'invitations']);
        Route::delete('invitations/{invitation}', [TenantMemberController::class, 'cancelInvitation']);
    });
```

---

## Acceptance Criteria

- [ ] Admin/owner can invite a user via email → invitation created, email queued
- [ ] User can accept invitation with valid token → becomes tenant member with specified role
- [ ] User cannot accept expired invitation → `422`
- [ ] User cannot accept already-accepted invitation → `422`
- [ ] Members can view list of all members in their tenant
- [ ] Members can view other members' profiles
- [ ] Admin/owner can change a member's role (member ↔ admin)
- [ ] Admin/owner cannot change the owner's role → `403`
- [ ] Admin/owner can suspend a member → status becomes `suspended`
- [ ] Suspended member cannot access tenant resources → `403`
- [ ] Admin/owner can restore a suspended member → status becomes `active`
- [ ] Admin/owner can remove a member → `left_at` set, status `left`
- [ ] Admin/owner cannot remove the owner → `403`
- [ ] Admin/owner can list pending invitations
- [ ] Admin/owner can cancel a pending invitation
- [ ] Non-admin cannot invite/suspend/remove → `403`

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_admin_can_invite_member` | Invitation created, email job dispatched |
| `test_member_cannot_invite` | Non-admin gets 403 |
| `test_user_can_accept_invitation` | Acceptance attaches user to tenant |
| `test_cannot_accept_expired_invitation` | Expired token → 422 |
| `test_cannot_accept_already_accepted` | Re-acceptance → 422 |
| `test_members_can_list_members` | GET returns all members |
| `test_can_view_member_profile` | GET returns member details |
| `test_admin_can_change_role` | PUT updates role |
| `test_cannot_change_owner_role` | PUT on owner → 403 |
| `test_admin_can_suspend_member` | POST sets suspended status |
| `test_suspended_member_cannot_access_tenant` | Suspended user gets 403 |
| `test_admin_can_restore_member` | POST restores active status |
| `test_admin_can_remove_member` | DELETE sets left_at |
| `test_cannot_remove_owner` | DELETE on owner → 403 |
| `test_can_list_pending_invitations` | GET returns pending invitations |
| `test_can_cancel_invitation` | DELETE removes pending invitation |

---

## What This Module Does NOT Include

- Team creation/management (Module 07)
- Employee onboarding flow with 2FA setup (Module 22)
- Employee offboarding with access revocation (Module 16, 22)
- Access grants or permissions (Module 08, 09)
- Activity logging (Module 20)
