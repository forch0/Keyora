# Product Requirements Document (PRD)

# Zekura — Secure Password & Secrets Management Platform

| Field | Value |
|---|---|
| **Product Name** | Zekura |
| **Document Version** | 1.0 |
| **Status** | Draft |
| **Last Updated** | 2026-08-31 |
| **Owner** | — |

---

## 1. Executive Summary

Zekura is a secure, multi-tenant password and secrets management platform built with Laravel. It serves both individuals (personal vaults) and organizations (shared company workspaces) with granular access control, temporary access, secure file sharing, and full audit trails.

The platform differentiates itself through time-bound and one-time access mechanisms, approval-based access workflows, dynamic document watermarking, and comprehensive employee lifecycle management — capabilities typically found only in enterprise-grade solutions.

---

## 2. Problem Statement

### Problems We're Solving

1. **Scattered credential storage** — Teams store passwords, API keys, and server credentials across spreadsheets, chat messages, and sticky notes. There's no central, encrypted, auditable repository.
2. **Uncontrolled sharing** — Sharing sensitive credentials via email or chat leaves no audit trail, no expiration, and no revocation capability.
3. **No temporary access** — Contractors and temporary team members often get permanent access because there's no easy way to grant time-limited or one-time access.
4. **Offboarding gaps** — When employees leave, their access to shared secrets is rarely fully revoked, creating security blind spots.
5. **No visibility into "who has access to what"** — Organizations lack a clear view of who can access which secrets, files, or notes, making access audits difficult.

### Target Users

| User Type | Description |
|---|---|
| **Individual users** | Developers, IT professionals, and privacy-conscious users who need a personal encrypted vault |
| **Small teams** | 5–50 person teams needing shared credential management with basic permissions |
| **Mid-size companies** | 50–500 person organizations requiring team-based vaults, access requests, and audit trails |
| **Enterprises** | 500+ person organizations needing SSO, directory integration, compliance, and granular governance |

---

## 3. Goals & Objectives

### Business Goals

- Launch a functional MVP within 3 months covering personal vault, company workspace, team permissions, and secure sharing
- Achieve a security-first reputation with verifiable encryption and audit capabilities
- Offer a freemium SaaS model with clear upgrade paths

### Product Goals

- Provide a seamless personal vault experience comparable to consumer password managers
- Deliver enterprise-grade access control without enterprise-grade complexity
- Ensure every shared resource has an audit trail, expiration option, and revocation path

### Success Metrics

| Metric | Target (MVP) | Target (6 months) |
|---|---|---|
| User registration | 500 users | 5,000 users |
| Company workspaces created | 50 | 500 |
| Secrets stored | 5,000 | 50,000 |
| Secure links shared | 1,000 | 20,000 |
| Paid conversion rate | 3% | 8% |

### Non-Goals (for MVP)

- Browser extensions (Chrome, Firefox, etc.)
- Mobile applications (iOS, Android)
- Desktop applications
- Self-hosted / on-premise deployment
- Zero-knowledge architecture (server-side encryption for MVP; client-side encryption is a future goal)
- Automated secret rotation (rotation *reminders* are in scope; automated rotation is not)

---

## 4. User Personas

### Persona 1: Sarah — Solo Developer

- **Role:** Freelance web developer
- **Needs:** Personal vault for client credentials, API keys, and SSH access
- **Pain points:** Credentials scattered across notes apps and browser autofill
- **Key features used:** Personal vault, password generator, secure notes, folders, search

### Persona 2: Marcus — IT Team Lead

- **Role:** IT manager at a 100-person company
- **Needs:** Shared team vault for server credentials, database access, and cloud API keys
- **Pain points:** No visibility into who has access to what; contractors need temporary access
- **Key features used:** Company workspace, team vaults, temporary access, access requests, audit trails

### Persona 3: Elena — Security Administrator

