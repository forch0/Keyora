# Module 14 — Temporary Access

| Field | Value |
|---|---|
| **Module** | 14 |
| **Name** | Temporary Access (Time-Limited & One-Time Access) |
| **Dependencies** | Module 08, Module 09 |
| **Status** | Not Started |

---

## Objective

Build the temporary access system on top of the existing `AccessGrant` model. This module adds preset durations, custom durations, first-view-started access, maximum view limits, one-time access, automatic expiration, and expiration warnings.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| TA-01 | Grant temporary access with start time (immediate or scheduled) | P0 |
| TA-02 | Access starts on first view (clock starts when recipient opens resource) | P0 |
| TA-03 | Preset durations: 15 min, 30 min, 1 hour, 24 hours | P0 |
| TA-04 | Custom duration (user-specified start and end datetime) | P1 |
| TA-05 | Maximum number of views (e.g., 1-time, 3-time) | P0 |
| TA-06 | One-time access (self-destructs after single view) | P0 |
| TA-07 | Automatic expiration when time limit or view limit reached | P0 |
| TA-08 | Expiration countdown visible to recipient | P1 |
| TA-09 | Expiration warnings (notification before expiry) | P1 |

---

## Tasks

### 14.1 Extend GrantAccessAction

- [ ] Update `GrantAccessAction` (from Module 09) to support temporary access parameters:
  - `expires_at` — when access expires (already in schema)
  - `starts_at` — when access starts (already in schema)
  - `start_on_first_view` — boolean, clock starts on first open (already in schema)
  - `max_views` — max number of views (already in schema)

- [ ] Add preset duration helper:

```php
// app/Enums/AccessDuration.php
enum AccessDuration: string
{
    case FifteenMinutes = '15m';
    case ThirtyMinutes = '30m';
    case OneHour = '1h';
    case TwentyFourHours = '24h';

    public function toSeconds(): int
    {
        return match($this) {
            self::FifteenMinutes => 900,
            self::ThirtyMinutes => 1800,
            self::OneHour => 3600,
            self::TwentyFourHours => 86400,
        };
    }

    public function toCarbon(): Carbon
    {
        return now()->addSeconds($this->toSeconds());
    }
}
```

### 14.2 API Endpoints — Temporary Access

Extend the existing access grant endpoints to accept temporary access parameters:

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/vault/items/{item}/access` | Grant access (with optional temp params) |
| `POST` | `/api/v1/files/{file}/access` | Grant file access (with optional temp params) |
| `POST` | `/api/v1/notes/{note}/access` | Grant note access (with optional temp params) |

**Extended request body:**
```json
{
  "subject_type": "User",
  "subject_id": 5,
  "permission": "view",
  "duration": "1h",
  "max_views": 1,
  "start_on_first_view": true,
  "starts_at": "2026-09-01T10:00:00Z"
}
```

Either `duration` (preset) OR `expires_at` (custom datetime) can be provided, not both.

### 14.3 Update GrantAccessRequest

- [ ] Add validation rules:
  - `duration`: nullable, in:15m,30m,1h,24h
  - `expires_at`: nullable, date, after:now
  - `max_views`: nullable, integer, min:1
  - `start_on_first_view`: nullable, boolean
  - `starts_at`: nullable, date, after:now
  - Validation: `duration` and `expires_at` are mutually exclusive
  - If `start_on_first_view` is true, `starts_at` must be null

### 14.4 First-View Tracking

- [ ] When a user views a resource (via `show()` on any controller):
  - Find active access grants for this user on this resource
  - For grants where `start_on_first_view = true` and `first_viewed_at IS NULL`:
    - Set `first_viewed_at = now()`
    - Calculate `expires_at` based on duration from `first_viewed_at` (if using preset duration)
  - Increment `views_count` on all matching grants
  - Check if `views_count >= max_views` → if so, auto-revoke (set `revoked_at`, reason = 'view_limit_reached')

### 14.5 View Tracking Service

- [ ] Create `app/Services/ViewTracker.php`:

```php
class ViewTracker
{
    public function recordView(User $user, Model $resource): void
    {
        // 1. Find active grants for this user + resource
        // 2. For start_on_first_view grants: set first_viewed_at if null
        // 3. Increment views_count
        // 4. Auto-revoke if views_count >= max_views
        // 5. Dispatch ResourceViewed event
    }
}
```

- [ ] Call `ViewTracker::recordView()` in every resource `show()` method (vault items, files, notes)

### 14.6 Expiration Check Command

- [ ] Create `app/Console/Commands/CheckExpiredAccess.php`:
  - Scheduled every minute
  - Finds grants where `expires_at <= now()` and `revoked_at IS NULL`
  - Sets `revoked_at = now()`, `revoke_reason = 'expired'`
  - Dispatches `AccessExpired` event for each

```php
// app/Console/Kernel.php
$schedule->command('access:check-expired')->everyMinute();
```

### 14.7 Expiration Warning Job

- [ ] Create `app/Jobs/SendExpirationWarning.php` (implements `ShouldQueue`):
  - Finds grants where `expires_at` is within 24 hours (or 1 hour for short durations)
  - Sends notification to grantee
  - Only sends once per grant (track with a `warning_sent_at` column or flag)

- [ ] Add `warning_sent_at` timestamp (nullable) to `access_grants` table

- [ ] Schedule:
```php
$schedule->job(new SendExpirationWarning)->hourly();
```

### 14.8 Expiration Countdown Endpoint

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/vault/items/{item}/access/countdown` | Get expiration countdown for current user's grant |

