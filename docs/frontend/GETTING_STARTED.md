# Zekura Frontend — Getting Started

> **For frontend developers** joining the Zekura project. This doc covers everything you need to start building against the API.

---

## 1. What is Zekura?

Zekura is an internal password and secrets management platform. It serves ~100 authenticated staff behind a VPN/firewall. The backend is a Laravel 13 REST API. You are building the React SPA that consumes it.

**Read these first:**
- `docs/PRD.md` — product requirements, what the product does
- `docs/ARCHITECTURE.md` — system architecture, API design standards
- `README.md` — tech stack, security model

---

## 2. Running the API locally

### Prerequisites

- Docker + Docker Compose
- Node.js 22+ (for the frontend)
- Git

### Start the backend

```bash
cd /path/to/Zekura
docker compose up -d
```

This starts:
- PostgreSQL 16
- Redis 7
- Mailpit (dev mail catcher)
- Laravel API on `http://localhost:8000`

### Verify the API is running

```bash
curl http://localhost:8000/api/v1/health
# {"status":"ok","timestamp":"..."}
```

### API documentation

Scramble generates OpenAPI docs dynamically from the routes:

- **Interactive HTML docs**: `http://localhost:8000/docs/api`
- **OpenAPI JSON spec**: `http://localhost:8000/docs/api.json`

Use the JSON spec to auto-generate TypeScript types and API clients (see `CONVENTIONS.md`).

---

## 3. Authentication

### Strategy: Sanctum cookie-based SPA auth

The React SPA will use **cookie-based authentication** via Laravel Sanctum, not Bearer tokens. This is more secure for a SPA:

- No token stored in JavaScript memory/localStorage (immune to XSS token theft)
- CSRF protection built in
- Session cookies are `HttpOnly`, `SameSite=Lax`

### Same-origin deployment

In production, Nginx serves the React static files and proxies `/api` to Laravel. Both are on the same domain, so cookies work seamlessly and no CORS configuration is needed.

In development, the Vite dev server runs on `localhost:3000` and proxies `/api` to `localhost:8000`. The Vite proxy handles same-origin requests during development.

### Auth flow summary

1. **GET `/sanctum/csrf-cookie`** — fetch CSRF token cookie
2. **POST `/api/v1/auth/login`** — submit email + password, receive session cookie
3. If 2FA is enabled, **POST `/api/v1/auth/2fa/verify`** with the `2fa_token`
4. **GET `/api/v1/auth/me`** — fetch authenticated user profile
5. All subsequent requests include the session cookie + `X-CSRF-TOKEN` header

See `AUTH_FLOWS.md` for the complete auth state machine.

---

## 4. Tenant resolution

Zekura is multi-tenant. Most endpoints require a tenant (workspace) context.

### How to set tenant context

Send an `X-Tenant-ID` header with the tenant ID:

```
X-Tenant-ID: 1
```

The `ResolveTenant` middleware:
1. Checks if the user's token has an associated tenant (priority 1)
2. Falls back to the `X-Tenant-ID` header (priority 2)
3. Verifies the user is an active, non-suspended member of that tenant

### When to send the header

- **Required**: all team/org vault, secure file, secure note, dashboard, and admin endpoints
- **Not required**: personal vault endpoints (scoped by user, not tenant), auth endpoints

### What happens if you don't send it

- Tenant-scoped queries will throw a 500 error: "Querying a tenant-scoped model without a current tenant context"
- Some endpoints are tenant-agnostic and will work without it

### Getting available tenants

```bash
GET /api/v1/tenants
```

Returns the list of workspaces the user belongs to. The frontend should let the user switch between tenants and send the selected tenant's ID as the `X-Tenant-ID` header.

---

## 5. Rate limiting

The API enforces rate limits per profile. The frontend should handle `429 Too Many Requests` responses gracefully.

| Profile | Limit | Window | Applies to |
|---|---|---|---|
| `read` | 60 | 1 min | All GET endpoints |
| `write` | 30 | 1 min | POST/PUT/PATCH/DELETE |
| `sensitive` | 10 | 1 min | 2FA, password change, offboarding, revoke-all |
| `auth.login` | 5 | 1 min | POST `/auth/login` |
| `auth.register` | 5 | 1 min | POST `/auth/register` |
| `auth.forgot_password` | 3 | 1 min | POST `/auth/forgot-password` |
| `2fa.verify` | 5 | 1 min | POST `/auth/2fa/verify` |

Rate-limited responses include a `Retry-After` header (seconds). Show the user a "rate limited" message and disable the action until the window passes.

---

## 6. Response format

### Success — single resource

```json
{
  "data": {
    "id": 42,
    "name": "GitHub Deploy Key",
    "type": "ssh_key",
    "username": "deploy-bot",
    "password": "decrypted-value",
    "url": "https://github.com",
    "notes": "Used for CI/CD deployments",
    "created_at": "2026-08-31T22:00:00.000000Z"
  }
}
```

### Success — paginated collection

```json
{
  "data": [ { "id": 1, ... }, { "id": 2, ... } ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 47,
    "last_page": 4
  },
  "links": {
    "first": "...?page=1",
    "last": "...?page=4",
    "prev": null,
    "next": "...?page=2"
  }
}
```

> **Note**: Pagination metadata is at `meta.per_page`, `meta.total`, etc. — not `meta.pagination.*`.

### Error

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "errors": {
      "name": ["The name field is required."],
      "password": ["The password must be at least 8 characters."]
    }
  }
}
```

### Status codes

| Code | Meaning |
|---|---|
| 200 | Success |
| 201 | Created |
| 204 | Deleted (no body) |
| 400 | Bad request |
| 401 | Not authenticated |
| 403 | Not authorized / wrong tenant |
| 404 | Not found |
| 409 | Conflict (duplicate) |
| 422 | Validation error |
| 423 | Re-authentication required (sensitive action) |
| 429 | Rate limited |
| 500 | Server error |

---

## 7. Key headers to send

| Header | When | Value |
|---|---|---|
| `X-CSRF-TOKEN` | All non-GET requests | Read from `XSRF-TOKEN` cookie |
| `X-Tenant-ID` | Tenant-scoped endpoints | Selected workspace ID |
| `Accept` | All requests | `application/json` |
| `Content-Type` | POST/PUT/PATCH | `application/json` |

---

## 8. Development setup (frontend)

See `CONVENTIONS.md` for the full project setup. Quick start:

```bash
# From the Zekura repo root
mkdir frontend && cd frontend
npm create vite@latest . -- --template react-ts
npm install
npm install @tanstack/react-query react-router-dom zustand
npm install tailwindcss @tailwindcss/vite
npm install -D openapi-typescript
npm run dev
```

Vite proxy config (`vite.config.ts`):

```ts
export default defineConfig({
  server: {
    proxy: {
      '/api': 'http://localhost:8000',
      '/sanctum': 'http://localhost:8000',
    },
  },
})
```

This makes the frontend talk to the API on the same origin during development, matching production behavior.
