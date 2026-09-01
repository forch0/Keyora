# Module 15 — Access Requests

| Field | Value |
|---|---|
| **Module** | 15 |
| **Name** | Access Requests |
| **Dependencies** | Module 08, Module 09, Module 14 |
| **Status** | Not Started |

---

## Objective

Build the access request workflow — users can request access to secrets, files, or folders they don't have access to. Resource owners/approvers can approve, reject, or modify requests (change duration or permission level).

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| AR-01 | Request access to a secret, file, or folder | P0 |
| AR-02 | Request includes desired duration and reason | P0 |
| AR-03 | Resource owner can approve or reject the request | P0 |
| AR-04 | Owner can modify requested duration before approving | P1 |
| AR-05 | Owner can grant a different permission level than requested | P1 |
| AR-06 | Requester can cancel their own request | P1 |
| AR-07 | Users can view their pending requests (sent and received) | P0 |
| AR-08 | Full request history is retained and viewable | P1 |
| AR-09 | Email notifications on request submission, approval, and rejection | P1 |

---

## Tasks

### 15.1 Access Requests Table & Model

- [ ] Create `access_requests` migration:

```
access_requests
  id                  -- bigIncrements
  tenant_id           -- foreignId (constrained, cascadeOnDelete)
  requester_id        -- foreignId (users, constrained, cascadeOnDelete)
  resource_type       -- string (morphs: 'VaultItem', 'SecureFile', 'SecureNote', 'Folder')
  resource_id         -- unsignedBigInteger
  resource_owner_id   -- foreignId (users) — denormalized for quick lookup
  requested_permission -- enum: 'view', 'download', 'edit', 'share', 'manage'
  granted_permission  -- enum, nullable (what was actually granted, may differ)
  requested_duration  -- string, nullable (e.g., '1h', '24h', '7d', 'permanent')
  granted_expires_at  -- timestamp, nullable (actual expiration granted)
  reason              -- text (why access is needed)
  status              -- enum: 'pending', 'approved', 'rejected', 'cancelled', 'expired'
  reviewed_by         -- foreignId, nullable (users)
  reviewed_at         -- timestamp, nullable
  review_note         -- text, nullable (approver's note)
  created_at
  updated_at

  index(tenant_id)
  index(requester_id, status)
  index(resource_owner_id, status)
  index(resource_type, resource_id)
  index(status)
```

- [ ] Create `AccessRequest` model:
  - `use BelongsToTenant` trait
  - `$fillable`: all columns above
  - `$casts`: `granted_expires_at` → `datetime`, `reviewed_at` → `datetime`
  - Relationships: `tenant()`, `requester()` → `belongsTo(User::class, 'requester_id')`, `resourceOwner()` → `belongsTo(User::class, 'resource_owner_id')`, `reviewer()` → `belongsTo(User::class, 'reviewed_by')`, `resource()` → `morphTo()`
  - Scope: `scopePending(Builder)`, `scopeForUser(Builder, User)` — requests sent or received

### 15.2 API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/access-requests` | List requests (sent + received, filterable) |
| `POST` | `/api/v1/access-requests` | Create access request |
| `GET` | `/api/v1/access-requests/{request}` | Get request details |
| `PUT` | `/api/v1/access-requests/{request}/approve` | Approve request (optionally modify) |
| `PUT` | `/api/v1/access-requests/{request}/reject` | Reject request |
| `DELETE` | `/api/v1/access-requests/{request}` | Cancel request (requester only) |
| `GET` | `/api/v1/access-requests/history` | Full request history (paginated) |

### 15.3 Controller

- [ ] `app/Http/Controllers/Api/V1/AccessRequestController.php`:
  - `index()` — list requests where user is requester OR resource owner, filter by status
  - `store()` — create request (auto-resolve resource owner from resource)
  - `show()` — view request details (requester or owner only)
  - `approve()` — approve request, optionally override permission/duration, creates access grant
  - `reject()` — reject request with optional note
  - `destroy()` — cancel request (requester only, only if pending)
  - `history()` — paginated full history with filters

### 15.4 Actions

- [ ] `app/Actions/CreateAccessRequestAction.php`:
  - Validate requester doesn't already have access (via `AccessResolver`)
  - Validate no existing pending request for same resource by same user
  - Auto-resolve resource owner from resource (`resource.user_id`)
  - Create `AccessRequest` record
  - Dispatch `AccessRequested` event
  - Return request

- [ ] `app/Actions/ApproveAccessRequestAction.php`:
  - Verify approver is the resource owner (or has `share` permission)
  - Accept optional overrides: `granted_permission`, `granted_expires_at`
  - Create `AccessGrant` via `GrantAccessAction` with approved parameters
  - Update request: `status = 'approved'`, `reviewed_by`, `reviewed_at`, `granted_permission`, `granted_expires_at`
  - Dispatch `AccessRequestApproved` event
  - Return request

- [ ] `app/Actions/RejectAccessRequestAction.php`:
  - Verify rejecter is the resource owner
  - Update request: `status = 'rejected'`, `reviewed_by`, `reviewed_at`, `review_note`
  - Dispatch `AccessRequestRejected` event
  - Return request

### 15.5 Form Requests