- **Role:** Security ops at a 500-person enterprise
- **Needs:** Granular permissions, compliance reporting, employee offboarding, SSO
- **Pain points:** Offboarding doesn't revoke all access; no audit trail for shared secrets
- **Key features used:** Access revocation, employee lifecycle, activity & security dashboards, SSO, API tokens

### Persona 4: David — External Contractor

- **Role:** Short-term developer brought in for a 2-week project
- **Needs:** Temporary access to specific credentials without permanent account
- **Pain points:** Gets over-provisioned access that's never revoked
- **Key features used:** Secure share links, one-time access, expiring access, view-only documents

---

## 5. Functional Requirements

### 5.1 Personal Vault

| ID | Requirement | Priority | MVP |
|---|---|---|---|
| PV-01 | User can create, edit, and delete password credentials (name, username, password, URL, notes) | P0 | ✅ |
| PV-02 | User can store API keys with label, key value, and metadata | P0 | ✅ |
| PV-03 | User can store server credentials (host, port, username, password/key) | P1 | ✅ |
| PV-04 | User can store database credentials (type, host, port, database, username, password) | P1 | ✅ |
| PV-05 | User can create secure notes with rich-text formatting | P1 | ✅ |
| PV-06 | User can create secure cards (card number, holder, expiry, CVV) | P2 | ❌ |
| PV-07 | User can create secure identities (name, email, phone, address, SSN, etc.) | P2 | ❌ |
| PV-08 | User can upload files (max 10 MB per file) | P1 | ✅ |
| PV-09 | User can organize items into nested folders | P1 | ✅ |
| PV-10 | User can tag items with custom labels | P1 | ✅ |
| PV-11 | User can mark items as favorites | P1 | ✅ |
| PV-12 | User can search across all vault items | P0 | ✅ |
| PV-13 | User can view recently accessed and recently added items | P1 | ✅ |
| PV-14 | User can archive and restore items | P2 | ❌ |
| PV-15 | User can view password history for any credential | P2 | ❌ |
| PV-16 | All vault data is encrypted at rest using AES-256 | P0 | ✅ |

### 5.2 Company Workspace

| ID | Requirement | Priority | MVP |
|---|---|---|---|
| CW-01 | User can create a company workspace | P0 | ✅ |
| CW-02 | Workspace owner can edit company profile (name, logo, description) | P1 | ✅ |
| CW-03 | Owner can invite employees via email | P0 | ✅ |
| CW-04 | Owner can remove employees from workspace | P0 | ✅ |
| CW-05 | Owner can suspend employees (temporarily disable access) | P1 | ✅ |
| CW-06 | Each employee has a profile (name, email, role, avatar, teams) | P1 | ✅ |
| CW-07 | Owner can create departments/teams | P0 | ✅ |
| CW-08 | Owner can create groups (collections of teams) | P2 | ❌ |
| CW-09 | Employees can belong to multiple teams | P0 | ✅ |
| CW-10 | Teams have their own shared vault | P0 | ✅ |
| CW-11 | Organization-wide shared resources are accessible to all members | P1 | ✅ |

### 5.3 Team & Permission Management

| ID | Requirement | Priority | MVP |
|---|---|---|---|
| TP-01 | User can share an item with a specific individual | P0 | ✅ |
| TP-02 | User can share an item with a team | P0 | ✅ |
| TP-03 | User can share an item with multiple teams | P1 | ✅ |
| TP-04 | User can share an item with everyone in the company | P1 | ✅ |
| TP-05 | Permissions: View (read-only) | P0 | ✅ |
| TP-06 | Permissions: Edit (modify content) | P0 | ✅ |
| TP-07 | Permissions: Share (can re-share with others) | P1 | ✅ |
| TP-08 | Permissions: Download (can download files) | P1 | ✅ |
| TP-09 | Permissions: Manage (full control including deletion) | P1 | ✅ |
| TP-10 | User can view who has access to a specific resource | P0 | ✅ |
| TP-11 | User can view how someone received access (direct, via team, via link) | P1 | ✅ |
| TP-12 | User can change someone's access level | P0 | ✅ |
| TP-13 | User can remove someone's access | P0 | ✅ |
| TP-14 | Custom access rules (e.g., IP-restricted, time-windowed) | P3 | ❌ |

