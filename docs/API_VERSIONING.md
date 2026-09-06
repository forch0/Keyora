# Keyora — API Versioning Strategy

> **Scope**: Internal tool for a single company with subsidiaries, max ~100 staff.
> All API endpoints are under `/api/v1/`.

---

## Current State

- All routes are prefixed with `/api/v1/` via `Route::prefix('v1')` in `routes/api.php`.
- The OpenAPI spec is generated dynamically by Scramble at `/docs/api.json` and `/docs/api`.
- There is no `v2` namespace at this time.

---

## Versioning Policy

### URL-based versioning

Keyora uses **URL-based versioning** (`/api/v1/`, `/api/v2/`, etc.) rather than header-based content negotiation. This is simpler for an internal tool and makes version explicit in client code.

### What counts as a breaking change

- Removing an endpoint
- Removing or renaming a response field
- Changing a field type (e.g., string → integer)
- Changing required request parameters
- Changing error status codes (e.g., 200 → 201)
- Changing authentication or authorization requirements

### What is NOT a breaking change

- Adding new optional request parameters
- Adding new response fields (clients must ignore unknown fields)
- Adding new endpoints
- Changing internal implementation details
- Performance improvements

### v1 support

- `v1` is the current and only version.
- `v1` will continue to receive bug fixes and non-breaking enhancements indefinitely.
- Breaking changes will NOT be introduced in `v1`.

### When v2 happens

- A `v2` namespace will be created only when a breaking change is unavoidable.
- When `v2` is introduced:
  1. Both `v1` and `v2` routes will be registered simultaneously.
  2. `v1` endpoints will be maintained for at least **6 months** after `v2` ships.
  3. `v1` responses will include a `Deprecation: true` header and a `Sunset` header with the deprecation date.
  4. The OpenAPI spec will document both versions.
  5. All internal clients will be updated to `v2` before `v1` is removed.

### Deprecation communication

Since Keyora is an internal tool with ~100 authenticated users, deprecation will be communicated via:

1. A `Deprecation` HTTP header on affected `v1` responses.
2. A `Sunset` HTTP header indicating when `v1` will be removed.
3. Direct communication to internal development teams.
4. A changelog entry in the release notes.

### No auto-versioning

Keyora does not auto-increment versions. Version bumps are deliberate, manual, and communicated ahead of time. There is no `Accept: application/vnd.keyora.v1+json` header strategy — the version is always in the URL.
