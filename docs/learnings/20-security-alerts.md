# 21 — How Module 21 (Security Alerts) Was Built

| Field | Value |
|---|---|
| **Module** | 21 — Security Alerts |
| **Date** | 2026-09-02 |
| **Spec** | `docs/modules/21-security-alerts.md` |

---

## Goal

Build the security alert system — new device login alerts, expiring access alerts, suspicious activity detection, and device management. Alerts are delivered via in-app notifications and email.

---

## Decisions Made Before Writing Code

### 1. Device fingerprinting by subnet

The `DeviceDetector` service generates a fingerprint from `browser + OS + IP subnet (/24)`. This means the same browser+OS from the same network doesn't trigger a new device alert. A different subnet does trigger an alert.

### 2. Security alert types as constants

`SecurityAlert` model has constants for types (`TYPE_NEW_DEVICE_LOGIN`, `TYPE_SUSPICIOUS_ACTIVITY`, `TYPE_EXPIRING_ACCESS`, `TYPE_ACCESS_EXPIRED`) and severity levels (`SEVERITY_INFO`, `SEVERITY_WARNING`, `SEVERITY_CRITICAL`).

### 3. Scheduled jobs for access alerts

Two jobs run hourly:
- `CheckExpiringAccess` — finds grants expiring within 24 hours, creates `expiring_access` alert
- `CheckExpiredAccess` — finds grants that expired in the last hour, creates `access_expired` alert

Both check for existing alerts to avoid duplicates.

### 4. Suspicious activity detection via command

The `security:detect-suspicious` command runs every 15 minutes and scans activity logs for:
- 5+ failed logins from the same IP in 15 minutes
- 3+ emergency revocations in 15 minutes

### 5. Device management endpoints

Users can list their known devices and revoke (delete) a device. Revoking forces re-detection on next login, which will trigger a new device alert.

---

## Files Created

| File | Purpose |
|---|---|
| `app/Models/SecurityAlert.php` | Security alert model with type/severity constants |
| `app/Models/UserDevice.php` | Device tracking model |
| `app/Services/DeviceDetector.php` | Device fingerprinting and detection |
| `app/Http/Controllers/Api/V1/SecurityAlertController.php` | Alert CRUD (list, read, dismiss) |
| `app/Http/Controllers/Api/V1/DeviceController.php` | Device list + revoke |
| `app/Http/Resources/V1/SecurityAlertResource.php` | Alert API resource |
| `app/Http/Resources/V1/DeviceResource.php` | Device API resource |
| `app/Notifications/NewDeviceLogin.php` | New device notification (mail + database) |
| `app/Notifications/SuspiciousActivityAlert.php` | Suspicious activity notification |
| `app/Notifications/AccessExpiringAlert.php` | Access expiring notification |
| `app/Jobs/CheckExpiringAccess.php` | Hourly job for expiring access alerts |
| `app/Jobs/CheckExpiredAccess.php` | Hourly job for expired access alerts |
| `app/Console/Commands/DetectSuspiciousActivity.php` | Suspicious activity detection command |
| `database/factories/SecurityAlertFactory.php` | Factory |
| `database/factories/UserDeviceFactory.php` | Factory |
| `tests/Feature/Api/V1/Security/SecurityAlertTest.php` | 13 feature tests |

---

## New Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/security-alerts` | List user's alerts (unread first) |
| GET | `/api/v1/security-alerts/unread-count` | Count of unread alerts |
| POST | `/api/v1/security-alerts/{alert}/read` | Mark alert as read |
| POST | `/api/v1/security-alerts/{alert}/dismiss` | Dismiss alert |
| POST | `/api/v1/security-alerts/read-all` | Mark all as read |
| GET | `/api/v1/devices` | List known devices |
| DELETE | `/api/v1/devices/{device}` | Revoke a device |

---

## Scheduled Tasks

| Schedule | Task | Description |
|---|---|---|
| Hourly | `CheckExpiringAccess` job | Create expiring access alerts |
| Hourly | `CheckExpiredAccess` job | Create expired access alerts |
| Every 15 min | `security:detect-suspicious` | Detect suspicious activity patterns |

---

## Bugs Found and Fixed During Implementation

### 1. PHPStan — `expires_at` type inference

**Cause:** PHPStan couldn't infer that `expires_at` is a `Carbon` instance from the `$casts` array when accessed via `$grant->expires_at`.

**Fix:** Used `$grant->getAttribute('expires_at')` with an `instanceof Carbon` check before calling methods.

### 2. PHPStan — `User::find()` return type

**Cause:** `User::find($attempt->user_id)` where `user_id` is `mixed` from a stdClass query result. PHPStan inferred the return as `User|Collection`.

**Fix:** Cast to `(int)` before passing to `find()`.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 313 tests, 813 assertions (13 new + 300 from Modules 02-20) |