### 5.4 Password & Secret Management

| ID | Requirement | Priority | MVP |
|---|---|---|---|
| PS-01 | User can generate passwords with customizable criteria (length, character sets, symbols) | P0 | ✅ |
| PS-02 | User can check password strength (entropy-based scoring) | P0 | ✅ |
| PS-03 | User can store SSH credentials (key type, private key, public key, host) | P1 | ❌ |
| PS-04 | User can store cloud credentials (provider, access key, secret key, region) | P1 | ❌ |
| PS-05 | User can store environment variables as a secret group | P1 | ❌ |
| PS-06 | User can add custom secret fields to any item | P1 | ✅ |
| PS-07 | User can set password expiration dates with rotation reminders | P2 | ❌ |
| PS-08 | User can view secret version history | P2 | ❌ |
| PS-09 | User can favorite, tag, and folder-organize secrets | P1 | ✅ |

### 5.5 Temporary Access

| ID | Requirement | Priority | MVP |
|---|---|---|---|
| TA-01 | User can grant temporary access with a start time (immediate or scheduled) | P0 | ✅ |
| TA-02 | Access can start on first view (clock starts when recipient opens the resource) | P0 | ✅ |
| TA-03 | Preset durations: 15 min, 30 min, 1 hour, 24 hours | P0 | ✅ |
| TA-04 | Custom duration (user-specified start and end datetime) | P1 | ✅ |
| TA-05 | Maximum number of views (e.g., 1-time, 3-time) | P0 | ✅ |
| TA-06 | One-time access (self-destructs after single view) | P0 | ✅ |
| TA-07 | Automatic expiration when time limit or view limit is reached | P0 | ✅ |
| TA-08 | Expiration countdown visible to recipient | P1 | ✅ |
| TA-09 | Expiration warnings (email/in-app notification before expiry) | P1 | ✅ |

### 5.6 Access Requests

| ID | Requirement | Priority | MVP |
|---|---|---|---|
| AR-01 | User can request access to a secret, file, or folder | P0 | ✅ |
| AR-02 | Request includes desired duration and reason | P0 | ✅ |
| AR-03 | Resource owner can approve or reject the request | P0 | ✅ |
| AR-04 | Owner can modify requested duration before approving | P1 | ✅ |
| AR-05 | Owner can grant a different permission level than requested | P1 | ✅ |
| AR-06 | Requester can cancel their own request | P1 | ✅ |
| AR-07 | Users can view their pending requests (sent and received) | P0 | ✅ |
| AR-08 | Full request history is retained and viewable | P1 | ✅ |
| AR-09 | Email notifications on request submission, approval, and rejection | P1 | ✅ |

### 5.7 Access Revocation

| ID | Requirement | Priority | MVP |
|---|---|---|---|
| RV-01 | User can revoke access for a specific individual | P0 | ✅ |
| RV-02 | User can revoke access for a team | P0 | ✅ |
| RV-03 | User can revoke company-wide access | P1 | ✅ |
| RV-04 | User can revoke temporary access before expiration | P0 | ✅ |
| RV-05 | User can revoke file access | P0 | ✅ |
| RV-06 | User can revoke shared links | P0 | ✅ |
| RV-07 | Emergency revoke — instantly revoke all access for a specific employee | P0 | ✅ |
| RV-08 | Automatic access removal when an employee is offboarded | P0 | ✅ |

### 5.8 Secure Files

