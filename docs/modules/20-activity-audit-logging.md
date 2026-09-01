# Module 20 — Activity & Audit Logging

| Field | Value |
|---|---|
| **Module** | 20 |
| **Name** | Activity & Audit Logging |
| **Dependencies** | All prior modules |
| **Status** | Not Started |

---

## Objective

Build the append-only audit logging system that records every significant action across the platform — vault access, sharing, revocation, logins, file downloads, configuration changes, etc.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| AS-01 | Personal activity history | P0 |
| AS-02 | Company activity feed | P1 |
| AS-03 | Secret access history | P0 |
| AS-04 | File access history | P0 |
| AS-05 | Sharing history | P0 |
| AS-06 | Access-grant and revocation history | P1 |
| AS-07 | Login history | P0 |
| AS-11 | Employee access overview | P1 |

---

## Tasks

### 20.1 Activity Logs Table & Model

- [ ] Create `activity_logs` migration:

```
activity_logs
  id              -- bigIncrements
  tenant_id       -- foreignId, nullable (null = system/personal action)
  user_id         -- foreignId, nullable (null = system action)
  action          -- string (e.g., 'vault_item.viewed', 'access.granted')
  subject_type    -- string, nullable (morphs: 'VaultItem', 'SecureFile', etc.)
  subject_id      -- unsignedBigInteger, nullable
  properties      -- json, nullable (additional context: IP, user agent, old/new values)
  ip_address      -- string, nullable
  user_agent      -- string, nullable
  created_at      -- timestamp (append-only, NO updated_at)

  index(tenant_id, created_at)
  index(user_id, created_at)
  index(action)
  index(subject_type, subject_id)
```

- [ ] Create `ActivityLog` model:
  - NO `$fillable` — use explicit creation via `ActivityLogger` service
  - NO `updated_at` — append-only (set `const UPDATED_AT = null`)
  - `$casts`: `properties` → `array`
  - Relationships: `tenant()`, `user()`, `subject()` → `morphTo()`
  - Scope: `scopeForTenant(Builder, int)`, `scopeForUser(Builder, int)`, `scopeAction(Builder, string)`

### 20.2 ActivityLogger Service

- [ ] Create `app/Services/ActivityLogger.php`:

```php
class ActivityLogger
{
    public function log(
        string $action,
        ?User $user = null,
        ?Model $subject = null,
        array $properties = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ActivityLog
}
```

- Auto-resolves `tenant_id` from `TenantManager`
- Auto-resolves `ip_address` and `user_agent` from request
- Creates `ActivityLog` record
- Dispatches to `audit` queue for async writing

### 20.3 Action Types

| Action | Description | Triggered By |
|---|---|---|
| `auth.login` | User logged in | AuthController |
| `auth.logout` | User logged out | AuthController |
| `auth.register` | User registered | AuthController |
| `auth.password_changed` | Password changed | AuthController |
| `auth.password_reset` | Password reset | AuthController |
| `vault_item.created` | Vault item created | CreateVaultItemAction |
| `vault_item.updated` | Vault item updated | UpdateVaultItemAction |
| `vault_item.deleted` | Vault item deleted | DeleteVaultItemAction |
| `vault_item.viewed` | Vault item viewed | ViewTracker |
| `file.uploaded` | File uploaded | UploadFileAction |
| `file.downloaded` | File downloaded | SecureFileController |
| `file.deleted` | File deleted | SecureFileController |
| `note.created` | Note created | CreateNoteAction |
| `note.updated` | Note updated | UpdateNoteAction |
| `note.viewed` | Note viewed | ViewTracker |
| `access.granted` | Access granted | GrantAccessAction |
| `access.updated` | Access permission changed | UpdateAccessAction |
| `access.revoked` | Access revoked | RevokeAccessAction |
| `access.expired` | Access expired | CheckExpiredAccess command |
| `access.emergency_revoked` | Emergency revoke | EmergencyRevokeAction |
| `access_request.created` | Access request submitted | CreateAccessRequestAction |
| `access_request.approved` | Access request approved | ApproveAccessRequestAction |
| `access_request.rejected` | Access request rejected | RejectAccessRequestAction |
| `secure_link.created` | Secure link created | CreateSecureLinkAction |
| `secure_link.accessed` | Secure link accessed | PublicLinkController |
| `secure_link.revoked` | Secure link revoked | SecureLinkController |
| `team.created` | Team created | TeamController |
| `team.deleted` | Team deleted | TeamController |
| `member.invited` | Member invited | InviteEmployeeAction |
| `member.joined` | Member accepted invitation | AcceptInvitationAction |
| `member.suspended` | Member suspended | TenantMemberController |
| `member.removed` | Member removed | TenantMemberController |
| `member.offboarded` | Member offboarded | OffboardEmployeeAction |

### 20.4 LogActivity Job

- [ ] `app/Jobs/LogActivity.php` (implements `ShouldQueue`):
  - Queue: `audit`
  - Writes `ActivityLog` record asynchronously
  - Accepts all parameters from `ActivityLogger::log()`

### 20.5 Wire Up Event Listeners

