# Module 27 — Rate Limiting & API Throttling

| Field | Value |
|---|---|
| **Module** | 27 |
| **Name** | Rate Limiting & API Throttling |
| **Dependencies** | Module 02, Module 23 |
| **Status** | Not Started |

---

## Objective

Apply granular rate limiting across all API endpoints. Currently only auth endpoints (login, register, forgot-password) are throttled. This module extends throttling to write endpoints, sensitive actions, and per-tenant limits to prevent abuse.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| RL-01 | Rate limit all write endpoints | P0 |
| RL-02 | Stricter limits on sensitive actions | P0 |
| RL-03 | Per-tenant rate limiting | P1 |
| RL-04 | Rate limit headers in responses | P1 |
| RL-05 | Configurable rate limits | P1 |

---

## Tasks

### 27.1 Rate Limit Configuration

- [ ] Create `config/rate_limits.php`:

```php
return [
    'read' => [
        'limit' => 60,
        'window' => 1, // per minute
    ],
    'write' => [
        'limit' => 30,
        'window' => 1,
    ],
    'sensitive' => [
        'limit' => 10,
        'window' => 1,
    ],
    'auth' => [
        'login' => ['limit' => 5, 'window' => 1],
        'register' => ['limit' => 5, 'window' => 1],
        'forgot_password' => ['limit' => 3, 'window' => 1],
    ],
    '2fa' => [
        'verify' => ['limit' => 5, 'window' => 1],
    ],
];
```

### 27.2 Rate Limit Profiles

- [ ] Create `app/Http/Middleware/RateLimitByProfile.php`:
  - Reads the profile from the route middleware parameter
  - Resolves limits from `config/rate_limits.php`
  - Uses `RateLimiter` facade with a key combining user ID + profile
  - Adds `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `Retry-After` headers

- [ ] Register middleware alias in `bootstrap/app.php`:
  - `rate.limit` → `RateLimitByProfile::class`

### 27.3 Per-Tenant Rate Limiting

- [ ] Create `app/Http/Middleware/TenantRateLimit.php`:
  - Uses tenant ID + user ID as the rate limit key
  - Allows higher limits for higher plan tiers (from `config/plans.php`)
  - Falls back to default limits if no tenant context

### 27.4 Apply Rate Limiting to Routes

- [ ] Read endpoints: `rate.limit:read` (60/min)
- [ ] Write endpoints (POST/PUT/DELETE on resources): `rate.limit:write` (30/min)
- [ ] Sensitive endpoints (offboard, revoke-all, 2FA, password change, secure link creation, vault item deletion): `rate.limit:sensitive` (10/min)
- [ ] Auth endpoints: keep existing `throttle` middleware, migrate to config-driven

### 27.5 Rate Limit Exceeded Response

- [ ] Return `429 Too Many Requests` with:
```json
{
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Too many requests. Please retry after N seconds.",
    "retry_after": 30
  }
}
```

### 27.6 Activity Logging

- [ ] Log rate limit exceeded events as security alerts when:
  - A user hits the sensitive action limit 3+ times in 5 minutes
  - A tenant exceeds its overall rate limit repeatedly

---

## Acceptance Criteria

- [ ] All read endpoints are rate-limited to 60/min
- [ ] All write endpoints are rate-limited to 30/min
- [ ] Sensitive actions are rate-limited to 10/min
- [ ] Rate limit headers are included in responses
- [ ] 429 response includes retry_after
- [ ] Per-tenant rate limiting works when tenant context is set
- [ ] Rate limits are configurable via `config/rate_limits.php`
- [ ] Repeated rate limit violations on sensitive actions create security alerts
- [ ] Existing auth rate limits still work

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_read_endpoint_rate_limited` | 61st read request in a minute → 429 |
| `test_write_endpoint_rate_limited` | 31st write request in a minute → 429 |
| `test_sensitive_endpoint_rate_limited` | 11th sensitive request in a minute → 429 |
| `test_rate_limit_headers_present` | Response includes X-RateLimit-* headers |
| `test_429_includes_retry_after` | 429 response has retry_after field |
| `test_per_tenant_rate_limiting` | Tenant context affects limit key |
| `test_rate_limit_configurable` | Changing config changes the limit |
| `test_repeated_violations_create_alert` | 3 violations in 5 min → security alert |
| `test_auth_login_still_rate_limited` | Existing auth throttle still works |
| `test_different_users_have_separate_limits` | User A hitting limit doesn't affect User B |

---

## What This Module Does NOT Include

- IP-based rate limiting (post-MVP — requires trusted proxy config)
- Distributed rate limiting with Redis (post-MVP — current setup uses in-memory)
- Rate limit dashboard/admin view (post-MVP)