| ID | Requirement | Priority | MVP |
|---|---|---|---|
| SF-01 | User can upload files (max 10 MB per file) | P0 | ✅ |
| SF-02 | User can upload multiple files at once | P1 | ✅ |
| SF-03 | Files can be organized into folders | P1 | ✅ |
| SF-04 | Files can be searched by name and metadata | P1 | ✅ |
| SF-05 | File preview (images, PDFs, text) in-platform | P2 | ❌ |
| SF-06 | Files can be shared with permissions (view-only or downloadable) | P0 | ✅ |
| SF-07 | Downloads can be disabled on shared files | P0 | ✅ |
| SF-08 | File expiration (auto-delete after date) | P1 | ✅ |
| SF-09 | Temporary and one-time file access | P0 | ✅ |
| SF-10 | File version history (replace and track versions) | P2 | ❌ |
| SF-11 | Archive, restore, and delete files | P1 | ✅ |
| SF-12 | Full file access history (who viewed/downloaded and when) | P0 | ✅ |

### 5.9 Protected Document Viewing

| ID | Requirement | Priority | MVP |
|---|---|---|---|
| PD-01 | In-platform document viewer (no download required) | P1 | ❌ |
| PD-02 | View-only mode (download, print, copy restrictions) | P1 | ❌ |
| PD-03 | Dynamic watermarking (viewer name, org, timestamp, session ID) | P2 | ❌ |
| PD-04 | Expiring viewer sessions | P2 | ❌ |
| PD-05 | Re-authentication before viewing sensitive documents | P2 | ❌ |

### 5.10 Secure Sharing

| ID | Requirement | Priority | MVP |
|---|---|---|---|
| SS-01 | Share internally (individuals, teams, company-wide) | P0 | ✅ |
| SS-02 | Share externally via secure link | P0 | ✅ |
| SS-03 | Links can be password-protected | P0 | ✅ |
| SS-04 | Links can be OTP-protected (one-time PIN sent via email) | P1 | ✅ |
| SS-05 | External access requires email verification | P1 | ✅ |
| SS-06 | Link expiration (time-based) | P0 | ✅ |
| SS-07 | Link expiration on first view (expires N hours after first open) | P0 | ✅ |
| SS-08 | Maximum views per link | P1 | ✅ |
| SS-09 | One-time links (self-destruct after single view) | P0 | ✅ |
| SS-10 | Disable downloads on shared links | P0 | ✅ |
| SS-11 | Revoke links at any time | P0 | ✅ |
| SS-12 | View link activity (who accessed, when, from where) | P0 | ✅ |

### 5.11 Secure Notes

| ID | Requirement | Priority | MVP |
|---|---|---|---|
| SN-01 | User can create personal, company, and team notes | P1 | ✅ |
| SN-02 | Rich-text editing (bold, italic, lists, code blocks) | P1 | ✅ |
| SN-03 | Attachments on notes | P2 | ❌ |
| SN-04 | Notes can be shared with view-only or edit permissions | P1 | ✅ |
| SN-05 | Temporary note access with expiration | P1 | ✅ |
| SN-06 | Note version history | P2 | ❌ |
| SN-07 | Note search, folders, and tags | P1 | ✅ |

### 5.12 Search & Organization

| ID | Requirement | Priority | MVP |
|---|---|---|---|
| SO-01 | Global search across passwords, secrets, files, notes, people, and teams | P0 | ✅ |
| SO-02 | Nested folders for hierarchical organization | P1 | ✅ |
| SO-03 | Tags for cross-cutting categorization | P1 | ✅ |
| SO-04 | Favorites for quick access | P1 | ✅ |
| SO-05 | Filters (by type, tag, folder, shared status, expiration) | P1 | ✅ |
| SO-06 | Sorting (by name, date created, date modified, expiration) | P1 | ✅ |
| SO-07 | Recently accessed and recently created views | P1 | ✅ |
| SO-08 | Expiring soon view (items with access expiring within 48 hours) | P1 | ✅ |

### 5.13 Activity & Security

