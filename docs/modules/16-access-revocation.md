# Module 16 — Access Revocation

| Field | Value |
|---|---|
| **Module** | 16 |
| **Name** | Access Revocation |
| **Dependencies** | Module 08, Module 09, Module 14 |
| **Status** | Not Started |

---

## Objective

Build comprehensive revocation controls — revoke individual access, team access, company-wide access, temporary access, shared links, and emergency revoke (break-glass). Includes automatic revocation when employees are offboarded.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| RV-01 | Revoke access for a specific individual | P0 |
| RV-02 | Revoke access for a team | P0 |
| RV-03 | Revoke company-wide access | P1 |
| RV-04 | Revoke temporary access before expiration | P0 |
| RV-05 | Revoke file access | P0 |
| RV-06 | Revoke shared links | P0 → Module 17 |
| RV-07 | Emergency revoke — instantly revoke all access for a specific employee | P0 |
| RV-08 | Automatic access removal when an employee is offboarded | P0 |

---

## Tasks

### 16.1 Extend RevokeAccessAction

- [ ] Update `RevokeAccessAction` (from Module 09) to support:
  - `revoke_reason` — string (manual, expired, view_limit, emergency, offboarding)
  - Set `revoked_at`, `revoked_by`, `revoke_reason` on the grant

### 16.2 API Endpoints — Individual Revocation

Already covered in Module 09:
| Method | Endpoint | Description |
|---|---|---|
| `DELETE` | `/api/v1/vault/items/{item}/access/{grant}` | Revoke individual grant |
| `DELETE` | `/api/v1/files/{file}/access/{grant}` | Revoke file grant |
| `DELETE` | `/api/v1/notes/{note}/access/{grant}` | Revoke note grant |

