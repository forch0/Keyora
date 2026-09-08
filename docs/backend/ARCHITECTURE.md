# Architecture & Coding Guidelines

# Zekura — Technical Architecture & Development Standards

| Field | Value |
|---|---|
| **Document Version** | 2.0 |
| **Last Updated** | 2026-09-02 |
| **Status** | Active |

---

## Table of Contents

- [1. Architectural Decisions](#1-architectural-decisions)
  - [1.1 ADR-001: API-First Design](#11-adr-001-api-first-design)
  - [1.2 ADR-002: Multi-Tenant Single Database](#12-adr-002-multi-tenant-single-database)
  - [1.3 ADR-003: Encryption Strategy](#13-adr-003-encryption-strategy)
  - [1.4 ADR-004: Authentication & Authorization](#14-adr-004-authentication--authorization)
  - [1.5 ADR-005: Permission System Design](#15-adr-005-permission-system-design)
  - [1.6 ADR-006: Audit Logging](#16-adr-006-audit-logging)
  - [1.7 ADR-007: Queue & Async Processing](#17-adr-007-queue--async-processing)
  - [1.8 ADR-008: File Storage](#18-adr-008-file-storage)
  - [1.9 ADR-009: Database Choice](#19-adr-009-database-choice)
  - [1.10 ADR-010: Frontend Strategy](#110-adr-010-frontend-strategy)
  - [1.11 ADR-011: Open-Source Project (No Billing)](#111-adr-011-open-source-project-no-billing)
  - [1.12 ADR-012: Native TOTP Implementation](#112-adr-012-native-totp-implementation)
  - [1.13 ADR-013: Re-authentication via Token Age + Cache](#113-adr-013-re-authentication-via-token-age--cache)
- [2. System Architecture](#2-system-architecture)
  - [2.1 High-Level Diagram](#21-high-level-diagram)
  - [2.2 Layered Architecture](#22-layered-architecture)
  - [2.3 Request Flow](#23-request-flow)
- [3. Multi-Tenancy Implementation](#3-multi-tenancy-implementation)
  - [3.1 Tenant Model](#31-tenant-model)
  - [3.2 Global Scopes](#32-global-scopes)
  - [3.3 Tenant Resolution](#33-tenant-resolution)
  - [3.4 Tenant-Aware Models](#34-tenant-aware-models)
  - [3.5 Non-Tenant Models](#35-non-tenant-models)
- [4. Database Design Guidelines](#4-database-design-guidelines)
  - [4.1 Naming Conventions](#41-naming-conventions)
  - [4.2 Column Standards](#42-column-standards)
  - [4.3 Indexing Strategy](#43-indexing-strategy)
  - [4.4 Foreign Keys & Constraints](#44-foreign-keys--constraints)
  - [4.5 Soft Deletes](#45-soft-deletes)
- [5. API Design Standards](#5-api-design-standards)
  - [5.1 Versioning](#51-versioning)
  - [5.2 URL Structure](#52-url-structure)
  - [5.3 HTTP Methods](#53-http-methods)
  - [5.4 Response Format](#54-response-format)
  - [5.5 Status Codes](#55-status-codes)
  - [5.6 Pagination](#56-pagination)
  - [5.7 Filtering & Sorting](#57-filtering--sorting)
  - [5.8 Rate Limiting](#58-rate-limiting)
  - [5.9 Error Handling](#59-error-handing)
- [6. Laravel Conventions](#6-laravel-conventions)
  - [6.1 Project Structure](#61-project-structure)
  - [6.2 Naming Conventions](#62-naming-conventions)
  - [6.3 Controllers](#63-controllers)
  - [6.4 Services](#64-services)
  - [6.5 Actions](#65-actions)
  - [6.6 Form Requests](#66-form-requests)
  - [6.7 API Resources](#67-api-resources)
  - [6.8 Models](#68-models)
  - [6.9 Migrations](#69-migrations)
  - [6.10 Events & Listeners](#610-events--listeners)
  - [6.11 Jobs](#611-jobs)
  - [6.12 Notifications](#612-notifications)
- [7. Code Formatting & Style](#7-code-formatting--style)
  - [7.1 PHP Standards](#71-php-standards)
  - [7.2 Laravel Pint Configuration](#72-laravel-pint-configuration)
  - [7.3 IDE & Tooling](#73-ide--tooling)
- [8. Security Guidelines](#8-security-guidelines)
  - [8.1 Encryption at Rest](#81-encryption-at-rest)
  - [8.2 Sensitive Field Handling](#82-sensitive-field-handling)
  - [8.3 API Security](#83-api-security)
  - [8.4 Input Validation](#84-input-validation)
- [9. Testing Standards](#9-testing-standards)
  - [9.1 Test Structure](#91-test-structure)
  - [9.2 Naming Conventions](#92-naming-conventions)
  - [9.3 What to Test](#93-what-to-test)
  - [9.4 Factories & Seeders](#94-factories--seeders)
- [10. Git Workflow](#10-git-workflow)
  - [10.1 Branch Strategy](#101-branch-strategy)
  - [10.2 Commit Conventions](#102-commit-conventions)
  - [10.3 PR Process](#103-pr-process)

---

## 1. Architectural Decisions

### 1.1 ADR-001: API-First Design

| Field | Value |
|---|---|
| **Status** | Accepted |
| **Date** | 2026-08-31 |

**Context**

Zekura is being built as a SaaS product. The initial deliverable is a RESTful API. A frontend (web, mobile, or browser extension) will consume this API later.

**Decision**

Build an **API-first** architecture. All business logic lives behind API endpoints. No Blade views or server-rendered UI in the MVP. The API is the product.

**Consequences**

- All endpoints return JSON via API Resources
- Authentication via Laravel Sanctum (token-based)
- No session-based auth, no CSRF tokens, no Blade controllers
- Frontend-agnostic — any client can consume the API
- API versioning from day one (`/api/v1/`)
- OpenAPI/Scribe documentation generated from code
- All validation via Form Requests
- All responses via API Resources (never raw Eloquent collections)

---

### 1.2 ADR-002: Multi-Tenant Single Database

| Field | Value |
|---|---|
| **Status** | Accepted |
| **Date** | 2026-08-31 |

**Context**

Zekura serves multiple companies (workspaces). Each company's data must be isolated. We need to decide between single-database with tenant scoping vs. database-per-tenant.

**Decision**

Use **single database with `tenant_id` column scoping**. Each company workspace is a tenant. All tenant-scoped models include a `tenant_id` column and are filtered via Laravel global scopes.

**Implementation**

- `Tenant` model represents a company workspace
- `BelongsToTenant` trait + `TenantScope` global scope on all tenant models
- Tenant resolution middleware extracts `tenant_id` from:
  1. API token scope (token is tied to a tenant)
  2. Request header `X-Tenant-ID`
  3. URL parameter (if applicable)
- Personal vault items are scoped by `user_id` (not `tenant_id`)
- Cross-tenant queries are explicitly opt-out (rare, admin-only)

**Consequences**

- Simpler infrastructure (one database, one connection)
- Easier backups and migrations
- All tenant models must include `tenant_id` — enforced by trait
- Must be disciplined about global scopes (never bypass without explicit reason)
- Future migration to per-DB tenancy is possible if models consistently use `tenant_id`
- Row-level isolation is logical, not physical — acceptable for MVP

---

### 1.3 ADR-003: Encryption Strategy

| Field | Value |
|---|---|
| **Status** | Accepted |
| **Date** | 2026-08-31 |

**Context**

Zekura stores highly sensitive data (passwords, API keys, credentials). This data must be encrypted at rest.

**Decision**

Use **Laravel's built-in `Crypt` facade (AES-256-CBC)** for encrypting sensitive fields at the application layer. This is server-side encryption — not zero-knowledge / client-side.

**Implementation**

- `Encryptable` trait on models with an `$encryptable` array property
- Trait overrides `getAttribute` and `setAttribute` to encrypt/decrypt automatically
- Encrypted fields stored as `TEXT` columns (encrypted output is base64-encoded)
- Encryption key stored in `.env` (`APP_KEY`) — never hardcode
- Passwords (user login passwords) hashed with bcrypt via Laravel's hasher — NOT encrypted
- Vault secrets (stored credentials) are encrypted — these are the user's data, not auth credentials

**Consequences**

- Server has access to plaintext during processing — acceptable for MVP
- Zero-knowledge encryption is a future goal (would require client-side key derivation)
- Key rotation requires re-encrypting all data — plan for a rotation job
- Encrypted fields cannot be queried with `WHERE` clauses (no searching on encrypted values)
- Search on sensitive fields must use separate indexed metadata columns (e.g., `name`, `url` stored in plaintext for search; `password`, `username` encrypted)

---

### 1.4 ADR-004: Authentication & Authorization

| Field | Value |
|---|---|
| **Status** | Accepted |
| **Date** | 2026-08-31 |

**Context**

The API needs stateless authentication for API consumers and a permission system for resource-level access control.

**Decision**

- **Authentication:** Laravel Sanctum (API tokens)
- **Authorization:** Laravel Policies + Gates (resource-level)
- **2FA:** TOTP-based (Google Authenticator compatible) via custom middleware
- **Sensitive actions:** Re-authentication middleware (require recent password confirmation)

**Implementation**

- Users generate API tokens with scoped abilities (`vault:read`, `vault:write`, `company:manage`, etc.)
- Tokens are tied to a user and optionally to a tenant
- Every API route wrapped in `auth:sanctum` middleware
- Every resource access goes through a Policy (`VaultItemPolicy`, `TeamPolicy`, etc.)
- Policies check both tenant membership and permission level

**Consequences**

- Stateless API — no sessions
- Token abilities provide coarse-grained API access control
- Policies provide fine-grained resource access control
- 2FA enforcement configurable per user and per tenant

---

### 1.5 ADR-005: Permission System Design

| Field | Value |
|---|---|
| **Status** | Accepted |
| **Date** | 2026-08-31 |

**Context**

Resources (secrets, files, notes) can be shared with individuals, teams, or the entire company. Each share grants a specific permission level. The system must answer: "who has access to this?" and "what does this person have access to?"

**Decision**

Use a **polymorphic access grant system** with explicit permission levels.

**Implementation**

```
access_grants
  id
  grantable_type    -- 'VaultItem' | 'SecureFile' | 'SecureNote' | 'Folder'
  grantable_id      -- FK to the resource
  subject_type      -- 'User' | 'Team' | 'Company'
  subject_id        -- FK to the subject
  permission        -- 'view' | 'edit' | 'share' | 'download' | 'manage'
  expires_at        -- nullable (null = permanent)
  max_views         -- nullable (null = unlimited)
  views_count       -- default 0
  starts_at         -- nullable (null = immediate)
  start_on_first_view -- boolean (clock starts on first open)
  first_viewed_at   -- nullable
  granted_by        -- FK to users
  created_at
  updated_at
```

**Permission hierarchy (cumulative):**

```
manage > share > edit > download > view
```

- `view` — can see the resource content
- `download` — can download files (implies view)
- `edit` — can modify the resource (implies view)
- `share` — can share with others (implies view)
- `manage` — full control including deletion and permission management (implies all above)

**Access resolution algorithm:**

1. Check if user is the owner of the resource → full access
2. Check direct user grants → highest permission wins
3. Check team grants for user's teams → highest permission wins
4. Check company-wide grants → applies if user is a company member
5. Apply temporal constraints (not expired, within start/end window)
6. Apply view constraints (views_count < max_views)

**Consequences**

- Single table handles all sharing relationships
- Easy to query "who has access to X" (`WHERE grantable_type = ? AND grantable_id = ?`)
- Easy to query "what does user X have access to" (union of user grants + team grants + company grants)
- Permission checks require a service class, not just a policy method
- Denormalized `views_count` requires atomic increments

---

### 1.6 ADR-006: Audit Logging

| Field | Value |
|---|---|
| **Status** | Accepted |
| **Date** | 2026-08-31 |

**Context**

Every access, modification, sharing, and revocation event must be logged for security and compliance.

**Decision**

Use a **dedicated `activity_logs` table** with an append-only pattern. No update or delete operations exposed through the application.

**Implementation**

```
activity_logs
  id
  tenant_id          -- nullable (null for personal vault events)
  user_id            -- who performed the action
  action             -- 'create' | 'view' | 'update' | 'delete' | 'share' | 'revoke' | 'access' | 'login' | 'logout' | ...
  subject_type       -- 'VaultItem' | 'SecureFile' | 'SecureLink' | 'User' | ...
  subject_id         -- FK to the subject
  properties         -- JSON (before/after diffs, metadata)
  ip_address
  user_agent
  created_at         -- no updated_at (append-only)
```

- `ActivityLog` model has no `updated_at` column
- No mass assignment — only created via `ActivityLogger` service
- Queryable by tenant, user, subject, action, and date range
- Retention policy: 90 days for personal vault, 1 year for company workspace (configurable)

**Consequences**

- Table grows fast — implement archival/cleanup job for old logs
- JSON `properties` column provides flexibility without schema changes
- Cannot use Eloquent's `update()` on log records — enforced by model boot

---

### 1.7 ADR-007: Queue & Async Processing

| Field | Value |
|---|---|
| **Status** | Accepted |
| **Date** | 2026-08-31 |

**Context**

Notifications, access expiration checks, audit log writes, and email sending should not block API responses.

**Decision**

Use **Laravel Queues with Redis** as the queue driver.

**Queues (channels):**

| Queue | Purpose | Priority |
|---|---|---|
| `default` | General jobs (notifications, emails) | Normal |
| `expirations` | Access expiration checks, revocations | High |
| `audit` | Audit log writes | Low |

**Jobs:**

| Job | Trigger | Queue |
|---|---|---|
| `ProcessAccessExpiration` | Scheduled every minute | `expirations` |
| `SendAccessRequestNotification` | Access request created | `default` |
| `SendExpirationWarning` | 24h before access expires | `default` |
| `LogActivity` | After any logged action | `audit` |
| `SendInviteEmail` | Employee invited | `default` |

**Consequences**

- API responses stay fast — heavy work deferred to workers
- Redis required as infrastructure dependency
- Failed jobs must be monitored (Laravel Horizon recommended)
- Expiration job is critical — if it fails, access doesn't expire (security risk)

---

### 1.8 ADR-008: File Storage

| Field | Value |
|---|---|
| **Status** | Accepted |
| **Date** | 2026-08-31 |

**Context**

Users upload files (max 10 MB) that must be stored securely and served with access controls.

**Decision**

Use **Laravel Filesystem with local disk for MVP**, designed for seamless S3 migration later.

**Implementation**

- Files stored in `storage/app/private/{tenant_id}/{file_path}`
- Disk configured as private (not web-accessible)
- File serving goes through a controller that checks permissions before streaming
- File path stored in DB: `{tenant_id}/{uuid}/{original_filename}`
- File metadata (name, size, mime, checksum) stored in `secure_files` table
- File content is NOT encrypted at the filesystem level for MVP (filesystem permissions + private storage provide isolation)
- Future: encrypt file contents before storage using `Crypt::encryptString()`

**Consequences**

- Local storage is simple but requires disk space management
- Migration to S3 only requires changing disk config — no code changes
- File serving is a bottleneck for large files — consider signed URLs with S3 later
- 10 MB limit enforced at upload (Form Request validation + server-side check)

---

### 1.9 ADR-009: Database Choice

| Field | Value |
|---|---|
| **Status** | Accepted |
| **Date** | 2026-09-01 (updated from MySQL to PostgreSQL) |

**Context**

Need to choose between MySQL and PostgreSQL for the primary database.

**Decision**

Use **PostgreSQL 16** for MVP and production.

**Rationale**

- Row-level security support for multi-tenant isolation (future)
- Full-text search capabilities (useful for search on non-encrypted metadata)
- JSONB column support (needed for `activity_logs.properties` and flexible metadata)
- UUID column support for externally-referenced models
- Superior performance for complex aggregate queries (dashboards)
- Laravel's query builder and Eloquent work equally well with both

**Implementation**

- Docker: `postgres:16-alpine` on port 5432
- Migrations use PostgreSQL-compatible schema builders

---

### 1.10 ADR-010: Frontend Strategy

| Field | Value |
|---|---|
| **Status** | Accepted |
| **Date** | 2026-08-31 |

**Context**

The MVP is API-only. A frontend will be built later to consume the API.

**Decision**

**Defer frontend.** API-first. When a frontend is built, it will be a separate SPA (React/Next.js or Vue/Nuxt) consuming the API. No Blade, no Livewire, no Inertia in the API codebase.

**Consequences**

- Laravel project is purely an API server
- No views, no Blade templates, no frontend assets
- API documentation (Scribe/OpenAPI) serves as the "UI" for developers during API phase
- CORS must be configured for future frontend domain
- API must be fully self-documenting and well-tested since there's no UI to manually click through

---

### 1.11 ADR-011: Open-Source Project (No Billing)

| Field | Value |
|---|---|
| **Status** | Accepted |
| **Date** | 2026-09-02 |

**Context**

Zekura was originally planned as a SaaS product with Paystack billing. The project direction has changed — it will be open-sourced with no billing.

**Decision**

**Skip all billing modules.** Remove Paystack integration from the roadmap. The `plan` column on tenants remains for usage-limit tiers (free/team/business/enterprise) but has no billing or payment associated with it.

**Implementation**

- `config/plans.php` defines usage limits per tier (max_members, max_storage_mb, max_vault_items)
- No payment provider, no invoices, no subscriptions, no webhooks
- Modules 25 and 26 (SaaS & Billing) are marked as skipped
- The `billing` queue channel is removed from the queue configuration

**Consequences**

- Simpler infrastructure — no payment provider credentials or webhook endpoints
- Plan tiers are purely for usage limits, not revenue
- Open-source contributors can self-host without payment setup
- Future billing can be added as a separate package if needed

---

### 1.12 ADR-012: Native TOTP Implementation

| Field | Value |
|---|---|
| **Status** | Accepted |
| **Date** | 2026-09-02 |

**Context**

Module 23 requires TOTP-based 2FA. Options were to use a package (`pragmarx/google2fa`, `spomky-labs/otphp`) or implement natively.

**Decision**

Implement TOTP natively using PHP's `hash_hmac` and `random_bytes` — no external package.

**Rationale**

- RFC 6238 TOTP is a well-defined algorithm (~50 lines of code)
- Avoids adding a dependency for a self-contained feature
- Full control over secret generation, base32 encoding, and verification logic
- The `TwoFactorService` handles: secret generation, QR URI creation, code verification (±1 window for clock drift), recovery code generation/hashing/consumption

**Consequences**

- Maintenance burden is on the project (but the algorithm is stable and unchanging)
- No package updates to track for security patches
- Recovery codes use bcrypt hashing + `Crypt::encryptString` for storage

---

### 1.13 ADR-013: Re-authentication via Token Age + Cache

| Field | Value |
|---|---|
| **Status** | Accepted |
| **Date** | 2026-09-02 |

**Context**

Sensitive actions (vault item deletion, secure link creation, offboarding, emergency revocation) require recent authentication. Need a mechanism to track "recently authenticated" state.

**Decision**

Use a dual-check approach in `RequireReauthentication` middleware:
1. **Token age** — if the current API token was created within 15 minutes, the user is considered recently authenticated (login itself is a form of re-auth)
2. **Cache timestamp** — `ReauthenticationService` stores a `reauth:{user_id}` timestamp in cache when the user re-authenticates with their password

**Rationale**

- Token-age check means existing tests (which create fresh tokens) don't need to re-authenticate
- Cache timestamp provides explicit re-authentication without requiring a new login
- 15-minute window balances security and usability

**Consequences**

- Tests for re-authentication expiration must explicitly age the token
- The middleware returns HTTP 423 when neither condition is met
- Re-authentication endpoint resets the cache timestamp

---

## 2. System Architecture

### 2.1 High-Level Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                        CLIENTS                               │
│                                                              │
│  Web App (future)   Mobile App (future)   CLI / API Client   │
└──────────────────────────┬──────────────────────────────────┘
                           │ HTTPS (JSON)
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                    LARAVEL API SERVER                        │
│                                                              │
│  ┌─────────────┐  ┌──────────────┐  ┌────────────────────┐  │
│  │  Middleware  │  │ Controllers  │  │   API Resources    │  │
│  │             │  │              │  │                    │  │
│  │ • Auth      │→ │ • Resource   │→ │ • Transform to     │  │
│  │ • Tenant    │  │   Controllers│  │   JSON response    │  │
│  │ • 2FA       │  │ • Action     │  │                    │  │
│  │ • Rate Limit│  │   Classes    │  │                    │  │
│  └─────────────┘  └──────┬───────┘  └────────────────────┘  │
│                          │                                   │
│                   ┌──────▼───────┐                           │
│                   │   Services   │                           │
│                   │              │                           │
│                   │ • Access     │                           │
│                   │   Resolver   │                           │
│                   │ • Encryption │                           │
│                   │ • Activity   │                           │
│                   │   Logger     │                           │
│                   │ • Tenant     │                           │
│                   │   Manager    │                           │
│                   └──────┬───────┘                           │
│                          │                                   │
│                   ┌──────▼───────┐                           │
│                   │   Policies   │                           │
│                   │              │                           │
│                   │ • VaultItem  │                           │
│                   │ • SecureFile │                           │
│                   │ • Team       │                           │
│                   │ • AccessGrant│                           │
│                   └──────┬───────┘                           │
│                          │                                   │
│                   ┌──────▼───────┐                           │
│                   │   Models     │                           │
│                   │              │                           │
│                   │ • Eloquent   │                           │
│                   │ • Scopes     │                           │
│                   │ • Traits     │                           │
│                   │ • Relations  │                           │
│                   └──────┬───────┘                           │
└──────────────────────────┼──────────────────────────────────┘
                           │
           ┌───────────────┼───────────────┐
           ▼               ▼               ▼
    ┌─────────────┐ ┌────────────┐ ┌────────────┐
    │ PostgreSQL  │ │   Redis    │ │  Storage   │
    │             │ │            │ │  (local)   │
    │ • Primary   │ │ • Queue    │ │ • Files    │
    │   database  │ │ • Cache    │ │ • Private  │
    │             │ │            │ │            │
    └─────────────┘ └────────────┘ └────────────┘
```

### 2.2 Layered Architecture

```
┌─────────────────────────────────────────────────────────┐
│  Layer 1: Routes (routes/api.php)                       │
│  - URL definitions, middleware groups, rate limiting    │
├─────────────────────────────────────────────────────────┤
│  Layer 2: Controllers (app/Http/Controllers/Api/V1/)    │
│  - Receive request, delegate to actions/services       │
│  - Return API Resource responses                       │
│  - Thin — no business logic                             │
├─────────────────────────────────────────────────────────┤
│  Layer 3: Form Requests (app/Http/Requests/)            │
│  - Validation rules                                     │
│  - Authorization checks                                 │
├─────────────────────────────────────────────────────────┤
│  Layer 4: Actions (app/Actions/)                        │
│  - Single-purpose classes executing one use case        │
│  - Orchestrate services, models, events                 │
│  - e.g., CreateVaultItemAction, GrantAccessAction       │
├─────────────────────────────────────────────────────────┤
│  Layer 5: Services (app/Services/)                      │
│  - Reusable business logic                              │
│  - e.g., AccessResolver, EncryptionService,             │
│    ActivityLogger, TenantManager                        │
├─────────────────────────────────────────────────────────┤
│  Layer 6: Models (app/Models/)                          │
│  - Eloquent models, relationships, scopes, traits      │
│  - No business logic — data representation only         │
├─────────────────────────────────────────────────────────┤
│  Layer 7: Policies (app/Policies/)                      │
│  - Authorization logic per model                        │
│  - Called by controllers via authorize()                │
└─────────────────────────────────────────────────────────┘
```

### 2.3 Request Flow

```
HTTP Request
    │
    ▼
[Route Matching] → routes/api.php
    │
    ▼
[Middleware Stack]
    │── throttle:api (rate limiting)
    │── auth:sanctum (authentication)
    │── tenant.resolve (set current tenant)
    │── reauth (if sensitive action — 15min window)
    │
    ▼
[Controller Method]
    │
    ├── Form Request (validation + authorization)
    │       │
    │       └── rules() → validate input
    │       └── authorize() → check permission
    │
    ├── Action Class (business logic)
    │       │
    │       ├── Service calls (AccessResolver, EncryptionService, etc.)
    │       ├── Model operations (create, update, delete)
    │       ├── Event dispatch (ItemViewed, AccessGranted, etc.)
    │       └── Job dispatch (LogActivity, SendNotification, etc.)
    │
    ├── API Resource (transform model → JSON)
    │
    └── Response (JSON, correct status code)
```

---

## 3. Multi-Tenancy Implementation

### 3.1 Tenant Model

The `Tenant` model represents a company workspace. It is the root entity for all company-scoped data.

```php
// app/Models/Tenant.php

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'plan',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }
}
```

### 3.2 Global Scopes

All tenant-scoped models use a `BelongsToTenant` trait that automatically:

1. Adds `tenant_id` to the model
2. Registers a global scope filtering by the current tenant
3. Automatically sets `tenant_id` on new model creation

```php
// app/Traits/BelongsToTenant.php

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app(TenantManager::class)->hasCurrentTenant()) {
                $builder->where('tenant_id', app(TenantManager::class)->currentTenantId());
            }
        });

        static::creating(function (Model $model) {
            if (!isset($model->tenant_id) && app(TenantManager::class)->hasCurrentTenant()) {
                $model->tenant_id = app(TenantManager::class)->currentTenantId();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

### 3.3 Tenant Resolution

Tenant resolution happens in middleware. The `TenantManager` singleton holds the current tenant for the request lifecycle.

```php
// app/Http/Middleware/ResolveTenant.php

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantManager = app(TenantManager::class);

        // Priority 1: Token's associated tenant
        $token = $request->user()?->currentAccessToken();
        if ($token && $token->tenant_id) {
            $tenantManager->setCurrentTenant($token->tenant_id);
            return $next($request);
        }

        // Priority 2: X-Tenant-ID header
        $tenantId = $request->header('X-Tenant-ID');
        if ($tenantId) {
            // Verify user belongs to this tenant
            if (!$request->user()->tenants()->where('tenants.id', $tenantId)->exists()) {
                abort(403, 'You do not belong to this workspace.');
            }
            $tenantManager->setCurrentTenant((int) $tenantId);
        }

        return $next($request);
    }
}
```

```php
// app/Services/TenantManager.php

class TenantManager
{
    private ?int $currentTenantId = null;

    public function setCurrentTenant(int $tenantId): void
    {
        $this->currentTenantId = $tenantId;
    }

    public function currentTenantId(): ?int
    {
        return $this->currentTenantId;
    }

    public function hasCurrentTenant(): bool
    {
        return $this->currentTenantId !== null;
    }

    public function forgetCurrentTenant(): void
    {
        $this->currentTenantId = null;
    }
}
```

### 3.4 Tenant-Aware Models

These models use the `BelongsToTenant` trait:

| Model | Description |
|---|---|
| `Team` | Teams within a company |
| `TeamMember` | User-team membership |
| `VaultItem` | Secrets, credentials, notes in team/company vaults |
| `SecureFile` | Uploaded files in company context |
| `SecureNote` | Company/team notes |
| `AccessGrant` | Permission grants |
| `AccessRequest` | Access request records |
| `SecureLink` | External sharing links |
| `ActivityLog` | Audit log entries |
| `Folder` | Organizational folders |
| `Tag` | Tags (if tenant-scoped) |

### 3.5 Non-Tenant Models

These models are **not** tenant-scoped:

| Model | Reason |
|---|---|
| `User` | A user can belong to multiple tenants |
| `Tenant` | The tenant entity itself |
| `PersonalVaultItem` | Scoped by `user_id`, not `tenant_id` |
| `PersonalVaultFolder` | Scoped by `user_id` |
| `ApiToken` | Belongs to user, may reference a tenant |
| `Device` | User's active sessions/devices |

---

## 4. Database Design Guidelines

### 4.1 Naming Conventions

| Element | Convention | Example |
|---|---|---|
| Tables | snake_case, plural | `vault_items`, `access_grants` |
| Columns | snake_case | `created_at`, `tenant_id`, `expires_at` |
| Foreign keys | `{singular_table}_id` | `user_id`, `tenant_id`, `team_id` |
| Pivot tables | `{singular_table}_{singular_table}` (alphabetical) | `team_user`, `tag_vault_item` |
| Pivot columns | snake_case | `joined_at`, `role`, `permission` |
| Indexes | `{table}_{columns}_index` | `vault_items_tenant_id_index` |
| Unique indexes | `{table}_{columns}_unique` | `users_email_unique` |
| Foreign key constraints | `{table}_{column}_foreign` | `vault_items_tenant_id_foreign` |

### 4.2 Column Standards

Every table must include:

| Column | Type | Description |
|---|---|---|
| `id` | `bigIncrements` or `uuid` | Primary key |
| `created_at` | `timestamp` | Record creation time |
| `updated_at` | `timestamp` | Last modification time |

Tenant-scoped tables must also include:

| Column | Type | Description |
|---|---|---|
| `tenant_id` | `foreignId` | FK to `tenants` table |

Soft-deletable tables include:

| Column | Type | Description |
|---|---|---|
| `deleted_at` | `timestamp nullable` | Soft delete timestamp |

**UUID vs Auto-increment:**

- Use **auto-increment integers** for internal models (users, tenants, teams)
- Use **UUIDs** for externally-referenced models (secure links, API tokens, shared resources)
- UUID columns named `uuid` with a unique index

### 4.3 Indexing Strategy

**Always index:**

- All foreign key columns (`tenant_id`, `user_id`, `team_id`, etc.)
- Columns used in `WHERE` clauses frequently (`permission`, `expires_at`, `status`)
- Columns used for sorting (`created_at`, `updated_at`, `name`)
- Composite indexes for common query patterns:

```php
// Example: frequently query vault_items by tenant + folder + type
$table->index(['tenant_id', 'folder_id', 'type']);
```

**Never index:**

- Encrypted columns (they're not searchable)
- Columns with very low cardinality (boolean flags with even distribution)
- `updated_at` alone (rarely queried without other filters)

### 4.4 Foreign Keys & Constraints

- Always define foreign key constraints in migrations
- Use `onDelete('cascade')` for child records that can't exist without parent (e.g., `vault_items` when `tenant` is deleted — but this should never happen in practice)
- Use `onDelete('restrict')` for parents that shouldn't be deleted if children exist (e.g., `tenants` with active `users`)
- Use `nullable()` + `nullOnDelete()` for optional relationships

```php
// Good
$table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
$table->foreignId('folder_id')->nullable()->constrained()->nullOnDelete();

// Bad — no foreign key constraint
$table->unsignedBigInteger('tenant_id');
```

### 4.5 Soft Deletes

- Use soft deletes on models where data retention matters: `VaultItem`, `SecureFile`, `SecureNote`, `Tenant` (already have it)
- Module 29 (planned) will add soft deletes to: `PersonalVaultItem`, `Team`, `AccessGrant`, `AccessRequest`, `SecureLink`, `SecurityAlert`, `UserDevice`
- Do NOT use soft deletes on: `ActivityLog` (append-only), `ResourceView` (analytics), `SecureLinkAccess` (access tracking), `TenantInvitation` (transient)
- Soft-deleted records are excluded from default queries — use `withTrashed()` / `onlyTrashed()` explicitly
- Force delete endpoints (planned in Module 29) permanently remove records and clean up related data

---

## 5. API Design Standards

### 5.1 Versioning

- API version in URL: `/api/v1/...`
- Version prefix defined in route group
- No header-based versioning (simpler, more explicit)
- Breaking changes → new version (`v2`); backward compatibility maintained for at least 6 months

### 5.2 URL Structure

```
Base URL: https://api.zekura.app/api/v1

Resource endpoints:

POST   /auth/register              -- Register new user
POST   /auth/login                 -- Login (returns token)
POST   /auth/logout                -- Logout (revoke token)
POST   /auth/forgot-password       -- Request password reset
POST   /auth/reset-password        -- Reset password with token
GET    /auth/me                    -- Get current user
PUT    /auth/me                    -- Update profile
POST   /auth/2fa/enable            -- Enable 2FA
POST   /auth/2fa/verify            -- Verify 2FA code
POST   /auth/2fa/disable           -- Disable 2FA
GET    /auth/devices               -- List active devices
DELETE /auth/devices/{id}          -- Revoke a device

GET    /tenants                    -- List user's workspaces
POST   /tenants                    -- Create workspace
GET    /tenants/{tenant}           -- Get workspace details
PUT    /tenants/{tenant}           -- Update workspace
DELETE /tenants/{tenant}           -- Delete workspace

GET    /tenants/{tenant}/members   -- List members
POST   /tenants/{tenant}/members   -- Invite member
PUT    /tenants/{tenant}/members/{user}  -- Update member role
DELETE /tenants/{tenant}/members/{user}  -- Remove member

GET    /tenants/{tenant}/teams     -- List teams
POST   /tenants/{tenant}/teams     -- Create team
GET    /tenants/{tenant}/teams/{team}    -- Get team
PUT    /tenants/{tenant}/teams/{team}    -- Update team
DELETE /tenants/{tenant}/teams/{team}    -- Delete team

GET    /vault/items                -- List vault items (personal + shared)
POST   /vault/items                -- Create vault item
GET    /vault/items/{item}         -- Get vault item
PUT    /vault/items/{item}         -- Update vault item
DELETE /vault/items/{item}         -- Delete vault item
POST   /vault/items/{item}/view    -- Record a view (triggers access check)

GET    /vault/items/{item}/access  -- Who has access
POST   /vault/items/{item}/access  -- Grant access
PUT    /vault/items/{item}/access/{grant}  -- Update access
DELETE /vault/items/{item}/access/{grant}  -- Revoke access

POST   /vault/items/{item}/share-link  -- Create secure link
GET    /vault/items/{item}/share-links  -- List links for item
DELETE /share-links/{link}         -- Revoke a link
GET    /share-links/{link}/activity -- View link access history

GET    /files                      -- List files
POST   /files                      -- Upload file
GET    /files/{file}               -- Get file metadata
GET    /files/{file}/download      -- Download file (permission checked)
DELETE /files/{file}               -- Delete file

GET    /access-requests            -- List requests (sent + received)
POST   /access-requests            -- Create access request
PUT    /access-requests/{request}/approve   -- Approve request
PUT    /access-requests/{request}/reject    -- Reject request
DELETE /access-requests/{request}  -- Cancel request

GET    /activity-logs              -- List activity logs (filtered)
GET    /activity-logs/summary      -- Aggregated stats

GET    /security-alerts            -- List security alerts
POST   /security-alerts/{id}/read  -- Mark alert as read
GET    /devices                    -- List active devices
DELETE /devices/{id}               -- Revoke a device

POST   /auth/2fa/enable            -- Enable 2FA (generate secret + QR)
POST   /auth/2fa/confirm           -- Confirm 2FA with TOTP code
POST   /auth/2fa/disable           -- Disable 2FA (requires password)
GET    /auth/2fa/recovery-codes    -- Regenerate recovery codes (requires re-auth)
POST   /auth/2fa/verify            -- Verify 2FA during login
POST   /auth/reauthenticate        -- Re-authenticate with password
GET    /auth/reauthenticate/status -- Check if re-auth is needed
POST   /auth/logout-all            -- Revoke all API tokens

GET    /dashboard/personal         -- Personal dashboard summary
GET    /dashboard/company          -- Company dashboard (admin/owner)
GET    /dashboard/usage            -- Usage dashboard (admin/owner)
```

### 5.3 HTTP Methods

| Method | Purpose | Idempotent? |
|---|---|---|
| `GET` | Retrieve resource(s) | Yes |
| `POST` | Create resource / Perform action | No |
| `PUT` | Replace entire resource | Yes |
| `PATCH` | Partial update | No |
| `DELETE` | Remove resource | Yes |

**Rules:**

- `GET` never modifies data
- `POST` returns `201 Created` with the new resource
- `PUT` / `PATCH` return `200 OK` with the updated resource
- `DELETE` returns `204 No Content` (empty body)
- `GET` on a collection returns `200 OK` with paginated array

### 5.4 Response Format

All API responses use a consistent envelope:

**Success — single resource:**

```json
{
  "data": {
    "id": 42,
    "name": "GitHub Deploy Key",
    "type": "ssh_key",
    "username": "deploy-bot",
    "password": null,
    "url": "https://github.com",
    "notes": "Used for CI/CD deployments",
    "folder_id": 5,
    "tags": ["ci-cd", "github"],
    "favorite": false,
    "created_at": "2026-08-31T22:00:00.000000Z",
    "updated_at": "2026-08-31T22:30:00.000000Z"
  }
}
```

**Success — collection (paginated):**

```json
{
  "data": [
    { "id": 1, ... },
    { "id": 2, ... },
    { "id": 3, ... }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 47,
    "last_page": 4
  },
  "links": {
    "first": "https://api.zekura.app/api/v1/vault/items?page=1",
    "last": "https://api.zekura.app/api/v1/vault/items?page=4",
    "prev": null,
    "next": "https://api.zekura.app/api/v1/vault/items?page=2"
  }
}
```

**Error:**

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

### 5.5 Status Codes

| Code | Meaning | When to use |
|---|---|---|
| `200 OK` | Success | GET, PUT, PATCH success |
| `201 Created` | Resource created | POST success |
| `204 No Content` | Success, no body | DELETE success |
| `400 Bad Request` | Malformed request | Invalid JSON, missing required parameters |
| `401 Unauthorized` | Not authenticated | Missing/invalid token |
| `403 Forbidden` | Authenticated but not authorized | Insufficient permissions, wrong tenant |
| `404 Not Found` | Resource doesn't exist | Invalid ID, wrong endpoint |
| `409 Conflict` | State conflict | Duplicate resource, concurrent modification |
| `422 Unprocessable Entity` | Validation error | Form Request validation failure |
| `423 Locked` | Re-authentication required | Sensitive action without recent auth |
| `429 Too Many Requests` | Rate limited | Throttle exceeded |
| `500 Internal Server Error` | Server error | Unhandled exception |

### 5.6 Pagination

- Default page size: **15**
- Max page size: **100** (enforced — `per_page` > 100 returns 422)
- Use Laravel's built-in paginator
- API Resource collection wraps with `meta` and `links`
- Cursor pagination for large datasets (activity logs) — use `cursorPaginator()`

### 5.7 Filtering & Sorting

**Filtering via query parameters:**

```
GET /vault/items?type=password&folder_id=5&tag=ci-cd&favorite=true
```

**Sorting via `sort` parameter:**

```
GET /vault/items?sort=-created_at,name
```

- Prefix with `-` for descending: `sort=-created_at`
- Multiple sort fields comma-separated: `sort=-created_at,name`
- Default sort: `-created_at` (newest first)

### 5.8 Rate Limiting

**Currently implemented (Module 02):**

| Endpoint Group | Limit | Window |
|---|---|---|
| Auth (login, register) | 5 requests | per minute per IP |
| Auth (forgot-password) | 3 requests | per minute per IP |

**Planned (Module 27 — Rate Limiting & API Throttling):**

| Profile | Limit | Window | Applies to |
|---|---|---|---|
| `read` | 60 requests | per minute per user | GET endpoints |
| `write` | 30 requests | per minute per user | POST/PUT/DELETE endpoints |
| `sensitive` | 10 requests | per minute per user | Offboard, revoke-all, 2FA, password change, secure link creation, vault item deletion |
| `auth.login` | 5 requests | per minute per IP | Login |
| `auth.register` | 5 requests | per minute per IP | Register |
| `auth.forgot_password` | 3 requests | per minute per IP | Forgot password |
| `2fa.verify` | 5 requests | per minute per user | 2FA verification |

- Configurable via `config/rate_limits.php`
- Per-tenant rate limiting (higher tiers get higher limits)
- 429 response includes `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `Retry-After` headers
- Repeated violations on sensitive actions create security alerts

### 5.9 Error Handling

All exceptions handled centrally in `app/Exceptions/Handler.php`:

```php
// Convert exceptions to JSON error responses
// Never expose stack traces in production
// Log unexpected exceptions for debugging
// Return consistent error envelope for all error types
```

**Error code conventions:**

| Prefix | Category |
|---|---|
| `AUTH_*` | Authentication errors |
| `FORBIDDEN_*` | Authorization errors |
| `VALIDATION_*` | Input validation errors |
| `NOT_FOUND_*` | Resource not found |
| `CONFLICT_*` | State conflicts |
| `RATE_LIMIT_*` | Rate limiting |
| `TENANT_*` | Tenant-related errors |

---

## 6. Laravel Conventions

### 6.1 Project Structure

```
app/
├── Console/
│   └── Commands/
│       ├── ExpireAccessGrants.php
│       └── CleanupActivityLogs.php
├── Events/
│   ├── AccessGranted.php
│   ├── AccessRevoked.php
│   ├── AccessRequested.php
│   ├── VaultItemCreated.php
│   ├── VaultItemViewed.php
│   └── EmployeeOffboarded.php
├── Exceptions/
│   └── Handler.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/
│   │           ├── AuthController.php
│   │           ├── TwoFactorController.php
│   │           ├── TenantController.php
│   │           ├── TenantMemberController.php
│   │           ├── TeamController.php
│   │           ├── VaultItemController.php
│   │           ├── PersonalVaultItemController.php
│   │           ├── SecureFileController.php
│   │           ├── SecureNoteController.php
│   │           ├── AccessGrantController.php
│   │           ├── AccessRequestController.php
│   │           ├── SecureLinkController.php
│   │           ├── PublicLinkController.php
│   │           ├── ActivityLogController.php
│   │           ├── SecurityAlertController.php
│   │           ├── DeviceController.php
│   │           ├── EmergencyRevokeController.php
│   │           ├── SearchController.php
│   │           └── DashboardController.php
│   ├── Middleware/
│   │   ├── ResolveTenant.php
│   │   └── RequireReauthentication.php
│   ├── Requests/
│   │   ├── Auth/
│   │   │   ├── LoginRequest.php
│   │   │   ├── RegisterRequest.php
│   │   │   └── ResetPasswordRequest.php
│   │   ├── Vault/
│   │   │   ├── CreateItemRequest.php
│   │   │   ├── UpdateItemRequest.php
│   │   │   └── GrantAccessRequest.php
│   │   └── ...
│   └── Resources/
│       └── V1/
│           ├── VaultItemResource.php
│           ├── VaultItemCollection.php
│           ├── SecureFileResource.php
│           ├── TeamResource.php
│           ├── AccessGrantResource.php
│           └── ...
├── Jobs/
│   ├── ProcessAccessExpiration.php
│   ├── SendAccessRequestNotification.php
│   ├── SendExpirationWarning.php
│   ├── LogActivity.php
│   └── SendInviteEmail.php
├── Listeners/
│   ├── LogAccessGranted.php
│   ├── LogVaultItemViewed.php
│   └── NotifyAccessRequestApprovers.php
├── Models/
│   ├── User.php
│   ├── Tenant.php
│   ├── Team.php
│   ├── VaultItem.php
│   ├── PersonalVaultItem.php
│   ├── SecureFile.php
│   ├── SecureNote.php
│   ├── AccessGrant.php
│   ├── AccessRequest.php
│   ├── SecureLink.php
│   ├── ActivityLog.php
│   ├── Folder.php
│   ├── Tag.php
│   └── ApiToken.php
├── Notifications/
│   ├── AccessRequestReceived.php
│   ├── AccessRequestApproved.php
│   ├── AccessRequestRejected.php
│   ├── AccessExpiringSoon.php
│   ├── EmployeeInvitation.php
│   └── NewDeviceLogin.php
├── Policies/
│   ├── VaultItemPolicy.php
│   ├── SecureFilePolicy.php
│   ├── SecureNotePolicy.php
│   ├── TeamPolicy.php
│   ├── AccessGrantPolicy.php
│   └── TenantPolicy.php
├── Providers/
│   ├── AppServiceProvider.php
│   ├── AuthServiceProvider.php
│   ├── EventServiceProvider.php
│   └── RouteServiceProvider.php
├── Services/
│   ├── AccessResolver.php
│   ├── ActivityLogger.php
│   ├── TenantManager.php
│   ├── PasswordGenerator.php
│   ├── PasswordStrengthChecker.php
│   ├── TwoFactorService.php
│   ├── ReauthenticationService.php
│   ├── DashboardService.php
│   ├── DeviceDetector.php
│   ├── GlobalSearch.php
│   └── ViewTracker.php
├── Actions/
│   ├── CreateVaultItemAction.php
│   ├── UpdateVaultItemAction.php
│   ├── DeleteVaultItemAction.php
│   ├── GrantAccessAction.php
│   ├── RevokeAccessAction.php
│   ├── CreateSecureLinkAction.php
│   ├── ApproveAccessRequestAction.php
│   ├── OffboardEmployeeAction.php
│   └── ...
├── Traits/
│   ├── BelongsToTenant.php
│   ├── Encryptable.php
│   └── HasActivityLogs.php
└── Rules/
    ├── StrongPassword.php
    └── ValidTotpCode.php

database/
├── migrations/
├── seeders/
└── factories/

routes/
└── api.php

tests/
├── Feature/
│   └── Api/
│       └── V1/
│           ├── Auth/
│           ├── Vault/
│           ├── Teams/
│           └── ...
└── Unit/
    ├── Services/
    │   ├── AccessResolverTest.php
    │   └── EncryptionServiceTest.php
    └── Models/
        └── VaultItemTest.php
```

### 6.2 Naming Conventions

| Element | Convention | Example |
|---|---|---|
| Controllers | PascalCase, singular + `Controller` | `VaultItemController` |
| Models | PascalCase, singular | `VaultItem`, `AccessGrant` |
| Migrations | snake_case, `create_{table}_table` | `create_vault_items_table` |
| Form Requests | PascalCase, `{Action}{Resource}Request` | `CreateVaultItemRequest` |
| API Resources | PascalCase, `{Resource}Resource` | `VaultItemResource` |
| Actions | PascalCase, `{Verb}{Resource}Action` | `CreateVaultItemAction` |
| Services | PascalCase, `{Name}Service` or just `{Name}` | `AccessResolver`, `EncryptionService` |
| Events | PascalCase, past tense | `VaultItemCreated`, `AccessGranted` |
| Listeners | PascalCase, `{Action}{Event}` | `LogVaultItemCreated` |
| Jobs | PascalCase, `{Verb}{Resource}` | `ProcessAccessExpiration` |
| Notifications | PascalCase, descriptive | `AccessRequestReceived` |
| Policies | PascalCase, `{Model}Policy` | `VaultItemPolicy` |
| Traits | PascalCase, adjective/behavioral | `BelongsToTenant`, `Encryptable` |
| Test classes | PascalCase, `{Class}Test` | `VaultItemControllerTest` |
| Test methods | snake_case, `test_{behavior}` | `test_user_can_create_vault_item` |

### 6.3 Controllers

**Rules:**

- Controllers are thin — no business logic
- One controller per resource
- Use dependency injection (type-hint in constructor or method)
- Delegate to Action classes for use cases
- Return API Resources, never raw models
- Use `response()` helper or Resource `response()` method for status codes

```php
// Good — thin controller
class VaultItemController extends Controller
{
    public function __construct(
        private CreateVaultItemAction $createAction,
    ) {}

    public function store(CreateItemRequest $request): JsonResponse
    {
        $item = ($this->createAction)(
            user: $request->user(),
            attributes: $request->validated(),
        );

        return (new VaultItemResource($item))
            ->response()
            ->setStatusCode(201);
    }

    public function show(VaultItem $item, Request $request): JsonResponse
    {
        $this->authorize('view', $item);

        return (new VaultItemResource($item))->response();
    }
}
```

```php
// Bad — fat controller with business logic
class VaultItemController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([...]);
        $data['password'] = Crypt::encrypt($data['password']);
        $data['tenant_id'] = app(TenantManager::class)->currentTenantId();
        $item = VaultItem::create($data);
        ActivityLog::create([...]);
        event(new VaultItemCreated($item));
        // ... 20 more lines of logic
    }
}
```

### 6.4 Services

**Rules:**

- Services hold reusable, cross-cutting business logic
- Not tied to a single use case (that's what Actions are for)
- Injected via constructor
- Stateless where possible

```php
class AccessResolver
{
    public function __construct(
        private TenantManager $tenantManager,
    ) {}

    public function can(User $user, string $permission, Model $resource): bool
    {
        // Check ownership
        if ($resource->user_id === $user->id) {
            return true;
        }

        // Check direct grants
        $directGrant = $this->getHighestDirectGrant($user, $resource);

        // Check team grants
        $teamGrant = $this->getHighestTeamGrant($user, $resource);

        // Check company grants
        $companyGrant = $this->getCompanyGrant($user, $resource);

        $highest = $this->resolveHighestPermission([
            $directGrant, $teamGrant, $companyGrant,
        ]);

        return $this->permissionSatisfies($highest, $permission)
            && $this->isWithinTimeConstraints($highest)
            && $this->isWithinViewConstraints($highest);
    }
}
```

### 6.5 Actions

**Rules:**

- One Action class = one use case
- Named with a verb: `CreateVaultItemAction`, `GrantAccessAction`
- Invokable (`__invoke`) — called like a function
- Accept typed parameters (use named arguments)
- Return the resulting model or void

```php
class GrantAccessAction
{
    public function __construct(
        private AccessResolver $accessResolver,
        private ActivityLogger $activityLogger,
    ) {}

    public function __invoke(
        User $grantedBy,
        Model $resource,
        string $subjectType,
        int $subjectId,
        string $permission,
        ?Carbon $expiresAt = null,
        ?int $maxViews = null,
        bool $startOnFirstView = false,
    ): AccessGrant {
        // Verify granter has share permission
        throw_unless(
            $this->accessResolver->can($grantedBy, 'share', $resource),
            new UnauthorizedException('You do not have permission to share this resource.')
        );

        $grant = AccessGrant::create([
            'grantable_type' => $resource::class,
            'grantable_id' => $resource->id,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'permission' => $permission,
            'expires_at' => $expiresAt,
            'max_views' => $maxViews,
            'start_on_first_view' => $startOnFirstView,
            'granted_by' => $grantedBy->id,
            'tenant_id' => app(TenantManager::class)->currentTenantId(),
        ]);

        event(new AccessGranted($grant, $grantedBy));

        return $grant;
    }
}
```

### 6.6 Form Requests

**Rules:**

- Every mutating endpoint has a Form Request
- Validation rules in `rules()` method
- Authorization in `authorize()` method
- Custom error messages in `messages()` when needed
- Use typed properties

```php
class CreateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:password,api_key,server,database,note'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:1000'],
            'url' => ['nullable', 'url', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'folder_id' => ['nullable', 'exists:folders,id'],
            'tags' => ['array'],
            'tags.*' => ['string', 'max:50'],
            'custom_fields' => ['array'],
            'custom_fields.*.key' => ['required', 'string', 'max:100'],
            'custom_fields.*.value' => ['required', 'string', 'max:1000'],
        ];
    }
}
```

### 6.7 API Resources

**Rules:**

- Every model has a Resource class
- Collections use `JsonResource::collection()` or a dedicated Collection Resource
- Transform logic in `toArray()` method
- Never expose encrypted/sensitive fields without explicit decryption
- Include relationships via conditional loading

```php
class VaultItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'username' => $this->username,
            'password' => $this->when(
                $this->resource->relationLoaded('decryptedPassword'),
                fn () => $this->decryptedPassword,
            ),
            'url' => $this->url,
            'notes' => $this->notes,
            'folder' => new FolderResource($this->whenLoaded('folder')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'favorite' => $this->favorite,
            'access' => [
                'can_edit' => $this->when(
                    isset($this->additional['permissions']),
                    fn () => $this->additional['permissions']['edit'] ?? false,
                ),
                'can_share' => $this->when(
                    isset($this->additional['permissions']),
                    fn () => $this->additional['permissions']['share'] ?? false,
                ),
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

### 6.8 Models

**Rules:**

- `$fillable` array — never use `$guarded = []` (mass assignment protection)
- Casts in `$casts` array
- Relationships defined as methods with return type hints
- Scopes named `scope{Name}` — camelCase
- Accessors/mutators named `get{Name}Attribute` / `set{Name}Attribute`
- Use traits for cross-cutting behavior (`BelongsToTenant`, `Encryptable`)

```php
class VaultItem extends Model
{
    use BelongsToTenant;
    use Encryptable;
    use SoftDeletes;

    protected array $encryptable = [
        'password',
        'api_key',
        'secret_value',
    ];

    protected $fillable = [
        'name',
        'type',
        'username',
        'password',
        'url',
        'notes',
        'folder_id',
        'favorite',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'favorite' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function accessGrants(): MorphMany
    {
        return $this->morphMany(AccessGrant::class, 'grantable');
    }

    public function scopeFavorite(Builder $query): Builder
    {
        return $query->where('favorite', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
}
```

### 6.9 Migrations

**Rules:**

- One table per migration
- Migration name: `create_{table}_table` or `add_{column}_to_{table}_table`
- Always include `down()` method that reverses the change
- Use `foreignId()` instead of `unsignedBigInteger()` for foreign keys
- Add indexes in the same migration as table creation
- Use `->after('column')` for new columns to keep schema readable

```php
return new class extends Migration {
    public function up(): void
    {
        Schema::create('vault_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->enum('type', ['password', 'api_key', 'server', 'database', 'note']);
            $table->text('username')->nullable();
            $table->text('password')->nullable();
            $table->text('api_key')->nullable();
            $table->string('url')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('favorite')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'folder_id']);
            $table->index(['tenant_id', 'favorite']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vault_items');
    }
};
```

### 6.10 Events & Listeners

**Rules:**

- Events named in past tense (`VaultItemCreated`, `AccessGranted`)
- Events carry the model and the acting user
- Listeners handle side effects (logging, notifications, cache invalidation)
- Use `ShouldQueue` interface for slow listeners
- Register in `EventServiceProvider`

```php
class AccessGranted
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public AccessGrant $grant,
        public User $grantedBy,
    ) {}
}
```

```php
class LogAccessGranted implements ShouldQueue
{
    public function __construct(
        private ActivityLogger $logger,
    ) {}

    public function handle(AccessGranted $event): void
    {
        $this->logger->log(
            user: $event->grantedBy,
            action: 'share',
            subject: $event->grant->grantable,
            properties: [
                'permission' => $event->grant->permission,
                'subject_type' => $event->grant->subject_type,
                'subject_id' => $event->grant->subject_id,
                'expires_at' => $event->grant->expires_at,
            ],
        );
    }
}
```

### 6.11 Jobs

**Rules:**

- Jobs named with verb (`ProcessAccessExpiration`, `SendInviteEmail`)
- Implement `ShouldQueue`
- Define queue and timeout in `$queue` and `$timeout` properties
- Handle failures gracefully — use `failed()` method for cleanup/alerting
- Idempotent where possible (safe to retry)

```php
class ProcessAccessExpiration implements ShouldQueue
{
    public $queue = 'expirations';
    public $timeout = 30;

    public function handle(AccessResolver $accessResolver): void
    {
        $expiredGrants = AccessGrant::where('expires_at', '<=', now())
            ->whereNull('revoked_at')
            ->get();

        foreach ($expiredGrants as $grant) {
            $grant->update(['revoked_at' => now()]);
            event(new AccessRevoked($grant, reason: 'expired'));
        }
    }
}
```

### 6.12 Notifications

**Rules:**

- One notification class per notification type
- Sent via `notify()` method on user or via `Notification::send()`
- Support multiple channels (mail, database) — database channel for in-app notifications
- Queued for async delivery

```php
class AccessRequestReceived extends Notification implements ShouldQueue
{
    public function __construct(
        public AccessRequest $request,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Access Request')
            ->line("{$this->request->requester->name} requested access to {$this->request->resource->name}")
            ->action('Review Request', url("/api/v1/access-requests/{$this->request->id}"))
            ->line("Requested permission: {$this->request->permission}")
            ->line("Requested duration: {$this->request->duration}");
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'access_request',
            'request_id' => $this->request->id,
            'requester_name' => $this->request->requester->name,
            'resource_name' => $this->request->resource->name,
            'permission' => $this->request->permission,
        ];
    }
}
```

---

## 7. Code Formatting & Style

### 7.1 PHP Standards

- Follow **PSR-12** as baseline
- Use **Laravel Pint** for automated formatting
- Strict typing: use `declare(strict_types=1)` in all PHP files
- Type hints and return types on all methods
- Use PHP 8.2+ features: readonly properties, enum, named arguments, constructor property promotion

**Key style rules:**

| Rule | Standard |
|---|---|
| Indentation | 4 spaces (no tabs) |
| Line length | 120 characters max |
| Class opening brace | Same line |
| Function opening brace | Next line |
| Control structure opening brace | Same line |
| Array syntax | Short array `[]` (not `array()`) |
| String quotes | Single quotes by default, double for interpolation |
| Trailing comma in multi-line arrays | Yes |
| Nullable types | `?type` syntax |
| Union types | `type1\|type2` syntax |
| Import classes | Use `use` statements (never inline FQCN) |
| Alphabetize imports | Yes (grouped: classes, functions, constants) |

### 7.2 Laravel Pint Configuration

`pint.json` (project root):

```json
{
  "preset": "laravel",
  "rules": {
    "strict_types": true,
    "ordered_imports": {
      "sort_algorithm": "alpha",
      "imports_order": ["classes", "functions", "constants"]
    },
    "no_unused_imports": true,
    "trailing_comma_in_multiline": true,
    "declare_strict_types": true,
    "native_function_invocation": false,
    "php_unit_test_class_requires_covers": false
  }
}
```

Run before every commit:

```bash
./vendor/bin/pint
```

### 7.3 IDE & Tooling

| Tool | Purpose | Config File |
|---|---|---|
| Laravel Pint | Code formatting | `pint.json` |
| PHPStan (Larastan) | Static analysis | `phpstan.neon` |
| Laravel IDE Helper | IDE autocompletion | `_ide_helper.php` |
| Rector | Code refactoring / modernization | `rector.php` |

**PHPStan configuration** (`phpstan.neon`):

```yaml
includes:
  - vendor/larastan/larastan/larastan.neon

parameters:
  level: 8
  paths:
    - app/
  excludePaths:
    - app/Console/Kernel.php
    - app/Exceptions/Handler.php
```

**EditorConfig** (`.editorconfig`):

```ini
root = true

[*]
charset = utf-8
end_of_line = lf
insert_final_newline = true
indent_style = space
indent_size = 4
trim_trailing_whitespace = true

[*.md]
trim_trailing_whitespace = false

[*.{yml,yaml,json}]
indent_size = 2
```

---

## 8. Security Guidelines

### 8.1 Encryption at Rest

| Data Type | Method |
|---|---|
| User login passwords | bcrypt via Laravel `Hash` facade (never reversible) |
| Vault secrets (passwords, API keys, credentials) | AES-256-CBC via Laravel `Crypt` facade |
| File contents | Filesystem permissions (private storage) for MVP; encrypted in future |
| API tokens | SHA-256 hash (Laravel Sanctum default) |
| Secure link passwords | bcrypt (same as user passwords) |
| 2FA secrets | AES-256 via `Crypt::encryptString()` — decrypted on demand by `TwoFactorService` |
| Recovery codes | Each code bcrypt-hashed, JSON of hashes encrypted via `Crypt::encryptString()` |

**Never log or expose:**

- Decrypted secret values
- Encryption keys
- API token plaintext (only show once on creation)
- 2FA secrets
- Recovery codes (only show once on generation)

### 8.2 Sensitive Field Handling

- Encrypted fields never appear in API responses without explicit decryption
- Encrypted fields are excluded from `$hidden` on models — use Resource classes to control output
- Never serialize encrypted values in JSON metadata
- Never include encrypted fields in search queries
- Never cache decrypted values in Redis or session

### 8.3 API Security

| Measure | Implementation |
|---|---|
| Authentication | Sanctum bearer tokens on every request |
| Authorization | Policy checks on every resource access |
| Rate limiting | Throttle middleware on all routes |
| Input validation | Form Requests on every mutating endpoint |
| SQL injection | Eloquent ORM with parameterized queries (never raw SQL with user input) |
| XSS | API-only (no HTML output) — but validate all string inputs |
| CORS | Restrict to known frontend domains (configurable) |
| HSTS | Enabled via middleware |
| Content-Type | `application/json` enforced on all responses |
| Request size | Limit to 10MB (file upload) / 1MB (JSON body) |

### 8.4 Input Validation

- Every input validated via Form Request
- Whitelist validation (define what's allowed, not what's blocked)
- Validate types, lengths, formats, and ranges
- Sanitize string inputs (trim whitespace)
- Reject unexpected fields (use `$request->validated()` only)
- Custom validation rules for domain-specific constraints (e.g., `StrongPassword`, `ValidTotpCode`)

---

## 9. Testing Standards

### 9.1 Test Structure

```
tests/
├── Feature/
│   └── Api/
│       └── V1/
│           ├── Auth/
│           │   ├── LoginTest.php
│           │   ├── RegisterTest.php
│           │   └── TwoFactorTest.php
│           ├── Vault/
│           │   ├── CreateVaultItemTest.php
│           │   ├── UpdateVaultItemTest.php
│           │   ├── DeleteVaultItemTest.php
│           │   └── ShareVaultItemTest.php
│           ├── Access/
│           │   ├── GrantAccessTest.php
│           │   ├── RevokeAccessTest.php
│           │   ├── TemporaryAccessTest.php
│           │   └── AccessRequestTest.php
│           └── ...
└── Unit/
    ├── Services/
    │   ├── AccessResolverTest.php
    │   ├── EncryptionServiceTest.php
    │   └── TenantManagerTest.php
    ├── Actions/
    │   ├── CreateVaultItemActionTest.php
    │   └── GrantAccessActionTest.php
    └── Models/
        └── VaultItemTest.php
```

### 9.2 Naming Conventions

| Type | Convention | Example |
|---|---|---|
| Test class | `{ClassUnderTest}Test` | `VaultItemControllerTest` |
| Test method | `test_{behavior}` (snake_case) | `test_user_can_create_vault_item` |
| Test method (negative) | `test_{behavior}_when_{condition}` | `test_cannot_create_item_without_authentication` |

### 9.3 What to Test

**Always test:**

- Every API endpoint (status code + response structure)
- Authentication required for protected endpoints
- Authorization (user can access own resources, cannot access others')
- Tenant isolation (user in tenant A cannot access tenant B's data)
- Validation rules (required fields, types, lengths, formats)
- Permission enforcement (view-only user cannot edit)
- Access expiration (expired grants don't grant access)
- One-time access (second view is blocked)
- Multi-tenant global scopes (queries are filtered by tenant)
- Encryption (sensitive fields are encrypted in DB, decrypted on retrieval)

**Test coverage target: 80%+ for MVP.**

### 9.4 Factories & Seeders

- Every model has a factory
- Factories produce valid default states
- Use states for variations: `UserFactory::admin()`, `VaultItemFactory::expired()`
- Seeders for development data only (not production)
- Use `RefreshDatabase` trait in all tests

```php
class VaultItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'type' => fake()->randomElement(['password', 'api_key', 'server']),
            'username' => fake()->userName(),
            'password' => Crypt::encrypt(fake()->password()),
            'url' => fake()->url(),
            'notes' => fake()->optional()->sentence(),
            'favorite' => fake()->boolean(20),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function favorite(): static
    {
        return $this->state(fn (array $attributes) => [
            'favorite' => true,
        ]);
    }
}
```

---

## 10. Git Workflow

### 10.1 Branch Strategy

| Branch | Purpose |
|---|---|
| `main` | Production-ready code |
| `develop` | Integration branch for features |
| `feature/{ticket}-{description}` | Individual feature (e.g., `feature/KEY-12-vault-items-crud`) |
| `fix/{ticket}-{description}` | Bug fix (e.g., `fix/KEY-45-tenant-scope-bypass`) |
| `hotfix/{ticket}-{description}` | Urgent production fix |

### 10.2 Commit Conventions

Follow **Conventional Commits**:

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

**Types:**

| Type | Description |
|---|---|
| `feat` | New feature |
| `fix` | Bug fix |
| `docs` | Documentation only |
| `style` | Code style (formatting, no logic change) |
| `refactor` | Code refactoring (no feature/fix) |
| `test` | Adding or modifying tests |
| `chore` | Build, tooling, dependencies |
| `perf` | Performance improvement |

**Examples:**

```
feat(vault): add create vault item endpoint with encryption
fix(tenant): prevent global scope bypass on raw queries
docs(api): update API documentation for access grants
test(access): add tests for temporary access expiration
refactor(services): extract access resolution logic to AccessResolver
```

### 10.3 PR Process

1. Create branch from `develop`
2. Write code following all guidelines in this document
3. Run `./vendor/bin/pint` (formatting)
4. Run `./vendor/bin/phpstan analyse` (static analysis)
5. Run `php artisan test` (all tests pass)
6. Create PR with description linking to ticket
7. PR must be reviewed before merge
8. Squash merge to `develop`
9. `develop` → `main` via release PR (with version tag)