| ID | Requirement | Priority | MVP |
|---|---|---|---|
| AS-01 | Personal activity history (all actions by the user) | P0 | ✅ |
| AS-02 | Company activity feed (all actions within the workspace) | P1 | ✅ |
| AS-03 | Secret access history (who accessed a specific secret and when) | P0 | ✅ |
| AS-04 | File access history | P0 | ✅ |
| AS-05 | Sharing history (what was shared, with whom, by whom) | P0 | ✅ |
| AS-06 | Access-grant and access-revocation history | P1 | ✅ |
| AS-07 | Login history (timestamp, IP, device, browser) | P0 | ✅ |
| AS-08 | Security alerts: new-device login | P1 | ✅ |
| AS-09 | Security alerts: suspicious activity (e.g., multiple failed logins) | P2 | ❌ |
| AS-10 | Security alerts: expiring access | P1 | ✅ |
| AS-11 | Employee access overview (what each employee has access to) | P1 | ✅ |

### 5.14 Employee Lifecycle

| ID | Requirement | Priority | MVP |
|---|---|---|---|
| EL-01 | Invite employee via email with role assignment | P0 | ✅ |
| EL-02 | Employee onboarding flow (accept invite, set password, 2FA setup) | P0 | ✅ |
| EL-03 | Assign employee to one or more teams | P0 | ✅ |
| EL-04 | Assign employee role (member, admin, owner) | P0 | ✅ |
| EL-05 | Grant initial access during onboarding | P1 | ✅ |
| EL-06 | Change employee permissions and teams | P0 | ✅ |
| EL-07 | Suspend employee (temporarily disable all access) | P1 | ✅ |
| EL-08 | Offboard employee (revoke all access, deactivate account) | P0 | ✅ |
| EL-09 | Transfer employee-owned resources to another employee | P2 | ❌ |
| EL-10 | Preserve employee activity history after offboarding | P1 | ✅ |

### 5.15 Account Security

| ID | Requirement | Priority | MVP |
|---|---|---|---|
| AC-01 | Two-factor authentication (TOTP-based, e.g., Google Authenticator) | P0 | ✅ |
| AC-02 | Recovery codes generated on 2FA enablement | P0 | ✅ |
| AC-03 | Active device management (view and revoke sessions) | P1 | ✅ |
| AC-04 | Login history | P0 | ✅ |
| AC-05 | Logout from all devices | P0 | ✅ |
| AC-06 | Change password | P0 | ✅ |
| AC-07 | Password reset via email | P0 | ✅ |
| AC-08 | Re-authentication (password re-entry) for sensitive actions (delete, share externally, export) | P1 | ✅ |

### 5.16 Dashboards

| ID | Requirement | Priority | MVP |
|---|---|---|---|
| DB-01 | Personal dashboard: my vault summary, favorites, recently viewed | P0 | ✅ |
| DB-02 | Personal dashboard: shared with me, expiring access, pending requests | P1 | ✅ |
| DB-03 | Company dashboard: overview, members, teams | P1 | ✅ |
| DB-04 | Company dashboard: shared vaults, files, access requests | P1 | ✅ |
| DB-05 | Company dashboard: temporary access, security activity, expiring access | P1 | ✅ |
| DB-06 | Company dashboard: recent activity feed | P1 | ✅ |

### 5.17 SaaS Features

| ID | Requirement | Priority | MVP |
|---|---|---|---|
| SA-01 | Free plan: 1 personal vault, limited storage, limited items | P0 | ✅ |
| SA-02 | Team plan: company workspace, up to 10 members, increased storage | P0 | ✅ |
| SA-03 | Business plan: up to 100 members, advanced permissions, API access | P1 | ❌ |
| SA-04 | Enterprise plan: unlimited members, SSO, directory integration | P2 | ❌ |
| SA-05 | Subscription management (upgrade, downgrade, cancel) | P0 | ✅ |
| SA-06 | Storage, member, and vault limits enforced per plan | P0 | ✅ |
| SA-07 | Usage dashboard (storage used, members, vaults, items) | P1 | ✅ |
| SA-08 | Billing history and invoice download | P1 | ✅ |

### 5.18 Integrations