- [ ] Create listeners for all events dispatched in prior modules:
  - `LogVaultItemCreated`, `LogVaultItemUpdated`, `LogVaultItemDeleted`
  - `LogAccessGranted`, `LogAccessRevoked`, `LogAccessExpired`
  - `LogAccessRequestCreated`, `LogAccessRequestApproved`, `LogAccessRequestRejected`
  - `LogSecureLinkCreated`, `LogSecureLinkAccessed`, `LogSecureLinkRevoked`
  - `LogFileUploaded`, `LogFileDownloaded`, `LogFileDeleted`
  - `LogMemberInvited`, `LogMemberJoined`, `LogMemberSuspended`, `LogMemberRemoved`
  - `LogAuthLogin`, `LogAuthLogout`, `LogAuthPasswordChanged`
- [ ] Register all listeners in `EventServiceProvider`

### 20.6 Update Prior Module Stubs

- [ ] Replace all stub logging calls in prior modules with actual `ActivityLogger` calls
- [ ] Add login/logout logging to `AuthController`
- [ ] Add view logging to `ViewTracker`

### 20.7 API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/activity-logs` | Personal activity history (current user's actions) |
| `GET` | `/api/v1/tenants/{tenant}/activity-logs` | Company activity feed (all tenant actions) |
| `GET` | `/api/v1/vault/items/{item}/activity-logs` | Secret access history |
| `GET` | `/api/v1/files/{file}/activity-logs` | File access history |
| `GET` | `/api/v1/tenants/{tenant}/members/{user}/activity-logs` | Employee access overview |

### 20.8 Controller

- [ ] `ActivityLogController.php`:
  - `personalHistory()` — logs where `user_id = auth()->id()`, paginated
  - `companyFeed()` — logs where `tenant_id = current_tenant_id`, paginated, admin/owner only
  - `resourceHistory()` — logs where `subject_type` and `subject_id` match, user must have access
  - `employeeOverview()` — logs where `user_id = {user}` and `tenant_id = current_tenant_id`, admin/owner only

### 20.9 API Resource

- [ ] `ActivityLogResource.php`:
  - `id`, `action`, `user` (id + name), `subject` (type + id + name if available), `properties`, `ip_address`, `created_at`

### 20.10 Filters

- [ ] Support query parameters:
  - `action` — filter by action type (e.g., `?action=access.granted`)
  - `user_id` — filter by user
  - `subject_type` — filter by resource type
  - `from` — date range start
  - `to` — date range end

### 20.11 Retention Policy

- [ ] Create `app/Console/Commands/CleanupActivityLogs.php`:
  - Scheduled monthly
  - Deletes logs older than 1 year (configurable)
  - Or archives to cold storage (post-MVP)

### 20.12 Routes

```php
Route::middleware(['auth:sanctum', 'tenant.resolve'])->group(function () {
    Route::get('v1/activity-logs', [ActivityLogController::class, 'personalHistory']);
    Route::get('v1/tenants/{tenant}/activity-logs', [ActivityLogController::class, 'companyFeed']);
    Route::get('v1/tenants/{tenant}/members/{user}/activity-logs', [ActivityLogController::class, 'employeeOverview']);
    Route::get('v1/vault/items/{item}/activity-logs', [ActivityLogController::class, 'resourceHistory']);
    Route::get('v1/files/{file}/activity-logs', [ActivityLogController::class, 'resourceHistory']);
});
```

---

## Acceptance Criteria

- [ ] Every significant action creates an `ActivityLog` record
- [ ] Logs are append-only (no `updated_at`, no updates, no deletes except cleanup)
- [ ] User can view their personal activity history
- [ ] Admin/owner can view company activity feed
- [ ] User can view access history for a specific resource (if they have access)
- [ ] Admin/owner can view employee access overview
- [ ] Activity logs include IP address and user agent
- [ | Logs are written asynchronously via `audit` queue
- [ ] Logs support filtering by action, user, subject type, and date range
- [ ] Cleanup command removes logs older than retention period
- [ ] Login and logout events are logged
- [ ] All event listeners are registered and functional

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_login_is_logged` | auth.login activity created on login |
| `test_logout_is_logged` | auth.logout activity created on logout |
| `test_vault_item_creation_logged` | vault_item.created activity |
| `test_vault_item_view_logged` | vault_item.viewed activity |
| `test_access_grant_logged` | access.granted activity |
| `test_access_revoke_logged` | access.revoked activity |
| `test_file_download_logged` | file.downloaded activity |
| `test_secure_link_access_logged` | secure_link.accessed activity |
| `test_personal_history` | GET returns user's own logs |
| `test_company_feed` | GET returns all tenant logs (admin only) |
| `test_resource_history` | GET returns logs for specific resource |
| `test_employee_overview` | GET returns logs for specific employee |
| `test_logs_are_append_only` | No update method available |
| `test_log_includes_ip_and_user_agent` | IP and UA stored |
| `test_filter_by_action` | ?action=access.granted filters correctly |
| `test_filter_by_date_range` | from/to date filters work |
| `test_non_admin_cannot_view_company_feed` | Non-admin → 403 |
| `test_cleanup_command_removes_old_logs` | Old logs deleted |

---

## What This Module Does NOT Include

- Security alerts (Module 21)
- Real-time activity streaming (post-MVP)
- Log export (post-MVP)
- Log search (post-MVP)
