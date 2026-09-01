# Module 22 — Employee Lifecycle

| Field | Value |
|---|---|
| **Module** | 22 |
| **Name** | Employee Lifecycle |
| **Dependencies** | Module 04, Module 07, Module 08, Module 16, Module 20 |
| **Status** | Not Started |

---

## Objective

Build the complete employee lifecycle — invitation, onboarding flow, team/role assignment, permission changes, suspension, offboarding (with automatic access revocation), and history preservation.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| EL-01 | Invite employee via email with role assignment | P0 → Module 04 |
| EL-02 | Employee onboarding flow (accept invite, set password, 2FA setup) | P0 |
| EL-03 | Assign employee to one or more teams | P0 |
| EL-04 | Assign employee role (member, admin, owner) | P0 → Module 04 |
| EL-05 | Grant initial access during onboarding | P1 |
| EL-06 | Change employee permissions and teams | P0 |
| EL-07 | Suspend employee | P1 → Module 04 |
| EL-08 | Offboard employee (revoke all access, deactivate account) | P0 |
| EL-09 | Transfer employee-owned resources to another employee | P2 |
| EL-10 | Preserve employee activity history after offboarding | P1 |

---

## Tasks

### 22.1 Onboarding Flow

- [ ] After accepting invitation (Module 04), the onboarding flow:
  1. User sets their password (if new user)
  2. User sets up 2FA (optional but recommended — Module 23)
  3. User is assigned to teams (by admin or during invitation)
  4. Initial access grants are created (if specified during invitation)

- [ ] Add `onboarding_completed_at` to `tenant_user` pivot
- [ ] Create `app/Actions/CompleteOnboardingAction.php`:
  - Mark onboarding as complete
  - Log `member.onboarded` activity

### 22.2 Extend Invitation with Team Assignment

- [ ] Add `team_ids` array to `InviteMemberRequest` (Module 04):
  - `team_ids`: nullable, array
  - `team_ids.*`: exists:teams,id (must belong to same tenant)
- [ ] Update `InviteEmployeeAction` to store team assignments with invitation
- [ ] Add `team_ids` json column to `tenant_invitations` table
- [ ] On acceptance, auto-assign user to specified teams

### 22.3 Extend Invitation with Initial Access

- [ ] Add `initial_access` json to `tenant_invitations` table:
  - Array of `{ resource_type, resource_id, permission }` grants to create on acceptance
- [ ] On acceptance, `AcceptInvitationAction` creates access grants via `GrantAccessAction`

### 22.4 Change Teams & Permissions

- [ ] API endpoints (extending Module 04/07):

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/tenants/{tenant}/members/{user}/teams` | Assign user to additional teams |
| `DELETE` | `/api/v1/tenants/{tenant}/members/{user}/teams/{team}` | Remove user from a team |
| `PUT` | `/api/v1/tenants/{tenant}/members/{user}/role` | Change user's tenant role |

- [ ] `app/Actions/AssignTeamAction.php` — assign user to team, log activity
- [ ] `app/Actions/RemoveFromTeamAction.php` — remove user from team, revoke team-based access grants

### 22.5 Offboard Employee Action

- [ ] `app/Actions/OffboardEmployeeAction.php`:

```php
public function __invoke(User $offboardedBy, User $targetUser, Tenant $tenant, ?string $reason = null): void
{
    // 1. Revoke all access grants (EmergencyRevokeAction from Module 16)
    app(EmergencyRevokeAction::class)($offboardedBy, $targetUser, 'offboarding');

    // 2. Remove from all teams
    $targetUser->teams()->where('tenant_id', $tenant->id)->detach();

    // 3. Set tenant_user status to 'left', set left_at
    $targetUser->tenants()->updateExistingPivot($tenant->id, [
        'status' => 'left',
        'left_at' => now(),
    ]);

    // 4. Revoke all API tokens for this tenant
    $targetUser->tokens()->where('tenant_id', $tenant->id)->delete();

    // 5. Revoke all secure links created by user
    SecureLink::where('created_by', $targetUser->id)
        ->where('tenant_id', $tenant->id)
        ->whereNull('revoked_at')
        ->update(['revoked_at' => now(), 'revoke_reason' => 'offboarding']);

    // 6. Transfer owned resources (if specified)
    // — post-MVP (EL-09 is P2)

    // 7. Dispatch event
    event(new EmployeeOffboarded($targetUser, $offboardedBy, $tenant, $reason));

    // 8. Log activity
    app(ActivityLogger::class)->log('member.offboarded', $offboardedBy, $tenant, [
        'offboarded_user' => $targetUser->name,
        'reason' => $reason,
    ]);
}
```

### 22.6 Offboarding Endpoint

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/tenants/{tenant}/members/{user}/offboard` | Offboard employee |