| ID | Requirement | Priority | MVP |
|---|---|---|---|
| IN-01 | Personal API tokens (read/write scope) | P1 | ❌ |
| IN-02 | Organization API tokens (admin-scoped) | P1 | ❌ |
| IN-03 | Webhooks for key events (access granted, access expired, item created) | P2 | ❌ |
| IN-04 | Slack notifications | P2 | ❌ |
| IN-05 | Microsoft Teams notifications | P2 | ❌ |
| IN-06 | GitHub integration (sync secrets to repo secrets) | P3 | ❌ |
| IN-07 | GitLab integration | P3 | ❌ |
| IN-08 | Cloud provider integrations (AWS, GCP, Azure) | P3 | ❌ |
| IN-09 | SSO (SAML 2.0 / OAuth) | P2 | ❌ |
| IN-10 | Enterprise directory integration (Active Directory, LDAP) | P3 | ❌ |

---

## 6. Non-Functional Requirements

### 6.1 Security

| ID | Requirement |
|---|---|
| NFR-S01 | All sensitive data (passwords, API keys, secrets, files) encrypted at rest using AES-256 |
| NFR-S02 | All data in transit encrypted via TLS 1.2+ |
| NFR-S03 | Passwords hashed using bcrypt or Argon2 (never stored in plaintext) |
| NFR-S04 | Encryption keys stored separately from encrypted data |
| NFR-S05 | All access and modification events logged immutably |
| NFR-S06 | Rate limiting on authentication endpoints (max 5 attempts per minute) |
| NFR-S07 | CSRF protection on all forms |
| NFR-S08 | XSS prevention via output escaping and CSP headers |
| NFR-S09 | SQL injection prevention via parameterized queries (Eloquent ORM) |
| NFR-S10 | Sensitive actions require re-authentication |

### 6.2 Performance

| ID | Requirement |
|---|---|
| NFR-P01 | Page load time under 2 seconds for dashboard views |
| NFR-P02 | Search results returned within 1 second for vaults up to 10,000 items |
| NFR-P03 | File upload up to 10 MB completes within 10 seconds |
| NFR-P04 | API response time under 500ms for 95th percentile |
| NFR-P05 | Background jobs (notifications, expirations) processed within 60 seconds |

### 6.3 Availability

| ID | Requirement |
|---|---|
| NFR-A01 | Application uptime target: 99.5% (MVP), 99.9% (post-MVP) |
| NFR-A02 | Automated database backups daily with 30-day retention |
| NFR-A03 | Graceful degradation — core vault access remains available if non-critical services fail |

### 6.4 Scalability

| ID | Requirement |
|---|---|
| NFR-SC01 | Support up to 1,000 concurrent users at MVP launch |
| NFR-SC02 | Database designed for horizontal read scaling via replicas |
| NFR-SC03 | File storage abstracted via Laravel Filesystem (local → S3 migration without code changes) |
| NFR-SC04 | Queue-based processing for non-real-time tasks (notifications, audit logging, expirations) |

### 6.5 Usability

| ID | Requirement |
|---|---|
| NFR-U01 | Responsive design (desktop, tablet, mobile web) |
| NFR-U02 | Keyboard navigation support for all primary actions |
| NFR-U03 | Clear empty states with calls to action |
| NFR-U04 | Confirmation dialogs for all destructive actions |
| NFR-U05 | Toast notifications for success/error feedback |

---

## 7. Technical Architecture

### 7.1 Technology Stack

| Layer | Technology | Rationale |
|---|---|---|
| Backend framework | Laravel 11 (PHP 8.2+) | Mature, expressive, built-in auth, queues, events |
| Database | MySQL 8.0 / PostgreSQL 15 | Relational data with complex permission relationships |
| Frontend | Blade + Livewire (or Inertia + Vue) | Server-rendered with reactive components |
| Authentication | Laravel Fortify + Sanctum | Session + token-based auth out of the box |
| Authorization | Laravel Policies + Gates | Resource-level permission checks |
| Encryption | Laravel Crypt (AES-256-CBC) | Built-in, well-tested encryption layer |
| File storage | Laravel Filesystem (local → S3) | Swappable storage drivers |
| Queue | Laravel Queue (Redis) | Async processing for notifications, expirations, audits |
| Mail | Laravel Mail (SMTP / SES) | Transactional emails for invites, alerts, requests |
| Payments | Paystack | Subscription billing, invoicing, and payment processing (Africa-focused with global card support) |

