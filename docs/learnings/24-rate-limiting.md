# 27 — How Module 27 (Rate Limiting) Was Built

| Field | Value |
|---|---|
| **Module** | 27 — Rate Limiting & API Throttling |
| **Date** | 2026-09-02 |
| **Spec** | `docs/modules/27-rate-limiting.md` |

---

## Goal

Apply granular, configurable rate limiting across all API endpoints. Previously only auth endpoints (login, register, forgot-password) were throttled. This module extends throttling to all read, write, and sensitive endpoints.

---

## Decisions Made Before Writing Code

### 1. Config-driven rate limit profiles

Created `config/rate_limits.php` with profiles:
- `read` — 60/min (GET endpoints)
- `write` — 30/min (POST/PUT/DELETE)
- `sensitive` — 10/min (offboard, revoke-all, 2FA, password change, secure link creation, vault deletion)
- `auth.login`, `auth.register` — 5/min per IP
- `auth.forgot_password` — 3/min per IP
- `2fa.verify` — 5/min per IP

All limits are configurable. The middleware reads from config at runtime, so changing config changes the limits without code changes.

### 2. Two middleware: RateLimitByProfile + TenantRateLimit

- `RateLimitByProfile` — the main middleware, used on all routes. Takes a profile name as a parameter (`rate.limit:read`, `rate.limit:write`, `rate.limit:sensitive`). Auth/2FA profiles are keyed by IP; other profiles are keyed by user ID.
- `TenantRateLimit` — per-tenant middleware with plan-tier multipliers (free=1x, team=2x, business=3x, enterprise=5x). Available as `tenant.rate` alias for future use.

### 3. Dual middleware on write/sensitive routes

Read endpoints get `rate.limit:read` at the group level. Write endpoints get an additional `rate.limit:write` on the specific route. Sensitive endpoints get `rate.limit:sensitive`. Both middlewares run independently with separate counters — a POST request consumes 1 read hit AND 1 write hit. This means a user can make 60 total requests/min, of which at most 30 can be writes.

### 4. Security alerts on repeated violations

When a user hits the sensitive rate limit 3+ times within 5 minutes, a `SecurityAlert` is created with type `suspicious_activity` and severity `warning`. The violation counter is cleared after the alert is created.

### 5. 429 response format

```json
{
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Too many requests. Please retry after N seconds.",
    "retry_after": 30
  }
}
```

Headers: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `Retry-After`

---

## Files Created/Modified

| File | Purpose |
|---|---|
| `app/Http/Middleware/RateLimitByProfile.php` | New — config-driven rate limiting by profile |
| `app/Http/Middleware/TenantRateLimit.php` | New — per-tenant rate limiting with plan multipliers |
| `bootstrap/app.php` | Extended — registered `rate.limit` and `tenant.rate` aliases |
| `config/rate_limits.php` | New — all rate limit profiles and thresholds |
| `routes/api.php` | Extended — applied rate limiting to all routes |
| `tests/Feature/Api/V1/RateLimiting/RateLimitTest.php` | 11 feature tests |

---

## Bugs Found and Fixed During Implementation

### 1. apiResource except() removed GET routes

**Cause:** Used `Route::apiResource('folders', ...)->except(['index', 'show'])->middleware('rate.limit:write')` to apply write limits only to mutating routes. But `except()` removed the GET routes entirely, causing 405 errors.

**Fix:** Defined each route individually with the appropriate middleware.

### 2. Rate limiter cache persisting between tests

**Cause:** The `Cache::flush()` in `setUp()` didn't fully clear the rate limiter state between tests. The `test_different_users_have_separate_limits` test failed because user2 inherited rate limit state from a previous test.

**Fix:** Added an explicit `Cache::flush()` call before user2's request in the test. The rate limiter uses the cache store, and flushing it resets all counters.

### 3. PHPStan: nullsafe on non-nullable + float/int arithmetic

**Cause:** `$request->user()?->id ?? $request->ip()` — PHPStan inferred `user()` as non-nullable (because the middleware runs after `auth:sanctum`). Also, `RateLimiter::attempts()` returns `float|int`, causing arithmetic type issues.

**Fix:** Changed `?->` to explicit null check. Cast remaining attempts to `int`.

---

## Verification Results

| Check | Result |
|---|---|
| `./vendor/bin/pint` | PASS — all files, no style issues |
| `./vendor/bin/phpstan analyse` (level 8) | PASS — 0 errors |
| `php artisan test` | PASS — 372 tests, 1100 assertions (11 new + 361 from Modules 02-24) |