- [ ] Returns:
```json
{
  "data": {
    "grant_id": 42,
    "expires_at": "2026-09-01T11:00:00Z",
    "seconds_remaining": 3600,
    "views_remaining": 2,
    "max_views": 3,
    "started_at": "2026-09-01T10:00:00Z",
    "start_on_first_view": true,
    "first_viewed_at": "2026-09-01T10:05:00Z"
  }
}
```

### 14.9 Notifications

- [ ] `app/Notifications/AccessExpiringSoon.php`:
  - Sent to grantee when access is about to expire
  - Includes: resource name, expiration time, time remaining
  - Channels: mail, database

- [ ] `app/Notifications/AccessExpiredNotification.php`:
  - Sent to grantee when access has expired
  - Includes: resource name, expiration time
  - Channels: database

### 14.10 Events

- [ ] `app/Events/AccessExpired.php` — carries `AccessGrant`
- [ ] `app/Events/ResourceViewed.php` — carries `Model $resource`, `User $user`

### 14.11 One-Time Access

- [ ] One-time access is a special case of temporary access:
  - `max_views = 1`
  - After the first view, `views_count` becomes 1, which equals `max_views`
  - `ViewTracker` auto-revokes the grant
  - Subsequent access attempts return `403`

---

## Acceptance Criteria

- [ ] User can grant access with a preset duration (15m, 30m, 1h, 24h)
- [ ] User can grant access with a custom datetime range (starts_at + expires_at)
- [ ] User can grant access that starts on first view (clock starts when recipient opens resource)
- [ ] User can grant access with a maximum view count
- [ ] User can grant one-time access (max_views = 1, self-destructs after single view)
- [ ] After max views is reached, access is automatically revoked
- [ ] After expiration time, access is automatically revoked (by scheduled command)
- [ ] Grantee can see expiration countdown (time remaining, views remaining)
- [ ] Grantee receives notification before access expires
- [ ] Grantee receives notification when access has expired
- [ ] Scheduled command runs every minute to check expired grants
- [ ] `duration` and `expires_at` are mutually exclusive in request validation
- [ ] `start_on_first_view` with `starts_at` is invalid (validation error)

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_grant_with_preset_duration` | 15m duration sets correct expires_at |
| `test_grant_with_custom_duration` | Custom starts_at + expires_at works |
| `test_grant_with_start_on_first_view` | first_viewed_at null until first view |
| `test_first_view_starts_clock` | Viewing sets first_viewed_at and calculates expires_at |
| `test_grant_with_max_views` | max_views = 3 allows 3 views |
| `test_one_time_access_self_destructs` | max_views = 1, second view → 403 |
| `test_auto_revoke_on_view_limit` | views_count >= max_views → revoked |
| `test_auto_revoke_on_expiration` | expires_at in past → revoked by command |
| `test_expiration_countdown_endpoint` | GET returns time/views remaining |
| `test_expiration_warning_sent` | Notification sent 24h before expiry |
| `test_expiration_notification_sent` | Notification sent when expired |
| `test_duration_and_expires_at_mutually_exclusive` | Both provided → 422 |
| `test_start_on_first_view_with_starts_at_invalid` | Both provided → 422 |
| `test_scheduled_command_revokes_expired` | Command revokes all expired grants |
| `test_view_count_increments_on_view` | Each view increments views_count |

---

## What This Module Does NOT Include

- Access requests workflow (Module 15)
- Manual revocation (Module 16)
- Secure external sharing links (Module 17, 18)
- Activity logging (Module 20)
