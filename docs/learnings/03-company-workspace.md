# 03 — How Module 04 (Company Workspace) Was Built

| Field | Value |
|---|---|
| **Module** | 04 — Company Workspace |
| **Date** | 2026-09-01 |
| **Spec** | `docs/modules/04-company-workspace.md` |
| **Architecture ref** | `docs/ARCHITECTURE.md` §3 (Multi-Tenancy) |

---

## Goal

Build the company workspace member management system on top of the tenant model from Module 03. This covers inviting employees via email, managing roles (member/admin/owner), suspending/restoring members, removing members, and listing/canceling pending invitations.

---

## Decisions Made Before Writing Code

### 1. Gates instead of model policy for TenantMemberPolicy

The `can:` middleware resolves the policy class by looking up the model class of the first argument. Both `TenantPolicy` (Module 03) and `TenantMemberPolicy` (Module 04) operate on `Tenant` — only one can be registered as the model policy. So I registered `TenantMemberPolicy` methods as named gates in `AppServiceProvider` and called `$this->authorize('member.invite', $tenant)` from the controller.

### 2. `$this->authorize()` in controller instead of `can:` middleware

The `can:` middleware with multiple route parameters (`can:suspend,tenant,user`) was ambiguous — it resolved to `TenantPolicy` instead of `TenantMemberPolicy`. Calling `$this->authorize('member.suspend', [$tenant, $user])` in the controller is explicit: the gate name is clear, and the arguments are passed directly.

### 3. `accept` endpoint has no membership check

The whole point of accepting an invitation is that the user is NOT yet a member. The invitation token itself is the authorization. Adding a `member.view` check would block the exact users who need this endpoint.

### 4. `isMemberOf()` now checks `status='active'`

In Module 03, `isMemberOf()` only checked `left_at IS NULL`. Now it also checks `status='active'`, which means suspended members automatically fail all membership checks. This is the correct behavior — a suspended member shouldn't be able to view tenants, list members, or access any tenant-scoped resource.

---

## Files Created / Modified

### Migrations

| File | Purpose |
|---|---|
| `2026_09_01_150000_create_tenant_invitations_table.php` | tenant_invitations table (id, tenant_id, email, role, token UUID, invited_by, accepted_at, expires_at) |
| `2026_09_01_150001_update_tenant_user_pivot_status.php` | Changes status to enum (active/suspended/left), adds suspended_at |

### Models

| File | What changed |
|---|---|
| `TenantInvitation.php` | Created — fillable, casts, tenant()/inviter() relations, isAccepted/isExpired/isPending helpers |
| `Tenant.php` | Modified — added status/suspended_at to pivot, activeMembers() scope, invitations() hasMany |
| `User.php` | Modified — isMemberOf() checks status='active', added isAdminOf(), statusIn() |

### Business Logic

| File | Purpose |
|---|---|
| `InviteEmployeeAction.php` | Generates UUID token, creates invitation, dispatches SendInviteEmail job |
| `AcceptInvitationAction.php` | Validates token (not expired/accepted), attaches user to tenant, marks accepted |
| `TenantMemberPolicy.php` | view (member), invite (admin/owner), update/suspend/restore/remove (admin/owner, not owner), manageInvitations (admin/owner) |
| `ResolveTenant.php` | Modified — blocks suspended members with 403, extracted ensureActiveMember() |

### HTTP Layer

| File | Purpose |
|---|---|
| `EmployeeInvitation.php` (Notification) | Email notification with tenant name, inviter name, token link |
| `SendInviteEmail.php` (Job) | Queued job that sends the notification via anonymous notifiable |
| `InviteMemberRequest.php` | Validates email (required), role (in:admin,member) |
| `AcceptInvitationRequest.php` | Validates token (required, uuid) |
| `UpdateMemberRequest.php` | Validates role (required, in:admin,member) |
| `TenantMemberResource.php` | id, name, email, role, status, joined_at, suspended_at, teams_count |
| `TenantInvitationResource.php` | id, email, role, invited_by, accepted_at, expires_at |
| `TenantMemberController.php` | 10 methods: index, invite, accept, show, update, suspend, restore, destroy, invitations, cancelInvitation |

