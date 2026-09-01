# Module 21 — Security Alerts

| Field | Value |
|---|---|
| **Module** | 21 |
| **Name** | Security Alerts |
| **Dependencies** | Module 20 |
| **Status** | Not Started |

---

## Objective

Build the security alert system — new device login alerts, expiring access alerts, and suspicious activity detection. Alerts are delivered via in-app notifications and email.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| AS-08 | Security alerts: new-device login | P1 |
| AS-09 | Security alerts: suspicious activity (multiple failed logins) | P2 |
| AS-10 | Security alerts: expiring access | P1 |

---

## Tasks

### 21.1 Security Alerts Table & Model

- [ ] Create `security_alerts` migration:

```
security_alerts
  id              -- bigIncrements
  tenant_id       -- foreignId, nullable
  user_id         -- foreignId (constrained, cascadeOnDelete)
  type            -- enum: 'new_device_login', 'suspicious_activity', 'expiring_access', 'access_expired'
  severity        -- enum: 'info', 'warning', 'critical'
  title           -- string
  message         -- text
  properties      -- json, nullable (context: IP, device, browser, etc.)
  read_at         -- timestamp, nullable
  dismissed_at    -- timestamp, nullable
  created_at
  updated_at

  index(user_id, read_at)
  index(tenant_id, created_at)
  index(type, created_at)
```

- [ ] Create `SecurityAlert` model:
  - `$fillable`: `tenant_id`, `user_id`, `type`, `severity`, `title`, `message`, `properties`, `read_at`, `dismissed_at`
  - `$casts`: `properties` → `array`, `read_at` → `datetime`, `dismissed_at` → `datetime`
  - Scope: `scopeUnread(Builder)`, `scopeForUser(Builder, User)`

### 21.2 New Device Login Detection

- [ ] Create `app/Services/DeviceDetector.php`:
  - Parse user agent to extract: browser, OS, device type
  - Generate device fingerprint (hash of browser + OS + IP subnet)
  - Store device info in `user_devices` table

- [ ] Create `user_devices` migration:

```
user_devices
  id              -- bigIncrements
  user_id         -- foreignId (constrained, cascadeOnDelete)
  device_fingerprint -- string
  browser         -- string
  os              -- string
  device_type     -- string (desktop, mobile, tablet)
  ip_address      -- string
  last_seen_at    -- timestamp
  first_seen_at   -- timestamp
  created_at
  updated_at

  unique(user_id, device_fingerprint)
  index(user_id)
```

- [ ] On login:
  - Calculate device fingerprint
  - Check if fingerprint exists for this user
  - If new → create `SecurityAlert` (type: `new_device_login`, severity: `info`)
  - If new → send `NewDeviceLogin` notification
  - Update `last_seen_at` on existing device

### 21.3 Suspicious Activity Detection

- [ ] Track failed login attempts per IP:
  - After 5 failed attempts in 15 minutes → create `SecurityAlert` (type: `suspicious_activity`, severity: `warning`)
  - Alert is created for the user whose email was used in failed attempts
  - Also create a tenant-wide alert if the email belongs to a tenant member

- [ ] Create `app/Console/Commands/DetectSuspiciousActivity.php`:
  - Scheduled every 15 minutes
  - Scans activity logs for patterns:
    - Multiple failed logins from same IP
    - Multiple access revocations in short time
    - Multiple emergency revokes
  - Creates alerts as needed

### 21.4 Expiring Access Alerts

- [ ] Create `app/Jobs/CheckExpiringAccess.php` (implements `ShouldQueue`):
  - Scheduled hourly
  - Finds access grants where `expires_at` is within 24 hours
  - Creates `SecurityAlert` (type: `expiring_access`, severity: `info`) for the grantee
  - Only creates one alert per grant (check existing)

- [ ] Create `app/Jobs/CheckExpiredAccess.php`:
  - Scheduled hourly
  - Finds grants that expired in the last hour
  - Creates `SecurityAlert` (type: `access_expired`, severity: `info`) for the grantee

### 21.5 Notifications

- [ ] `app/Notifications/NewDeviceLogin.php`:
  - Sent to user on new device login
  - Includes: device, browser, OS, IP, location (if available), timestamp
  - Channels: mail, database

- [ ] `app/Notifications/SuspiciousActivityAlert.php`:
  - Sent to user on suspicious activity
  - Includes: activity type, IP, count, time range
  - Channels: mail, database