### 7.2 Core Data Models

```
User
 ├── PersonalVault (1:1)
 │    ├── Items (1:N) → Password, ApiKey, ServerCred, DbCred, SecureNote, SecureFile
 │    ├── Folders (1:N, self-referencing for nesting)
 │    └── Tags (M:N)
 ├── CompanyWorkspace (1:N as owner)
 │    ├── Employees (M:N via CompanyMember)
 │    ├── Teams (1:N)
 │    │    ├── TeamMembers (M:N)
 │    │    └── TeamVault (1:1)
 │    │         └── Items (1:N)
 │    └── OrgResources (1:N)
 ├── AccessGrants (1:N as grantor / grantee)
 ├── AccessRequests (1:N as requester / approver)
 ├── SecureLinks (1:N)
 ├── ActivityLogs (1:N)
 └── ApiTokens (1:N)
```

### 7.3 Key Design Decisions

1. **Server-side encryption for MVP** — Secrets are encrypted with Laravel's AES-256 before storage. Client-side (zero-knowledge) encryption is a future goal but adds significant complexity for MVP.
2. **Permission system via polymorphic grants** — `AccessGrant` model with `grantable_type` and `grantable_id` allows sharing any resource type with any subject type (user, team, company).
3. **Scheduled job for expirations** — A Laravel scheduled command runs every minute to check and revoke expired access grants and links.
4. **Immutable audit log** — Activity logs are append-only; no update or delete operations exposed.
5. **File storage abstraction** — Files stored via Laravel Filesystem disk, allowing migration from local to S3 without code changes.

---

## 8. MVP Scope

### 8.1 In Scope (MVP)

Based on the requirements marked ✅ above, the MVP includes:

- **Personal vault** — Credentials, API keys, server/database creds, secure notes, file uploads, folders, tags, favorites, search
- **Company workspace** — Create workspace, invite/remove/suspend employees, teams, team vaults, org-wide resources
- **Team & permission management** — Share with individuals/teams/company, view/edit/share/download/manage permissions, access visibility
- **Temporary access** — Immediate/scheduled/first-view start, preset + custom durations, view limits, one-time access, expiration warnings
- **Access requests** — Request, approve, reject, modify, cancel, history, notifications
- **Access revocation** — Individual, team, company-wide, temporary, file, link, emergency, offboarding auto-revoke
- **Secure files** — Upload, organize, share, view-only/downloadable, expiration, temporary/one-time access, access history
- **Secure sharing** — Internal + external, password/OTP-protected links, email verification, expiration, max views, one-time links, revoke, activity tracking
- **Secure notes** — Personal/company/team, rich-text, sharing with permissions, temporary access, search/organize
- **Search & organization** — Global search, nested folders, tags, favorites, filters, sorting, recent views, expiring soon
- **Activity & security** — Personal/company activity, access histories, login history, new-device + expiring-access alerts, employee access overview
- **Employee lifecycle** — Invite, onboard, assign teams/roles, change permissions, suspend, offboard, preserve history
- **Account security** — 2FA, recovery codes, device management, login history, logout all, password change/reset, re-auth for sensitive actions
- **Dashboards** — Personal + company dashboards with all sub-views
- **SaaS** — Free + Team plans, subscription management, limits, usage dashboard, billing

### 8.2 Out of Scope (MVP)

- Secure cards and secure identities
- SSH credentials, cloud credentials, environment variable groups
- Password expiration and rotation reminders
- Secret versioning and password history
- Protected document viewing (in-platform viewer, watermarking, copy restrictions)
- File preview, file version history
- Note attachments and version history
- Suspicious activity detection
- Employee resource transfer on offboarding
- Business and Enterprise plans
- All integrations (API tokens, webhooks, Slack/Teams, GitHub/GitLab, cloud providers, SSO, directory)
- Browser extensions, mobile apps, desktop apps
- Client-side / zero-knowledge encryption
- Custom access rules (IP restriction, time windows)