### 16.3 API Endpoints — Bulk & Emergency Revocation

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/tenants/{tenant}/members/{user}/revoke-all` | Emergency revoke ALL access for a user |
| `POST` | `/api/v1/vault/items/{item}/access/revoke-all` | Revoke all access for a resource |
| `POST` | `/api/v1/vault/items/{item}/access/revoke-team/{team}` | Revoke team access for a resource |

### 16.4 Emergency Revoke Action

- [ ] `app/Actions/EmergencyRevokeAction.php`:
  - Revokes ALL access grants where the user is the subject (`subject_type = 'User'`, `subject_id = user.id`)
  - Also revokes all grants where the user's teams are the subject
  - Also revokes all secure links created by the user
  - Sets `revoked_at`, `revoked_by`, `revoke_reason = 'emergency'`
  - Dispatches `EmergencyRevoked` event
  - Returns count of revoked grants

```php
public function __invoke(User $revokedBy, User $targetUser, string $reason = 'emergency'): int
{
    $count = 0;

    // Revoke all direct user grants
    AccessGrant::where('subject_type', 'User')
        ->where('subject_id', $targetUser->id)
        ->whereNull('revoked_at')
        ->update([
            'revoked_at' => now(),
            'revoked_by' => $revokedBy->id,
            'revoke_reason' => $reason,
        ]);

    // Revoke all team grants for user's teams
    $teamIds = $targetUser->teams()->pluck('teams.id');
    AccessGrant::where('subject_type', 'Team')
        ->whereIn('subject_id', $teamIds)
        ->whereNull('revoked_at')
        ->update([...]);

    // Revoke secure links created by user (Module 17)
    SecureLink::where('created_by', $targetUser->id)
        ->whereNull('revoked_at')
        ->update([...]);

    event(new EmergencyRevoked($targetUser, $revokedBy, $count));

    return $count;
}
```

### 16.5 Revoke All For Resource Action

- [ ] `app/Actions/RevokeAllAccessAction.php`:
  - Revokes all active grants for a specific resource
  - Used when a resource owner wants to instantly cut off all shared access

### 16.6 Revoke Team Access Action

- [ ] `app/Actions/RevokeTeamAccessAction.php`:
  - Revokes all grants where `subject_type = 'Team'` and `subject_id = team.id` for a specific resource

### 16.7 Offboarding Auto-Revoke

- [ ] Update `OffboardEmployeeAction` (from Module 04/22) to call `EmergencyRevokeAction`:
  - When an employee is offboarded, all their access is automatically revoked
  - Reason: `'offboarding'`

### 16.8 Controller

- [ ] `EmergencyRevokeController.php`:
  - `revokeAllForUser()` — admin/owner only, calls `EmergencyRevokeAction`
  - `revokeAllForResource()` — resource owner or admin, calls `RevokeAllAccessAction`
  - `revokeTeamAccess()` — resource owner or admin, calls `RevokeTeamAccessAction`

### 16.9 Form Requests

- [ ] `EmergencyRevokeRequest`:
  - `reason`: nullable, string, max:500

### 16.10 Events

- [ ] `app/Events/EmergencyRevoked.php` — carries `User $targetUser`, `User $revokedBy`, `int $count`
- [ ] `app/Events/AllAccessRevokedForResource.php` — carries `Model $resource`, `User $revokedBy`, `int $count`

### 16.11 Notification

- [ ] `app/Notifications/AccessRevokedNotification.php`:
  - Sent to user whose access was revoked
  - Includes: resource name, reason
  - Channels: database

### 16.12 Policy

- [ ] Emergency revoke: only admin/owner of tenant
- [ ] Revoke all for resource: resource owner or user with `manage` permission
- [ ] Revoke team access: resource owner or user with `manage` permission

### 16.13 Routes

```php
Route::middleware(['auth:sanctum', 'tenant.resolve'])->group(function () {
    Route::post('v1/tenants/{tenant}/members/{user}/revoke-all', [EmergencyRevokeController::class, 'revokeAllForUser']);

    Route::post('v1/vault/items/{item}/access/revoke-all', [EmergencyRevokeController::class, 'revokeAllForResource']);
    Route::post('v1/vault/items/{item}/access/revoke-team/{team}', [EmergencyRevokeController::class, 'revokeTeamAccess']);

    Route::post('v1/files/{file}/access/revoke-all', [EmergencyRevokeController::class, 'revokeAllForResource']);
    Route::post('v1/notes/{note}/access/revoke-all', [EmergencyRevokeController::class, 'revokeAllForResource']);
});
```

---

## Acceptance Criteria

- [ ] User can revoke access for a specific individual (existing from Module 09)
- [ ] User can revoke access for a team on a resource
- [ ] User can revoke all access for a resource (bulk revoke)
- [ ] Admin/owner can emergency revoke ALL access for a specific employee
- [ ] Emergency revoke removes direct grants, team grants, and secure links
- [ ] Emergency revoke returns count of revoked items
- [ ] Offboarded employees have all access automatically revoked
- [ ] Revoked grants have `revoked_at`, `revoked_by`, and `revoke_reason` set
- [ ] Revoked grants are not hard-deleted (audit trail preserved)
- [ ] User whose access was revoked receives notification
- [ ] Non-admin cannot emergency revoke → `403`
- [ ] All revocation operations dispatch events

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_revoke_individual_access` | DELETE revokes single grant |
| `test_revoke_team_access_for_resource` | POST removes team's grants on resource |
| `test_revoke_all_for_resource` | POST removes all grants on resource |
| `test_emergency_revoke_removes_all_user_grants` | All user's direct grants revoked |
| `test_emergency_revoke_removes_team_grants` | All user's team grants revoked |
| `test_emergency_revoke_removes_secure_links` | User's secure links revoked |
| `test_emergency_revoke_returns_count` | Response includes count |
| `test_offboarding_auto_revokes` | Offboard → all access revoked |
| `test_revoked_grants_have_reason` | revoked_reason set correctly |
| `test_revoked_grants_not_hard_deleted` | Grant still exists in DB |
| `test_user_notified_on_revocation` | Notification sent |
| `test_non_admin_cannot_emergency_revoke` | Non-admin → 403 |
| `test_revoke_all_requires_manage_permission` | No manage → 403 |

---

## What This Module Does NOT Include

- Secure link creation and management (Module 17)
- Secure link access tracking (Module 18)
- Activity logging (Module 20)
- Employee offboarding flow (Module 22 — but this module provides the revoke action)
