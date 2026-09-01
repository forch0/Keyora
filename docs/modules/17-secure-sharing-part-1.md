# Module 17 — Secure External Sharing: Part 1 (Link Creation)

| Field | Value |
|---|---|
| **Module** | 17 |
| **Name** | Secure External Sharing — Link Creation |
| **Dependencies** | Module 08, Module 09 |
| **Status** | Not Started |

---

## Objective

Build the secure share link system — create password-protected, OTP-protected, expiring, and one-time links for sharing resources externally (with non-platform users). This module covers link creation and configuration.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| SS-01 | Share internally (individuals, teams, company-wide) | P0 → Module 09 |
| SS-02 | Share externally via secure link | P0 |
| SS-03 | Links can be password-protected | P0 |
| SS-04 | Links can be OTP-protected (one-time PIN sent via email) | P1 |
| SS-05 | External access requires email verification | P1 |
| SS-06 | Link expiration (time-based) | P0 |
| SS-07 | Link expiration on first view (expires N hours after first open) | P0 |
| SS-08 | Maximum views per link | P1 |
| SS-09 | One-time links (self-destruct after single view) | P0 |
| SS-10 | Disable downloads on shared links | P0 |
| SS-11 | Revoke links at any time | P0 → Module 16 |

---

## Tasks

### 17.1 Secure Links Table & Model

- [ ] Create `secure_links` migration:

```
secure_links
  id                  -- bigIncrements
  tenant_id           -- foreignId (constrained, cascadeOnDelete)
  uuid                -- uuid, unique (public identifier in URL)
  resource_type       -- string (morphs: 'VaultItem', 'SecureFile', 'SecureNote')
  resource_id         -- unsignedBigInteger
  created_by          -- foreignId (users)
  recipient_email     -- string, nullable (for OTP and tracking)
  password_hash       -- string, nullable (bcrypt hash of link password)
  otp_code_hash       -- string, nullable (hash of OTP code)
  otp_sent_at         -- timestamp, nullable
  email_verified_at   -- timestamp, nullable
  permission          -- enum: 'view', 'download' (external users can only view or download)
  download_enabled    -- boolean, default true
  expires_at          -- timestamp, nullable (absolute expiration)
  first_view_expires_hours -- integer, nullable (expires N hours after first view)
  max_views           -- integer, nullable (null = unlimited)
  views_count         -- integer, default 0
  first_viewed_at     -- timestamp, nullable
  is_one_time         -- boolean, default false (shortcut for max_views = 1)
  revoked_at          -- timestamp, nullable
  revoked_by          -- foreignId, nullable (users)
  revoke_reason       -- string, nullable
  created_at
  updated_at

  index(tenant_id)
  index(uuid)
  index(resource_type, resource_id)
  index(created_by)
  index(expires_at)
  index(revoked_at)
```

- [ ] Create `SecureLink` model:
  - `use BelongsToTenant` trait
  - `$fillable`: all columns above
  - `$casts`: `expires_at` → `datetime`, `first_viewed_at` → `datetime`, `otp_sent_at` → `datetime`, `email_verified_at` → `datetime`, `revoked_at` → `datetime`, `download_enabled` → `boolean`, `is_one_time` → `boolean`, `views_count` → `integer`, `max_views` → `integer`, `first_view_expires_hours` → `integer`
  - Relationships: `tenant()`, `creator()` → `belongsTo(User::class, 'created_by')`, `resource()` → `morphTo()`
  - Scope: `scopeActive(Builder)` — where `revoked_at IS NULL` and not expired
  - Helper: `isActive(): bool`
  - Helper: `isExpired(): bool`
  - Helper: `url(): string` — returns full URL using UUID

### 17.2 API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/vault/items/{item}/share-links` | Create secure link for vault item |
| `POST` | `/api/v1/files/{file}/share-links` | Create secure link for file |
| `POST` | `/api/v1/notes/{note}/share-links` | Create secure link for note |
| `GET` | `/api/v1/vault/items/{item}/share-links` | List links for a resource |
| `GET` | `/api/v1/files/{file}/share-links` | List links for a file |
| `GET` | `/api/v1/notes/{note}/share-links` | List links for a note |
| `DELETE` | `/api/v1/share-links/{link}` | Revoke a link |

### 17.3 Controller

- [ ] `SecureLinkController.php`:
  - `store()` — create link with options (password, OTP, expiration, max views, one-time, download enabled)
  - `index()` — list links for a resource (creator or admin only)
  - `destroy()` — revoke a link

### 17.4 Action

- [ ] `app/Actions/CreateSecureLinkAction.php`:

```php
public function __invoke(
    User $createdBy,
    Model $resource,
    ?string $recipientEmail = null,
    ?string $password = null,
    bool $requireOtp = false,
    bool $requireEmailVerification = false,
    string $permission = 'view',
    bool $downloadEnabled = true,
    ?Carbon $expiresAt = null,
    ?int $firstViewExpiresHours = null,
    ?int $maxViews = null,
    bool $isOneTime = false,
): SecureLink
```

- Verify creator has `share` permission on resource
- Generate UUID for the link
- Hash password with bcrypt if provided
- Generate and hash OTP code if required
- Create `SecureLink` record
- If `requireOtp` and `recipientEmail` — send OTP email
- Dispatch `SecureLinkCreated` event
- Return link (include plaintext URL and OTP code in response — only shown once)