---

## 9. Release Plan

| Phase | Duration | Scope |
|---|---|---|
| **Phase 1 — Foundation** | Weeks 1–4 | Auth, user model, personal vault (credentials, notes, folders, tags), encryption layer, basic UI |
| **Phase 2 — Collaboration** | Weeks 5–8 | Company workspace, teams, team vaults, permission system, sharing (internal), access grants |
| **Phase 3 — Advanced Access** | Weeks 9–12 | Temporary access, access requests, access revocation, secure links (external sharing), expirations |
| **Phase 4 — Audit & Security** | Weeks 13–16 | Activity logs, audit trails, dashboards, 2FA, device management, security alerts, employee lifecycle |
| **Phase 5 — SaaS & Polish** | Weeks 17–20 | Stripe billing, plan limits, usage dashboard, UI polish, performance optimization, testing |
| **Phase 6 — Post-MVP** | Ongoing | API tokens, webhooks, integrations, protected viewing, watermarking, advanced plans, SSO |

---

## 10. Risks & Mitigations

| Risk | Impact | Likelihood | Mitigation |
|---|---|---|---|
| Encryption implementation flaws | Critical | Medium | Use Laravel's built-in Crypt; never roll custom crypto; security audit before launch |
| Scope creep | High | High | Strict MVP boundary; features outside MVP are documented but deferred |
| Performance with large vaults | Medium | Medium | Index key columns; paginate all list views; benchmark with 10K items |
| Permission logic bugs (over/under-sharing) | Critical | Medium | Comprehensive policy tests; integration tests for every share/revoke scenario |
| File storage costs | Medium | Low | 10 MB limit per file; S3 with lifecycle policies; storage quotas per plan |
| Third-party dependencies (Paystack, mail) | Medium | Low | Abstract behind Laravel services; fallback providers configured |

---

## 11. Open Questions

| # | Question | Status |
|---|---|---|
| 1 | Should MVP use Blade + Livewire or Inertia + Vue for frontend? | **Decision needed** |
| 2 | Should we use MySQL or PostgreSQL for MVP? | **Decision needed** |
| 3 | ~~Is Stripe the payment provider, or do we need alternatives?~~ **Resolved: Paystack** | **Resolved** |
| 4 | Should the free plan have a time limit (e.g., 30-day trial) or be permanently free with limits? | **Decision needed** |
| 5 | Do we need email infrastructure (SES, Postmark) from day one, or is SMTP sufficient for MVP? | **Decision needed** |
| 6 | Should audit logs be stored in the primary database or a separate log store (e.g., Elasticsearch)? | **Decision needed** |

---

## 12. Glossary

| Term | Definition |
|---|---|
| **Vault** | An encrypted container for storing secrets, files, and notes |
| **Secret** | Any sensitive data item (password, API key, credential, note) |
| **Access Grant** | A permission record linking a subject (user/team/company) to a resource with a specific permission level |
| **Secure Link** | A shareable URL that provides external access to a resource with optional protection (password, OTP, expiration, view limits) |
| **Temporary Access** | An access grant with a time or view limit that auto-expires |
| **One-time Access** | An access grant or link that self-destructs after a single view |
| **First-view Expiration** | A link whose expiration countdown begins when the recipient first opens it |
| **Break-glass / Emergency Revoke** | An action that instantly revokes all access for a specific user |
| **Offboarding** | The process of removing an employee from the workspace, revoking all access, and preserving their activity history |
| **Watermarking** | Overlaying viewer identity and metadata onto a document being viewed to deter screenshots and sharing |
| **2FA** | Two-factor authentication requiring a second verification (TOTP) in addition to password |
| **RBAC** | Role-Based Access Control — permission system based on roles and resource-level grants |