- [ ] `CreateAccessRequestRequest`:
  - `resource_type`: required, in:VaultItem,SecureFile,SecureNote,Folder
  - `resource_id`: required, integer
  - `requested_permission`: required, in:view,download,edit,share,manage
  - `requested_duration`: nullable, in:15m,30m,1h,24h,7d,30d,permanent
  - `reason`: required, string, max:1000

- [ ] `ApproveAccessRequestRequest`:
  - `granted_permission`: nullable, in:view,download,edit,share,manage (defaults to requested)
  - `granted_duration`: nullable, in:15m,30m,1h,24h,7d,30d,permanent (defaults to requested)
  - `review_note`: nullable, string, max:1000

- [ ] `RejectAccessRequestRequest`:
  - `review_note`: nullable, string, max:1000

### 15.6 API Resource

- [ ] `AccessRequestResource.php`:
  - `id`, `resource` (type + id + name), `requester` (id + name), `resource_owner` (id + name), `requested_permission`, `granted_permission`, `requested_duration`, `granted_expires_at`, `reason`, `status`, `review_note`, `reviewed_by` (name), `reviewed_at`, `created_at`

### 15.7 Events

- [ ] `app/Events/AccessRequested.php` — carries `AccessRequest`
- [ ] `app/Events/AccessRequestApproved.php` — carries `AccessRequest`, `AccessGrant`
- [ ] `app/Events/AccessRequestRejected.php` — carries `AccessRequest`

### 15.8 Notifications

- [ ] `app/Notifications/AccessRequestReceived.php`:
  - Sent to resource owner when a request is submitted
  - Includes: requester name, resource name, requested permission, duration, reason
  - Channels: mail, database

- [ ] `app/Notifications/AccessRequestApproved.php`:
  - Sent to requester when approved
  - Includes: resource name, granted permission, expiration
  - Channels: mail, database

- [ ] `app/Notifications/AccessRequestRejected.php`:
  - Sent to requester when rejected
  - Includes: resource name, review note
  - Channels: mail, database

### 15.9 Jobs

- [ ] `app/Jobs/SendAccessRequestNotification.php` — sends notification to resource owner
- [ ] `app/Jobs/SendAccessRequestApprovedNotification.php` — sends notification to requester
- [ ] `app/Jobs/SendAccessRequestRejectedNotification.php` — sends notification to requester

### 15.10 Policy

- [ ] `AccessRequestPolicy.php`:
  - `view()` — user is requester or resource owner
  - `approve()` — user is resource owner or has `share` permission on resource
  - `reject()` — user is resource owner or has `share` permission on resource
  - `cancel()` — user is requester AND status is `pending`

### 15.11 Routes

```php
Route::middleware(['auth:sanctum', 'tenant.resolve'])->prefix('v1/access-requests')->group(function () {
    Route::get('/', [AccessRequestController::class, 'index']);
    Route::post('/', [AccessRequestController::class, 'store']);
    Route::get('/history', [AccessRequestController::class, 'history']);
    Route::get('/{request}', [AccessRequestController::class, 'show']);
    Route::put('/{request}/approve', [AccessRequestController::class, 'approve']);
    Route::put('/{request}/reject', [AccessRequestController::class, 'reject']);
    Route::delete('/{request}', [AccessRequestController::class, 'destroy']);
});
```

---

## Acceptance Criteria

- [ ] User can request access to a resource they don't have access to
- [ ] Request includes permission level, duration, and reason
- [ ] Cannot create request for a resource user already has access to → `422`
- [ ] Cannot create duplicate pending request for same resource → `422`
- [ ] Resource owner receives notification when request is submitted
- [ ] Resource owner can approve request → access grant created
- [ ] Resource owner can modify permission level when approving (grant different than requested)
- [ ] Resource owner can modify duration when approving
- [ ] Resource owner can reject request with optional note
- [ ] Requester receives notification on approval and rejection
- [ ] Requester can cancel their own pending request
- [ ] Requester cannot cancel an already-approved/rejected request → `422`
- [ ] User can view their sent requests
- [ ] User can view received requests (as resource owner)
- [ ] Full request history is retained (all statuses)
- [ ] Non-owner cannot approve/reject → `403`
- [ ] All requests are tenant-scoped

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_user_can_request_access` | POST creates request |
| `test_cannot_request_access_already_have` | Already has access → 422 |
| `test_cannot_create_duplicate_pending` | Duplicate → 422 |
| `test_owner_receives_notification` | Notification sent on request |
| `test_owner_can_approve` | PUT /approve creates access grant |
| `test_owner_can_modify_permission_on_approve` | Granted permission differs from requested |
| `test_owner_can_modify_duration_on_approve` | Granted duration differs from requested |
| `test_owner_can_reject` | PUT /reject sets status rejected |
| `test_requester_notified_on_approval` | Notification sent to requester |
| `test_requester_notified_on_rejection` | Notification sent to requester |
| `test_requester_can_cancel_pending` | DELETE on pending → cancelled |
| `test_cannot_cancel_approved` | DELETE on approved → 422 |
| `test_user_can_view_sent_requests` | GET returns requests where requester |
| `test_user_can_view_received_requests` | GET returns requests where owner |
| `test_request_history_retained` | All statuses in history |
| `test_non_owner_cannot_approve` | Non-owner → 403 |
| `test_requests_are_tenant_scoped` | Cross-tenant isolation |

---

## What This Module Does NOT Include

- Manual revocation of granted access (Module 16)
- Secure external sharing links (Module 17, 18)
- Activity logging (Module 20)
