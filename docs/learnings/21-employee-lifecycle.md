# 22 — How Module 22 (Employee Lifecycle) Was Built

| Field | Value |
|---|---|
| **Module** | 22 — Employee Lifecycle |
| **Date** | 2026-09-02 |
| **Spec** | `docs/modules/22-employee-lifecycle.md` |

---

## Goal

Build the complete employee lifecycle — invitation with team/access assignment, onboarding flow, team/role changes, offboarding with automatic access revocation, and history preservation.

---

## Decisions Made Before Writing Code

### 1. Extend existing invitation system

Rather than creating a new invitation flow, extended the existing `TenantInvitation` with:
- `team_ids` (JSON) — array of team IDs to auto-assign on acceptance
- `initial_access` (JSON) — array of `{resource_type, resource_id, permission}` grants to create on acceptance

### 2. Offboarding preserves audit trail

The old `OffboardEmployeeAction` used `$tenant->users()->detach()` which deleted the pivot row. The new version uses `updateExistingPivot` to set `status='left'` and `left_at=now()`, preserving the audit trail while revoking access.

### 3. Offboarding is comprehensive

The rewritten `OffboardEmployeeAction`:
1. Revokes all access grants (via `EmergencyRevokeAction`)
2. Removes from all teams in the tenant
3. Sets tenant_user status to 'left', sets left_at
4. Revokes all API tokens for this tenant
5. Revokes all secure links created by the user
6. Dispatches `EmployeeOffboarded` event
7. Sends `EmployeeOffboardedNotification` to the user
8. Logs `member.offboarded` activity

### 4. Team removal revokes team-based access

`RemoveFromTeamAction` not only detaches the user from the team but also revokes any access grants to team resources where the user was the subject.

### 5. Initial access grants bypass permission checks

When creating initial access grants during invitation acceptance, the grants are created directly (not via `GrantAccessAction`) because the inviter's permission check would fail in the context of a new user accepting an invitation.

---

## Files Created/Modified

| File | Purpose |
|---|---|
| `app/Actions/AcceptInvitationAction.php` | Extended — auto team assignment + initial access grants |
| `app/Actions/AssignTeamAction.php` | New — assign user to team with logging |
| `app/Actions/CompleteOnboardingAction.php` | New — mark onboarding complete |
| `app/Actions/InviteEmployeeAction.php` | Extended — store team_ids + initial_access |
| `app/Actions/OffboardEmployeeAction.php` | Rewritten — full lifecycle offboarding |
| `app/Actions/RemoveFromTeamAction.php` | New — remove from team + revoke team access |
| `app/Events/EmployeeOffboarded.php` | New — domain event |
| `app/Http/Controllers/Api/V1/TenantMemberController.php` | Extended — 5 new methods |
| `app/Http/Requests/Tenant/AssignTeamsRequest.php` | New |
| `app/Http/Requests/Tenant/ChangeRoleRequest.php` | New |
| `app/Http/Requests/Tenant/InviteMemberRequest.php` | Extended — team_ids + initial_access validation |
| `app/Http/Requests/Tenant/OffboardEmployeeRequest.php` | New |
| `app/Models/TenantInvitation.php` | Extended — team_ids + initial_access casts |
| `app/Models/User.php` | Extended — onboarding_completed_at in pivot |
| `app/Notifications/EmployeeOffboardedNotification.php` | New |
| `tests/Feature/Api/V1/Employee/EmployeeLifecycleTest.php` | 16 feature tests |

---

## New Endpoints

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/v1/tenants/{tenant}/members/onboarding-complete` | Complete onboarding |
| PUT | `/api/v1/tenants/{tenant}/members/{user}/role` | Change user's role |
| POST | `/api/v1/tenants/{tenant}/members/{user}/teams` | Assign user to teams |
| DELETE | `/api/v1/tenants/{tenant}/members/{user}/teams/{team}` | Remove from team |
| POST | `/api/v1/tenants/{tenant}/members/{user}/offboard` | Offboard employee |

---

## Bugs Found and Fixed During Implementation

### 1. Old test expected detach, new code uses updateExistingPivot

**Cause:** The existing `test_offboarding_auto_revokes` test checked that the user was completely detached from the tenant. The new `OffboardEmployeeAction` sets `status='left'` instead.

**Fix:** Updated the test to check `wherePivotNull('left_at')` instead of `exists()`.

### 2. PHPStan — is_array on already-cast properties

**Cause:** `team_ids` and `initial_access` are cast to `array` in the model, so PHPStan knows they're already arrays.

**Fix:** Removed the redundant `is_array()` checks.

### 3. Sanctum token tenant_id not fillable

**Cause:** `PersonalAccessToken::$fillable` doesn't include `tenant_id`. In tests, `forceFill` + `save` didn't persist the `tenant_id`.

**Fix:** Used `DB::table('personal_access_tokens')->update()` to set `tenant_id` in tests.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 329 tests, 844 assertions (16 new + 313 from Modules 02-21) |
