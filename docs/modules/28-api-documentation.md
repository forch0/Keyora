# Module 28 — API Documentation (OpenAPI/Scribe)

| Field | Value |
|---|---|
| **Module** | 28 |
| **Name** | API Documentation |
| **Dependencies** | All prior modules |
| **Status** | ✅ Complete |

---

## Objective

Generate comprehensive API documentation from the codebase using Scribe. Provide an OpenAPI spec and a browsable HTML documentation site so API consumers can understand endpoints, request/response shapes, authentication, and error codes without reading the source.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| AD-01 | API documentation for all endpoints | P0 |
| AD-02 | OpenAPI 3.1 spec output | P1 |
| AD-03 | Browsable HTML documentation | P0 |
| AD-04 | Authentication documentation | P0 |
| AD-05 | Error code reference | P1 |

---

## Tasks

### 28.1 Install Scribe

- [ ] Install `knuckleswtf/scribe` as a dev dependency
- [ ] Publish the config file: `php artisan vendor:publish --tag=scribe-config`
- [ ] Configure `config/scribe.php`:
  - `type` → `laravel` (uses routes)
  - `theme` → `default`
  - `base_url` → from `APP_URL`
  - `auth` → bearer token via Sanctum
  - `groups.order` → organized by module

### 28.2 Document Endpoints

- [ ] Add `@group` annotations to all controllers, organized by module:
  - Authentication
  - Personal Vault
  - Teams & Team Vaults
  - Secure Files
  - Secure Notes
  - Access Management
  - Access Requests
  - Secure Sharing
  - Search & Organization
  - Activity & Audit
  - Security Alerts
  - Employee Lifecycle
  - Account Security (2FA)
  - Dashboards
  - Tenant Management

- [ ] Add `@bodyParam` / `@queryParam` annotations to all controller methods
- [ ] Add `@response` annotations with realistic examples
- [ ] Add `@responseField` annotations for response shapes
- [ ] Add `@authenticated` to endpoints requiring auth
- [ ] Add `@unauthenticated` to public endpoints (login, register, 2FA verify)

### 28.3 Document Authentication

- [ ] Configure Scribe auth section:
  - Bearer token via Sanctum
  - How to get a token (register/login flow)
  - 2FA flow documentation
  - X-Tenant-ID header for tenant context
  - Re-authentication header requirements

### 28.4 Document Error Codes

- [ ] Create a dedicated documentation section for error codes:
  - 401 — Unauthenticated
  - 403 — Forbidden (not a member, not admin, suspended)
  - 404 — Resource not found
  - 422 — Validation errors
  - 423 — Re-authentication required
  - 429 — Rate limit exceeded
  - 500 — Server error

- [ ] Document common error response shapes:
```json
{
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable message"
  }
}
```

### 28.5 Generate Documentation

- [ ] Run `php artisan scribe:generate` to produce:
  - `public/docs/index.html` — browsable HTML docs
  - `public/docs/openapi.yaml` — OpenAPI 3.1 spec
  - `public/docs/postman.collection.json` — Postman collection

- [ ] Add `docs/` output to `.gitignore` (generated artifact)
- [ ] Add a `composer.json` script: `"docs": "php artisan scribe:generate"`

### 28.6 Custom Examples

- [ ] Create `app/Docs/Examples/` with example response classes for complex endpoints:
  - Dashboard responses
  - Activity log responses
  - Access grant responses

- [ ] Use `@responseFile` for large response examples

### 28.7 CI Integration (Future)

- [ ] Add a CI step to regenerate docs and verify they're up to date
- [ ] Serve docs at `/docs` in non-production environments

---

## Acceptance Criteria

- [ ] `php artisan scribe:generate` runs without errors
- [ ] HTML documentation is browsable at `public/docs/index.html`
- [ ] OpenAPI 3.1 spec is generated at `public/docs/openapi.yaml`
- [ ] All endpoints are documented with request params and response shapes
- [ ] Authentication flow is documented
- [ ] Error codes are documented
- [ ] Endpoints are organized by group/module
- [ ] Postman collection is generated
- [ ] Documentation includes realistic example responses

---

## Tests to Write

This module is primarily documentation generation. Tests verify:

| Test | What it verifies |
|---|---|
| `test_scribe_generate_succeeds` | `php artisan scribe:generate` exits 0 |
| `test_openapi_spec_exists` | `public/docs/openapi.yaml` is generated |
| `test_html_docs_exist` | `public/docs/index.html` is generated |
| `test_all_routes_documented` | No routes missing from the generated docs |
| `test_auth_endpoints_marked` | Authenticated endpoints have security scheme |
| `test_postman_collection_exists` | Postman collection JSON is generated |

---

## What This Module Does NOT Include

- Interactive API playground (post-MVP)
- Auto-generated SDK clients (post-MVP)
- Versioned API documentation (post-MVP)
- GraphQL schema (not applicable)