### 17.5 Form Requests

- [ ] `CreateSecureLinkRequest`:
  - `recipient_email`: nullable, email
  - `password`: nullable, string, min:4, max:100
  - `require_otp`: nullable, boolean
  - `require_email_verification`: nullable, boolean
  - `permission`: required, in:view,download
  - `download_enabled`: nullable, boolean
  - `expires_at`: nullable, date, after:now
  - `first_view_expires_hours`: nullable, integer, min:1, max:720 (30 days)
  - `max_views`: nullable, integer, min:1
  - `is_one_time`: nullable, boolean
  - Validation: `is_one_time` and `max_views` are mutually exclusive (one-time sets max_views = 1)
  - Validation: `expires_at` and `first_view_expires_hours` can coexist (absolute + relative)
  - Validation: `require_otp` requires `recipient_email`

### 17.6 API Resource

- [ ] `SecureLinkResource.php`:
  - `id`, `uuid`, `url` (full URL), `resource` (type + id + name), `recipient_email`, `permission`, `download_enabled`, `expires_at`, `first_view_expires_hours`, `max_views`, `views_count`, `is_one_time`, `is_active`, `is_expired`, `first_viewed_at`, `revoked_at`, `revoke_reason`, `has_password`, `requires_otp`, `requires_email_verification`, `created_at`
  - `has_password` — boolean (never expose the password hash)
  - `otp_code` — only included in the creation response (not in list/show)

### 17.7 OTP Generation

- [ ] Generate 6-digit numeric OTP code
- [ ] Hash with bcrypt for storage
- [ ] Send plaintext OTP to recipient email
- [ ] OTP is valid for 10 minutes
- [ ] OTP is single-use (cleared after successful verification in Module 18)

### 17.8 Notification

- [ ] `app/Notifications/SecureLinkOtpNotification.php`:
  - Sent to recipient email
  - Includes: OTP code, link URL, sender name, resource name
  - Channels: mail only

- [ ] `app/Notifications/SecureLinkCreatedNotification.php`:
  - Sent to link creator (confirmation)
  - Channels: database

### 17.9 Event

- [ ] `app/Events/SecureLinkCreated.php` — carries `SecureLink`, `User $createdBy`

### 17.10 Policy

- [ ] `SecureLinkPolicy.php`:
  - `create()` — user must have `share` permission on the resource
  - `view()` — user is creator or admin/owner
  - `delete()` — user is creator or admin/owner or has `manage` permission on resource

### 17.11 Routes

```php
Route::middleware(['auth:sanctum', 'tenant.resolve'])->group(function () {
    Route::post('v1/vault/items/{item}/share-links', [SecureLinkController::class, 'store']);
    Route::get('v1/vault/items/{item}/share-links', [SecureLinkController::class, 'index']);
    Route::post('v1/files/{file}/share-links', [SecureLinkController::class, 'store']);
    Route::get('v1/files/{file}/share-links', [SecureLinkController::class, 'index']);
    Route::post('v1/notes/{note}/share-links', [SecureLinkController::class, 'store']);
    Route::get('v1/notes/{note}/share-links', [SecureLinkController::class, 'index']);
    Route::delete('v1/share-links/{link}', [SecureLinkController::class, 'destroy']);
});
```

---

## Acceptance Criteria

- [ ] User can create a secure link for a vault item, file, or note
- [ ] Link has a UUID-based URL for external access
- [ ] Link can be password-protected (password hashed with bcrypt)
- [ ] Link can be OTP-protected (6-digit code sent to recipient email)
- [ ] OTP requires recipient email to be set
- [ ] Link can have absolute expiration (`expires_at`)
- [ ] Link can have first-view expiration (expires N hours after first open)
- [ ] Link can have maximum views limit
- [ ] Link can be one-time (self-destructs after single view)
- [ ] Link can disable downloads (`download_enabled = false`)
- [ ] User can list all links for a resource
- [ ] User can revoke a link
- [ ] OTP code is only shown once in creation response
- [ ] Password is never returned in any response
- [ ] Only users with `share` permission can create links
- [ ] Links are tenant-scoped

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_can_create_basic_link` | POST creates link with UUID |
| `test_can_create_password_protected_link` | Password hashed in DB |
| `test_can_create_otp_protected_link` | OTP hashed, email sent |
| `test_otp_requires_email` | require_otp without email → 422 |
| `test_can_create_expiring_link` | expires_at set |
| `test_can_create_first_view_expiring_link` | first_view_expires_hours set |
| `test_can_create_max_views_link` | max_views set |
| `test_can_create_one_time_link` | is_one_time sets max_views = 1 |
| `test_can_disable_downloads` | download_enabled = false |
| `test_can_list_links_for_resource` | GET returns links |
| `test_can_revoke_link` | DELETE sets revoked_at |
| `test_password_never_in_response` | No password in any response |
| `test_otp_only_in_creation_response` | OTP not in list/show |
| `test_only_share_permission_can_create` | No share permission → 403 |
| `test_links_are_tenant_scoped` | Cross-tenant isolation |

---

## What This Module Does NOT Include

- Link access (opening the link, verifying password/OTP) — Module 18
- Link activity tracking (who accessed, when) — Module 18
- Activity logging (Module 20)