### 22.7 Offboarding Request

- [ ] `OffboardEmployeeRequest`:
  - `reason`: nullable, string, max:500
  - `transfer_resources_to`: nullable, exists:users,id (post-MVP — EL-09)

### 22.8 Event

- [ ] `app/Events/EmployeeOffboarded.php` — carries `User $offboarded`, `User $offboardedBy`, `Tenant $tenant`, `?string $reason`

### 22.9 Notification

- [ ] `app/Notifications/EmployeeOffboardedNotification.php`:
  - Sent to offboarded user
  - Includes: tenant name, reason
  - Channels: mail

### 22.10 Preserve Activity History

- [ ] Activity logs are NOT deleted on offboarding (append-only table)
- [ ] Offboarded user's activity history remains viewable by admin/owner
- [ ] `GET /api/v1/tenants/{tenant}/members/{user}/activity-logs` still works for offboarded users (admin only)

### 22.11 Controller

- [ ] Extend `TenantMemberController.php`:
  - `assignTeams()` — POST teams to user
  - `removeFromTeam()` — DELETE user from team
  - `changeRole()` — PUT user's role
  - `offboard()` — POST offboard user

### 22.12 Routes

```php
Route::middleware(['auth:sanctum', 'tenant.resolve'])
    ->prefix('v1/tenants/{tenant}/members/{user}')
    ->group(function () {
        Route::post('teams', [TenantMemberController::class, 'assignTeams']);
        Route::delete('teams/{team}', [TenantMemberController::class, 'removeFromTeam']);
        Route::put('role', [TenantMemberController::class, 'changeRole']);
        Route::post('offboard', [TenantMemberController::class, 'offboard']);
    });
```

---

## Acceptance Criteria

- [ ] Invitation can include team assignments and initial access grants
- [ ] On acceptance, user is auto-assigned to specified teams
- [ ] On acceptance, initial access grants are created
- [ ] User can complete onboarding (set password, optionally set up 2FA)
- [ ] Admin can assign user to additional teams
- [ ] Admin can remove user from a team (revokes team-based access)
- [ ] Admin can change user's role (member ↔ admin)
- [ ] Admin can offboard an employee
- [ ] Offboarding revokes all access grants
- [ ] Offboarding removes user from all teams
- [ ] Offboarding revokes API tokens for the tenant
- [ ] Offboarding revokes secure links
- [ ] Offboarded user's activity history is preserved
- [ ] Offboarded user receives notification
- [ ] Offboarded user can no longer access tenant resources

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_invitation_with_team_assignment` | Teams assigned on acceptance |
| `test_invitation_with_initial_access` | Access grants created on acceptance |
| `test_onboarding_completion` | onboarding_completed_at set |
| `test_admin_can_assign_teams` | POST adds user to teams |
| `test_admin_can_remove_from_team` | DELETE removes from team |
| `test_removing_from_team_revokes_team_access` | Team access grants revoked |
| `test_admin_can_change_role` | PUT updates role |
| `test_admin_can_offboard_employee` | POST offboards user |
| `test_offboarding_revokes_all_access` | All grants revoked |
| `test_offboarding_removes_from_teams` | All team memberships removed |
| `test_offboarding_revokes_tokens` | API tokens deleted |
| `test_offboarding_revokes_secure_links` | Secure links revoked |
| `test_offboarded_user_cannot_access_tenant` | Access attempts → 403 |
| `test_activity_history_preserved` | Activity logs still exist |
| `test_offboarded_user_notified` | Notification sent |
| `test_non_admin_cannot_offboard` | Non-admin → 403 |

---

## What This Module Does NOT Include

- Resource ownership transfer (post-MVP — EL-09)
- 2FA enforcement during onboarding (Module 23)
- Reactivation of offboarded employees (re-invite via Module 04)