### Other

| File | What changed |
|---|---|
| `Controller.php` | Added `AuthorizesRequests` trait (Laravel 13 doesn't include it by default) |
| `AppServiceProvider.php` | Registered 7 gates delegating to TenantMemberPolicy |
| `routes/api.php` | Added 10 member/invitation routes nested under tenants/{tenant} |
| `TenantHelper.php` | Added createInvitation() helper, attachUserToTenant() accepts extra attributes |

---

## Request Flow (how an invitation works)

```
1. Admin sends POST /api/v1/tenants/{tenant}/members/invite
   Body: { "email": "newuser@example.com", "role": "member" }
        │
        ▼
2. Middleware: auth:sanctum → tenant.resolve → sets current tenant
        │
        ▼
3. Controller: TenantMemberController::invite()
   → $this->authorize('member.invite', $tenant) → Gate → TenantMemberPolicy::invite()
   → Delegates to InviteEmployeeAction
        │
        ▼
4. InviteEmployeeAction:
   → Creates TenantInvitation (UUID token, 7-day expiry)
   → Dispatches SendInviteEmail job (queued)
   → Returns invitation
        │
        ▼
5. Response: 201 with TenantInvitationResource

--- Later ---

6. Invitee sends POST /api/v1/tenants/{tenant}/members/accept
   Body: { "token": "uuid-from-email" }
        │
        ▼
7. Controller: TenantMemberController::accept()
   → NO membership check (user is not yet a member)
   → Delegates to AcceptInvitationAction
        │
        ▼
8. AcceptInvitationAction:
   → Finds invitation by token
   → Checks: not accepted, not expired
   → Attaches user to tenant with invited role
   → Marks invitation as accepted
   → Returns tenant
        │
        ▼
9. Response: 200 with tenant_id and welcome message
```

---

## All Endpoints

| Method | Endpoint | Auth | Role | Status | Description |
|---|---|---|---|---|---|
| GET | `/api/v1/tenants/{tenant}/members` | Yes | Member | 200 | List all members |
| POST | `/api/v1/tenants/{tenant}/members/invite` | Yes | Admin/Owner | 201 | Invite employee via email |
| POST | `/api/v1/tenants/{tenant}/members/accept` | Yes | Any user | 200/422 | Accept invitation with token |
| GET | `/api/v1/tenants/{tenant}/members/{user}` | Yes | Member | 200 | View member profile |
| PUT | `/api/v1/tenants/{tenant}/members/{user}` | Yes | Admin/Owner | 200 | Change member role |
| POST | `/api/v1/tenants/{tenant}/members/{user}/suspend` | Yes | Admin/Owner | 204 | Suspend member |
| POST | `/api/v1/tenants/{tenant}/members/{user}/restore` | Yes | Admin/Owner | 204 | Restore suspended member |
| DELETE | `/api/v1/tenants/{tenant}/members/{user}` | Yes | Admin/Owner | 204 | Remove member |
| GET | `/api/v1/tenants/{tenant}/invitations` | Yes | Admin/Owner | 200 | List pending invitations |
| DELETE | `/api/v1/tenants/{tenant}/invitations/{invitation}` | Yes | Admin/Owner | 204 | Cancel invitation |

---

## Bugs Found and Fixed During Implementation

### 1. All member endpoints return 403

**Cause:** The `can:` middleware resolves the policy by the model class of the first argument. `can:invite,tenant` looked up the policy for `Tenant`, which is `TenantPolicy` (from Module 03) — not `TenantMemberPolicy`. All member-specific abilities (invite, suspend, remove, etc.) don't exist on `TenantPolicy`, so they returned false → 403.

**Fix:** Replaced `can:` middleware with `$this->authorize()` calls in the controller. Registered `TenantMemberPolicy` methods as named gates in `AppServiceProvider`. This separates the two policies clearly: `TenantPolicy` for tenant CRUD, gates for member management.

### 2. `authorize()` method undefined

**Cause:** Laravel 13's base `Controller` class is empty — it doesn't include any traits. The `AuthorizesRequests` trait (which provides `$this->authorize()`) is not included by default.

**Fix:** Added `use AuthorizesRequests` to the base `Controller`.

### 3. Accept endpoint returns 403

**Cause:** The `accept` method had `$this->authorize('member.view', $tenant)`, but the user accepting the invitation is NOT yet a member — that's the whole point. The membership check blocked the exact users who needed this endpoint.

**Fix:** Removed the membership check from `accept`. The invitation token is the authorization.

### 4. PHPStan: nullable relations and cast types

Multiple PHPStan issues with `BelongsTo` returning `Tenant|null` and model casts not being recognized.

**Fix:** Added null checks in actions/jobs, and `@property` PHPDoc annotations to `TenantInvitation` model for Carbon-cast dates.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — 88 files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 57 tests, 175 assertions (16 new + 41 from Modules 02/03) |

---

## What This Module Does NOT Include

Per the module spec, these are deferred to later modules:

- Team creation/management → Module 07
- Employee onboarding flow with 2FA setup → Module 22
- Employee offboarding with access revocation → Module 16, 22
- Access grants or permissions → Module 08, 09
- Activity logging → Module 20

---

## Key Takeaways

1. **`can:` middleware resolves policy by model class** — when two policies operate on the same model, only one can be the registered model policy. Use named gates for the second one.

2. **Laravel 13's Controller is bare** — no traits by default. Add `AuthorizesRequests` when you need `$this->authorize()`.

3. **Don't gate the entry door** — the accept endpoint must NOT require membership. The invitation token is the authorization. Think about what each endpoint's authorization actually means.

4. **`isMemberOf()` should check status** — a suspended member is not really a member for access purposes. Making the check stricter in the model means all gates/policies automatically respect suspension.

5. **Anonymous notifiables for non-users** — invitation emails go to people who may not have accounts yet. Use `Notification::route('mail', $email)->notify(...)` instead of `$user->notify(...)`.

---

## Build Order — Files Created A to Z

```
 1. Migration          → 2026_09_01_150000_create_tenant_invitations_table.php
 2. Migration          → 2026_09_01_150001_update_tenant_user_pivot_status.php
    → php artisan migrate:fresh
 3. Model              → TenantInvitation.php
 4. Model              → Tenant.php (modified — pivot columns, activeMembers, invitations)
 5. Model              → User.php (modified — isMemberOf checks status, isAdminOf, statusIn)
 6. Notification       → EmployeeInvitation.php
 7. Job                → SendInviteEmail.php
 8. Action             → InviteEmployeeAction.php
 9. Action             → AcceptInvitationAction.php
10. Form Request       → InviteMemberRequest.php
11. Form Request       → AcceptInvitationRequest.php
12. Form Request       → UpdateMemberRequest.php
13. API Resource       → TenantMemberResource.php
14. API Resource       → TenantInvitationResource.php
15. Policy             → TenantMemberPolicy.php
16. Controller         → Controller.php (modified — added AuthorizesRequests)
17. Controller         → TenantMemberController.php
18. Middleware         → ResolveTenant.php (modified — block suspended, ensureActiveMember)
19. Provider           → AppServiceProvider.php (modified — registered 7 gates)
20. Routes             → routes/api.php (modified — added 10 member/invitation routes)
21. Test Helper        → TenantHelper.php (modified — createInvitation, extra pivot attrs)
22. Test               → InvitationTest.php (7 tests)
23. Test               → MemberManagementTest.php (9 tests)
24. Verification       → pint → phpstan → php artisan test
```