- [ ] `app/Notifications/AccessExpiringAlert.php`:
  - Sent to user when access is expiring soon
  - Includes: resource name, expiration time
  - Channels: database

### 21.6 API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/security-alerts` | List user's security alerts (unread first) |
| `GET` | `/api/v1/security-alerts/unread-count` | Count of unread alerts |
| `POST` | `/api/v1/security-alerts/{alert}/read` | Mark alert as read |
| `POST` | `/api/v1/security-alerts/{alert}/dismiss` | Dismiss alert |
| `POST` | `/api/v1/security-alerts/read-all` | Mark all as read |
| `GET` | `/api/v1/devices` | List user's known devices |
| `DELETE` | `/api/v1/devices/{device}` | Revoke a device (force logout) |

### 21.7 Controller

- [ ] `SecurityAlertController.php`:
  - `index()` — list alerts for current user, sorted by unread + created_at desc
  - `unreadCount()` — return count of unread alerts
  - `markRead()` — set `read_at`
  - `dismiss()` — set `dismissed_at`
  - `markAllRead()` — set `read_at` on all unread
- [ ] `DeviceController.php`:
  - `index()` — list user's devices
  - `destroy()` — revoke device (revoke all tokens from that device)

### 21.8 API Resource

- [ ] `SecurityAlertResource.php`:
  - `id`, `type`, `severity`, `title`, `message`, `properties`, `read_at`, `dismissed_at`, `created_at`

- [ ] `DeviceResource.php`:
  - `id`, `browser`, `os`, `device_type`, `ip_address`, `last_seen_at`, `first_seen_at`, `is_current_device`

### 21.9 Scheduled Commands

```php
// app/Console/Kernel.php
$schedule->job(new CheckExpiringAccess)->hourly();
$schedule->job(new CheckExpiredAccess)->hourly();
$schedule->command('security:detect-suspicious')->everyFifteenMinutes();
```

### 21.10 Routes

```php
Route::middleware(['auth:sanctum', 'tenant.resolve'])->group(function () {
    Route::get('v1/security-alerts', [SecurityAlertController::class, 'index']);
    Route::get('v1/security-alerts/unread-count', [SecurityAlertController::class, 'unreadCount']);
    Route::post('v1/security-alerts/read-all', [SecurityAlertController::class, 'markAllRead']);
    Route::post('v1/security-alerts/{alert}/read', [SecurityAlertController::class, 'markRead']);
    Route::post('v1/security-alerts/{alert}/dismiss', [SecurityAlertController::class, 'dismiss']);

    Route::get('v1/devices', [DeviceController::class, 'index']);
    Route::delete('v1/devices/{device}', [DeviceController::class, 'destroy']);
});
```

---

## Acceptance Criteria

- [ ] Login from a new device creates a security alert and sends notification
- [ ] Login from a known device does NOT create an alert
- [ ] 5 failed login attempts in 15 minutes create a suspicious activity alert
- [ ] Access grants expiring within 24 hours create an expiring_access alert
- [ ] Expired access grants create an access_expired alert
- [ ] User can list their security alerts (unread first)
- [ ] User can get unread alert count
- [ ] User can mark individual alerts as read
- [ ] User can mark all alerts as read
- [ ] User can dismiss alerts
- [ ] User can list their known devices
- [ ] User can revoke a device (forces logout from that device)
- [ ] Alerts include contextual properties (IP, device, browser, etc.)
- [ ] Scheduled commands run automatically

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_new_device_login_creates_alert` | First login from new device → alert |
| `test_known_device_no_alert` | Second login from same device → no alert |
| `test_failed_logins_create_alert` | 5 failures → suspicious_activity alert |
| `test_expiring_access_creates_alert` | Grant expiring <24h → alert |
| `test_expired_access_creates_alert` | Grant expired → alert |
| `test_user_can_list_alerts` | GET returns alerts |
| `test_user_can_get_unread_count` | GET returns count |
| `test_user_can_mark_read` | POST sets read_at |
| `test_user_can_mark_all_read` | POST sets read_at on all |
| `test_user_can_dismiss_alert` | POST sets dismissed_at |
| `test_user_can_list_devices` | GET returns known devices |
| `test_user_can_revoke_device` | DELETE removes device |
| `test_alerts_sorted_unread_first` | Unread before read |

---

## What This Module Does NOT Include

- Geolocation of IP addresses (post-MVP)
- Real-time push notifications (post-MVP)
- Machine learning for anomaly detection (post-MVP)
- SSO/SAML integration (post-MVP)
