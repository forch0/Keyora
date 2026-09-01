# 🔐 Keyora

**Keyora** is a secure, full-featured password and secrets management platform built with Laravel. It provides personal vaults for individuals and shared workspaces for teams and organizations — covering everything from credential storage and secure file sharing to granular access control, temporary access, and full audit trails.

---

## Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
  - [Personal Vault](#personal-vault)
  - [Company Workspace](#company-workspace)
  - [Team & Permission Management](#team--permission-management)
  - [Password & Secret Management](#password--secret-management)
  - [Temporary Access](#temporary-access)
  - [Access Requests](#access-requests)
  - [Access Revocation](#access-revocation)
  - [Secure Files](#secure-files)
  - [Protected Document Viewing](#protected-document-viewing)
  - [Secure Sharing](#secure-sharing)
  - [Secure Notes](#secure-notes)
  - [Search & Organization](#search--organization)
  - [Activity & Security](#activity--security)
  - [Employee Lifecycle](#employee-lifecycle)
  - [Account Security](#account-security)
  - [Dashboards](#dashboards)
  - [SaaS Features](#saas-features)
  - [Integrations](#integrations)
  - [Advanced / Differentiating Features](#advanced--differentiating-features)
- [Tech Stack](#tech-stack)
- [Getting Started](#getting-started)
- [Project Structure](#project-structure)
- [License](#license)

---

## Overview

Keyora is designed to be a comprehensive secrets management solution that serves both individuals and organizations. It combines the convenience of a personal password manager with the governance, sharing, and auditing capabilities required by teams and enterprises.

### Core Value Propositions

- **Personal + Company vaults** — Keep personal credentials private while collaborating on shared company resources.
- **Granular access control** — Share with individuals, teams, or the entire organization with view, edit, share, download, and manage permissions.
- **Temporary & expiring access** — Grant time-limited or one-time access to secrets, files, and notes with automatic revocation.
- **Full audit trails** — Every access, share, and revocation is logged and traceable.
- **Secure external sharing** — Share sensitive data with external parties via password-protected, OTP-verified, or expiring secure links.

---

## Key Features

### Personal Vault

A private, encrypted vault for individual users to store and organize their sensitive data.

- Save login credentials, API keys, server credentials, and database credentials
- Secure notes, secure cards, and secure identities
- Personal file storage (up to 10 MB per file)
- Organize with folders, tags, and favorites
- Search, recently viewed, and recently added views
- Archive and restore items
- Edit and delete items
- Password history tracking

### Company Workspace

Shared organizational workspace with member and team management.

- Create and manage company workspace
- Company profile management
- Invite, remove, and suspend employees
- Employee profiles
- Departments and teams
- Groups with multiple teams per employee
- Team management
- Organization-wide shared resources

### Team & Permission Management

Fine-grained sharing and permission system for collaborative access control.

- Share with an individual, a team, multiple teams, or everyone in the company
- Permission levels: **View**, **Edit**, **Share**, **Download**, **Manage**
- Custom access rules
- View who has access and how they received it
- Change or remove someone's access at any time

### Password & Secret Management

Centralized management for all types of secrets.

- Password credentials, API keys, SSH credentials, database credentials, cloud credentials
- Environment variables
- Secure notes with custom secret fields
- Password generator and strength checker
- Password expiration and rotation reminders
- Secret history and versioning
- Favorite, tag, and folder organization for secrets

### Temporary Access

Time-bound access grants with flexible duration options.

- Access starting immediately or on first view
- Scheduled access with custom start and end times
- Preset durations: 15 minutes, 30 minutes, 1 hour, 24 hours, or custom
- Maximum number of views / one-time access
- Automatic expiration and revocation
- Expiration countdown and warnings

### Access Requests

Workflow for requesting and approving access to restricted resources.

- Request access to secrets, files, or folders
- Specify requested duration and reason
- Approve, reject, or modify requests (change duration or permission level)
- Cancel requests
- View pending requests and full request history

### Access Revocation

Comprehensive revocation controls for managing and removing access.

- Revoke individual, team, or company-wide access
- Revoke temporary access, file access, and shared links
- Emergency revoke (break-glass)
- Revoke all access for a specific employee
- Automatic access removal when an employee leaves the organization

### Secure Files

Encrypted file storage with sharing and access controls (10 MB limit per file).

- Single and multiple file uploads
- File folders, organization, and search
- File preview and version history
- File sharing with permissions (view-only or downloadable)
- Disable downloads on shared files
- File expiration, temporary access, and one-time access
- Replace, archive, restore, and delete files
- Full file access history

### Protected Document Viewing

In-platform document viewer with security restrictions.

- View-only mode with download, print, and copy restrictions
- Dynamic watermarking (viewer name, organization, timestamp, session)
- Expiring viewer sessions
- Re-authentication before viewing sensitive documents

### Secure Sharing

Share resources internally and externally with multiple security layers.

- Internal sharing (individuals, teams, company-wide)
- External sharing via secure links
- Password-protected and OTP-protected links
- Email verification for external access
- Link expiration (time-based or first-view-based)
- Maximum views and one-time links
- Disable downloads on shared links
- Revoke links and view link activity (who accessed and when)

### Secure Notes

Rich-text notes with sharing and access controls.

- Personal, company, and team notes
- Rich-text editing with attachments
- Note sharing with view-only or edit permissions
- Temporary note access and expiration
- Note version history
- Search, folders, and tags for organization

### Search & Organization

Powerful search and organization across all resource types.

- Global search across passwords, secrets, files, notes, people, and teams
- Nested folders for hierarchical organization
- Tags and favorites for quick access
- Filters and sorting options
- Recently accessed and recently created views
- Expiring soon view for time-sensitive items

### Activity & Security

Comprehensive auditing and security monitoring.

- Personal activity history
- Company-wide activity feed
- Secret, file, and sharing access history
- Access-grant and access-revocation history
- Login history
- Security alerts: new-device, suspicious activity, expiring-access
- Employee access overview

### Employee Lifecycle

End-to-end employee management from onboarding to offboarding.

- Invite and onboard employees
- Assign teams, roles, and initial access
- Change permissions as roles evolve
- Suspend employees
- Offboard employees with full access revocation
- Transfer employee-owned resources
- Preserve employee activity history for compliance

### Account Security

User-level security settings and controls.

- Two-factor authentication (2FA)
- Recovery codes
- Active device management
- Login history and logout from all devices
- Change password and password reset
- Re-authentication for sensitive actions

### Dashboards

Role-based dashboards for personal and company-wide visibility.

**Personal Dashboard**
- My vault, favorites, recently viewed
- Shared with me, expiring access
- Pending access requests

**Company Dashboard**
- Company overview, members, and teams
- Shared vaults and files
- Access requests and temporary access
- Security activity and expiring access
- Recent activity feed

### SaaS Features

Multi-tier subscription model with self-service management.

- Plans: **Free**, **Team**, **Business**, **Enterprise**
- Subscription management (upgrade, downgrade, cancel)
- Storage, member, and vault limits per plan
- Usage dashboard
- Billing history and invoices

### Integrations

Extensible platform with API access and third-party integrations.

- API access with personal and organization API tokens
- Webhooks for event-driven automation
- Slack and Microsoft Teams notifications
- GitHub and GitLab integration
- Cloud provider integrations
- SSO (Single Sign-On)
- Enterprise directory integration (e.g., Active Directory, LDAP)

### Advanced / Differentiating Features

Standout capabilities that set Keyora apart:

- **Access starts when first viewed** — The clock starts ticking only when the recipient actually opens the shared resource.
- **One-time secret & file access** — Access self-destructs after a single view.
- **Time-limited access** — Grant access for precise durations with automatic revocation.
- **View-only documents** — Prevent downloads, prints, and copies with in-platform viewing.
- **Dynamic document watermarking** — Embed viewer identity, organization, and timestamps directly into viewed documents.
- **Approval-based access** — Structured request-and-approve workflow for sensitive resources.
- **Emergency access revocation** — Break-glass capability to instantly revoke all access.
- **Employee offboarding** — Automated access removal and resource transfer when employees leave.
- **Secure external sharing** — Share with external parties without compromising security.
- **Access review dashboards:**
  - *"Who has access to this?"* — Resource-centric access view.
  - *"What does this employee have access to?"* — Employee-centric access view.
- **Temporary contractor access** — Short-lived, scoped access for external contractors.
- **Secret rotation reminders** — Proactive alerts to rotate aging credentials.
- **Access expiration warnings** — Notifications before temporary access expires.
- **Personal + company vault separation** — Clear boundary between personal and organizational data.
- **Team-based vaults** — Dedicated vaults per team with cross-team sharing support.
- **One-time secure links** — Self-destructing links for sharing sensitive data externally.
- **Access history for every resource** — Complete audit trail for compliance and security.

---

## Tech Stack

| Layer | Technology |
|---|---|
| **Backend** | Laravel 13 (PHP 8.4+) |
| **Database** | MySQL 8.0 |
| **Frontend** | API-only (frontend TBD) |
| **Authentication** | Laravel Sanctum |
| **Encryption** | AES-256 (via Laravel Crypt) |
| **File Storage** | Laravel Filesystem (local private disk / S3) |
| **Queue** | Redis 7 |
| **Cache** | Redis 7 |
| **API** | RESTful JSON API (`/api/v1/`) |
| **Payments** | Paystack (subscription billing & invoicing) |
| **Dev Tools** | Docker, Telescope, Nimbus, Pint, Larastan |

---

## Getting Started

### Prerequisites

- **Docker** (recommended) — Docker Desktop with WSL2
- **Or** PHP >= 8.3 + Composer + MySQL 8.0 + Redis 7

### Quick Start with Docker (Recommended)

```bash
# Clone the repository
git clone https://github.com/your-username/keyora.git
cd keyora

# Build and start all containers
docker compose up -d --build

# Install Composer dependencies (inside container)
docker compose exec app composer install

# Generate application key
docker compose exec app php artisan key:generate

# Run database migrations
docker compose exec app php artisan migrate
```

Navigate to `http://localhost:8080/api/v1/` to verify the API is running.

### Local Development (without Docker)

```bash
# Clone the repository
git clone https://github.com/your-username/keyora.git
cd keyora

# Install PHP dependencies
cd src && composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate

# Start the development server
php artisan serve
```

Navigate to `http://localhost:8000` to access the application.

### Development Tools

| Tool | URL | Purpose |
|---|---|---|
| **Nimbus** | `http://localhost:8080/api-client` | In-browser API client (auto-discovers routes, Sanctum auth, impersonation) |
| **Telescope** | `http://localhost:8080/telescope` | Debug assistant (requests, queries, jobs, mail) |
| **Mailpit** | `http://localhost:8025` | Local email testing (SMTP on port 1025) |

---

## Project Structure

```
keyora/
├── src/                        # Laravel application
│   ├── app/
│   │   ├── Actions/             # Single-action business logic classes
│   │   ├── Events/              # Domain events
│   │   ├── Jobs/                # Queue jobs
│   │   ├── Listeners/           # Event listeners
│   │   ├── Notifications/       # Email/Slack notifications
│   │   ├── Policies/            # Authorization policies
│   │   ├── Rules/               # Custom validation rules
│   │   ├── Services/            # Service classes (Encryption, TenantManager, etc.)
│   │   ├── Traits/              # Reusable traits
│   │   ├── Enums/               # PHP enums
│   │   └── Http/
│   │       ├── Controllers/Api/V1/  # API controllers (versioned)
│   │       ├── Middleware/          # Custom middleware
│   │       ├── Requests/            # Form request validation
│   │       └── Resources/V1/        # API resources (transformers)
│   ├── bootstrap/app.php        # App bootstrap (routing, middleware, exceptions)
│   ├── config/                  # Configuration files
│   ├── database/
│   │   ├── migrations/          # Database schema
│   │   └── seeders/             # Seed data
│   ├── routes/
│   │   ├── api.php              # API routes (v1)
│   │   └── console.php          # Console routes
│   ├── tests/
│   │   ├── Helpers/             # Test traits (AuthHelper, TenantHelper)
│   │   ├── Unit/                # Unit tests
│   │   └── Feature/             # Feature tests
│   ├── composer.json
│   ├── pint.json                # Code formatting config
│   ├── phpstan.neon             # Static analysis config
│   └── .env                     # Environment configuration
├── docker/                      # Docker configuration
│   ├── Dockerfile               # PHP 8.4-FPM Alpine image
│   ├── nginx/default.conf       # Nginx reverse proxy config
│   └── php/local.ini            # PHP INI overrides
├── docker-compose.yml           # Docker Compose (app, nginx, mysql, redis, mailpit)
├── docs/
│   └── modules/                 # 26 module documentation files
│       ├── 00-index.md          # Module index & dependency graph
│       ├── 01-project-setup.md  # ... through ...
│       └── 26-saas-billing-part-2.md
├── BUILD_LOG.md                 # Implementation progress tracker
├── PRD.md                       # Product Requirements Document
├── ARCHITECTURE.md              # Architecture & ADRs
├── README.md
└── .gitignore
```

---

## License

This project is proprietary software. All rights reserved.
