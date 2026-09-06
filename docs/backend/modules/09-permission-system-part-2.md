# Module 09 — Permission System: Part 2 (Sharing & Access Management)

| Field | Value |
|---|---|
| **Module** | 09 |
| **Name** | Permission System — Sharing & Access Management |
| **Dependencies** | Module 08 |
| **Status** | ✅ Complete |

---

## Objective

Build the sharing endpoints that allow users to grant access to resources (vault items, files, notes) to individuals, teams, or the entire company. Includes changing permissions and removing access.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| TP-01 | Share an item with a specific individual | P0 |
| TP-02 | Share an item with a team | P0 |
| TP-03 | Share an item with multiple teams | P1 |
| TP-04 | Share an item with everyone in the company | P1 |
| TP-12 | Change someone's access level | P0 |
| TP-13 | Remove someone's access | P0 |

---

## Tasks

### 9.1 API Endpoints — Grant Access

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/vault/items/{item}/access` | Grant access to a user/team/company |
| `PUT` | `/api/v1/vault/items/{item}/access/{grant}` | Update access (change permission) |
| `DELETE` | `/api/v1/vault/items/{item}/access/{grant}` | Revoke access |

Note: These endpoints work for any grantable resource type (`vault/items`, `files`, `notes`). The route pattern is consistent across resource types.

### 9.2 Actions

- [ ] `app/Actions/GrantAccessAction.php`:
  - Verify granter has `share` permission on the resource (via `AccessResolver`)
  - Validate subject exists and belongs to same tenant (for team/company subjects)
  - Check for existing grant — if one exists, update it instead of creating duplicate
  - Create `AccessGrant` record
  - Dispatch `AccessGranted` event
  - Return grant

```php
public function __invoke(
    User $grantedBy,
    Model $resource,
    string $subjectType,
    int $subjectId,
    Permission $permission,
    ?Carbon $expiresAt = null,
    ?int $maxViews = null,
    bool $startOnFirstView = false,
    ?Carbon $startsAt = null,
): AccessGrant
```

- [ ] `app/Actions/UpdateAccessAction.php`:
  - Verify updater has `manage` or `share` permission
  - Update permission level on existing grant
  - Dispatch `AccessUpdated` event
  - Return grant

- [ ] `app/Actions/RevokeAccessAction.php`:
  - Verify revoker has `manage` or `share` permission
  - Set `revoked_at`, `revoked_by`, `revoke_reason`
  - Dispatch `AccessRevoked` event
  - Return grant

### 9.3 Controller

- [ ] Extend `AccessGrantController.php`:
  - `store()` — grant access (calls `GrantAccessAction`)
  - `update()` — change permission (calls `UpdateAccessAction`)
  - `destroy()` — revoke access (calls `RevokeAccessAction`)

### 9.4 Form Requests

- [ ] `GrantAccessRequest`:
  - `subject_type`: required, in:User,Team,Tenant
  - `subject_id`: required, integer
  - `permission`: required, in:view,download,edit,share,manage
  - `expires_at`: nullable, date, after:now
  - `max_views`: nullable, integer, min:1
  - `start_on_first_view`: nullable, boolean
  - `starts_at`: nullable, date, after:now
  - Custom validation: if `subject_type = 'User'`, verify user is a tenant member
  - Custom validation: if `subject_type = 'Team'`, verify team belongs to tenant
  - Custom validation: if `subject_type = 'Tenant'`, verify it's the current tenant

- [ ] `UpdateAccessRequest`:
  - `permission`: required, in:view,download,edit,share,manage
  - `expires_at`: nullable, date
  - `max_views`: nullable, integer, min:1

### 9.5 Events

- [ ] `app/Events/AccessGranted.php` — carries `AccessGrant` and `User $grantedBy`
- [ ] `app/Events/AccessUpdated.php` — carries `AccessGrant` and `User $updatedBy`
- [ ] `app/Events/AccessRevoked.php` — carries `AccessGrant`, `User $revokedBy`, `string $reason`

### 9.6 Listeners

- [ ] `LogAccessGranted` — logs to activity log (Module 20, but stub for now)
- [ ] `LogAccessRevoked` — logs to activity log
- [ ] `NotifyAccessGranted` — sends notification to grantee

### 9.7 Notification

- [ ] `app/Notifications/AccessGrantedNotification.php`:
  - Sent to the user who received access
  - Includes: resource name, permission level, granted by, expiration
  - Channels: database (in-app)

### 9.8 Policy

- [ ] `AccessGrantPolicy.php`:
  - `create()` — user must have `share` permission on the grantable resource
  - `update()` — user must have `share` or `manage` permission
  - `delete()` — user must have `share` or `manage` permission

### 9.9 Bulk Sharing (Multiple Teams)

- [ ] Support `POST /api/v1/vault/items/{item}/access/bulk`:
  - Accept `team_ids: array` + `permission`
  - Create grants for each team in a single request
  - Return collection of created grants

### 9.10 Routes

```php
Route::middleware(['auth:sanctum', 'tenant.resolve'])->group(function () {
    // Access grants for vault items
    Route::prefix('v1/vault/items/{item}/access')->group(function () {
        Route::get('/', [AccessGrantController::class, 'index']);
        Route::post('/', [AccessGrantController::class, 'store']);
        Route::post('/bulk', [AccessGrantController::class, 'bulkStore']);
        Route::put('/{grant}', [AccessGrantController::class, 'update']);
        Route::delete('/{grant}', [AccessGrantController::class, 'destroy']);
    });

    // Same pattern for files and notes (added in their respective modules)
});
```

---

## Acceptance Criteria

- [ ] User with `share` permission can grant access to another user
- [ ] User with `share` permission can grant access to a team
- [ ] User with `share` permission can grant access to the entire company (tenant)
- [ ] User can grant access to multiple teams in one request (bulk)
- [ ] User can change someone's permission level (e.g., view → edit)
- [ ] User can revoke someone's access
- [ ] User without `share` permission cannot grant access → `403`
- [ ] Granting access to a non-tenant-member user → `422`
- [ ] Duplicate grant (same subject + same resource) updates existing grant instead of creating duplicate
- [ ] Grantee receives notification when access is granted
- [ ] All grant operations dispatch events for audit logging
- [ ] Revoked grants have `revoked_at` set (not hard deleted)

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_user_can_share_with_individual` | POST creates grant for user |
| `test_user_can_share_with_team` | POST creates grant for team |
| `test_user_can_share_with_company` | POST creates tenant-wide grant |
| `test_user_can_bulk_share_with_teams` | POST /bulk creates multiple grants |
| `test_user_can_change_permission` | PUT updates permission level |
| `test_user_can_revoke_access` | DELETE sets revoked_at |
| `test_user_without_share_permission_cannot_grant` | No share permission → 403 |
| `test_cannot_grant_to_non_tenant_member` | Non-member subject → 422 |
| `test_duplicate_grant_updates_existing` | Second grant updates first |
| `test_grantee_receives_notification` | Notification sent on grant |
| `test_revoked_grant_appears_in_history` | Revoked grant still in DB with revoked_at |
| `test_events_dispatched_on_grant` | AccessGranted event fired |
| `test_events_dispatched_on_revoke` | AccessRevoked event fired |

---

## What This Module Does NOT Include

- Temporary access with preset durations (Module 14)
- Access request workflow (Module 15)
- Emergency revocation (Module 16)
- Secure external sharing links (Module 17, 18)
- Activity log implementation (Module 20 — stubs only here)
