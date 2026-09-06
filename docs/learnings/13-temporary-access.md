# 14 — How Module 14 (Temporary Access) Was Built

| Field | Value |
|---|---|
| **Module** | 14 — Temporary Access |
| **Date** | 2026-09-02 |
| **Spec** | `docs/modules/14-temporary-access.md` |

---

## Goal

Build time-limited and one-time access on top of the existing `AccessGrant` model. Add preset durations, custom datetime ranges, first-view-started access, maximum view limits, one-time access, automatic expiration, and expiration warnings.

---

## Decisions Made Before Writing Code

### 1. Reuse existing schema

The `access_grants` table from Module 08 already had `expires_at`, `starts_at`, `start_on_first_view`, `first_viewed_at`, `max_views`, and `views_count`. Only one new column was needed: `warning_sent_at` (to track whether the expiration warning notification was already sent).

### 2. Duration resolution in controllers

The `AccessDuration` enum converts preset strings (`15m`, `30m`, `1h`, `24h`) to `Carbon` expiry times. Each access controller (vault, file, note) has a `resolveExpiresAt()` helper that converts `duration` to `expires_at` before calling `GrantAccessAction`. This keeps the action signature unchanged.

### 3. ViewTracker as a service

`ViewTracker::recordView()` is called in the `show()` method of `SecureNoteController` and `SecureFileController`. It:
- Sets `first_viewed_at` on `start_on_first_view` grants
- Increments `views_count`
- Auto-revokes when `views_count >= max_views`
- Dispatches `ResourceViewed` event

### 4. First-view chicken-and-egg problem

The original `hasStarted()` returned `false` when `start_on_first_view=true` and `first_viewed_at=null`, which blocked the first view entirely. The fix: `grantIsCurrentlyValid()` in `AccessResolver` now allows `start_on_first_view` grants even when `hasStarted()` returns false, so the first view can happen and start the clock. Also, `isExpired()` returns `false` for `start_on_first_view` grants with no `first_viewed_at` (the clock hasn't started yet).

### 5. Scheduled commands

- `access:check-expired` runs every minute, revokes expired grants, dispatches `AccessExpired` event, and sends `AccessExpiredNotification` to user grantees.
- `SendExpirationWarning` job runs hourly, finds grants expiring within 24 hours, sends `AccessExpiringSoon` notification, and sets `warning_sent_at` to avoid duplicates.

---

## Files Created

| File | Purpose |
|---|---|
| `app/Enums/AccessDuration.php` | Preset duration enum (15m, 30m, 1h, 24h) |
| `app/Services/ViewTracker.php` | Records views, starts clocks, auto-revokes |
| `app/Events/ResourceViewed.php` | Event dispatched on resource view |
| `app/Events/AccessExpired.php` | Event dispatched on grant expiration |
| `app/Notifications/AccessExpiringSoon.php` | Warning notification (mail + database) |
| `app/Notifications/AccessExpiredNotification.php` | Expiration notification (mail + database) |
| `app/Console/Commands/CheckExpiredAccess.php` | Scheduled command (every minute) |
| `app/Jobs/SendExpirationWarning.php` | Scheduled job (hourly) |
| `tests/Feature/Api/V1/Access/TemporaryAccessTest.php` | 15 feature tests |

---

## Files Modified

| File | Change |
|---|---|
| `app/Models/AccessGrant.php` | Added `warning_sent_at` to fillable/casts; fixed `isExpired()` for start_on_first_view |
| `app/Services/AccessResolver.php` | Allow start_on_first_view grants before first view |
| `app/Http/Requests/Access/GrantAccessRequest.php` | Added `duration` validation + mutual exclusivity |
| `app/Http/Controllers/Api/V1/AccessGrantController.php` | Added `countdown()` + `resolveExpiresAt()` |
| `app/Http/Controllers/Api/V1/FileAccessController.php` | Added `resolveExpiresAt()` |
| `app/Http/Controllers/Api/V1/NoteAccessController.php` | Added `resolveExpiresAt()` |
| `app/Http/Controllers/Api/V1/SecureNoteController.php` | Integrated ViewTracker into `show()` |
| `app/Http/Controllers/Api/V1/SecureFileController.php` | Integrated ViewTracker into `show()` |
| `routes/api.php` | Added countdown route |
| `routes/console.php` | Registered scheduled command + job |
| `tests/Feature/Api/V1/Access/AccessGrantTest.php` | Updated start_on_first_view test for new behavior |

---

## New Endpoint

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/vault/items/{item}/access/countdown` | Get expiration countdown for current user |

---

## Bugs Found and Fixed During Implementation

### 1. First-view chicken-and-egg

**Cause:** `hasStarted()` returned `false` for `start_on_first_view` grants with no `first_viewed_at`, blocking the first view entirely — but the first view is what starts the clock.

**Fix:** Modified `AccessResolver::grantIsCurrentlyValid()` to allow `start_on_first_view` grants even when `hasStarted()` returns false. Also fixed `isExpired()` to return `false` for `start_on_first_view` grants with no `first_viewed_at` (clock hasn't started).

### 2. PHPStan: `Model::notify()` undefined

**Cause:** `$grant->subject` returns a `Model`, but `notify()` is a `Notifiable` trait method on `User`.

**Fix:** Used `instanceof \App\Models\User` check before calling `notify()`.

### 3. Existing test expected old behavior

**Cause:** `AccessGrantTest::test_start_on_first_view` asserted that a `start_on_first_view` grant with no `first_viewed_at` returns `false` from `$resolver->can()`.

**Fix:** Updated the test to assert `true` — the first view is allowed to start the clock.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 205 tests, 576 assertions (15 new + 190 from Modules 02-13) |
