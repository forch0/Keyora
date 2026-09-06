# Keyora — Module Index

This document tracks all build modules in dependency order. Each module is a single-responsibility unit of work. Complete modules in order — later modules depend on earlier ones.

## Build Order

| # | Module | File | Status | Dependencies |
|---|---|---|---|---|
| 01 | Project Setup & Foundation | [01-project-setup.md](01-project-setup.md) | Not Started | — |
| 02 | Authentication | [02-authentication.md](02-authentication.md) | Not Started | 01 |
| 03 | Multi-Tenancy | [03-multi-tenancy.md](03-multi-tenancy.md) | Not Started | 01, 02 |
| 04 | Company Workspace | [04-company-workspace.md](04-company-workspace.md) | Not Started | 02, 03 |
| 05 | Personal Vault — Part 1: Core CRUD & Encryption | [05-personal-vault-part-1.md](05-personal-vault-part-1.md) | Not Started | 01, 02 |
| 06 | Personal Vault — Part 2: Organization | [06-personal-vault-part-2.md](06-personal-vault-part-2.md) | Not Started | 05 |
| 07 | Teams & Team Vaults | [07-teams.md](07-teams.md) | Not Started | 03, 04 |
| 08 | Permission System — Part 1: Core | [08-permission-system-part-1.md](08-permission-system-part-1.md) | Not Started | 02, 03, 07 |
| 09 | Permission System — Part 2: Sharing & Access Management | [09-permission-system-part-2.md](09-permission-system-part-2.md) | Not Started | 08 |
| 10 | Password Tools | [10-password-tools.md](10-password-tools.md) | Not Started | 05 |
| 11 | Secure Files — Part 1: Storage & Management | [11-secure-files-part-1.md](11-secure-files-part-1.md) | Not Started | 01, 03 |
| 12 | Secure Files — Part 2: Sharing & Access | [12-secure-files-part-2.md](12-secure-files-part-2.md) | Not Started | 08, 09, 11 |
| 13 | Secure Notes | [13-secure-notes.md](13-secure-notes.md) | Not Started | 05, 08, 09 |
| 14 | Temporary Access | [14-temporary-access.md](14-temporary-access.md) | Not Started | 08, 09 |
| 15 | Access Requests | [15-access-requests.md](15-access-requests.md) | Not Started | 08, 09, 14 |
| 16 | Access Revocation | [16-access-revocation.md](16-access-revocation.md) | Not Started | 08, 09, 14 |
| 17 | Secure External Sharing — Part 1: Link Creation | [17-secure-sharing-part-1.md](17-secure-sharing-part-1.md) | Not Started | 08, 09 |
| 18 | Secure External Sharing — Part 2: Link Access & Tracking | [18-secure-sharing-part-2.md](18-secure-sharing-part-2.md) | Not Started | 17 |
| 19 | Search & Organization | [19-search-and-organization.md](19-search-and-organization.md) | Not Started | 05, 06, 11, 13 |
| 20 | Activity & Audit Logging | [20-activity-audit-logging.md](20-activity-audit-logging.md) | Not Started | All prior |
| 21 | Security Alerts | [21-security-alerts.md](21-security-alerts.md) | Not Started | 20 |
| 22 | Employee Lifecycle | [22-employee-lifecycle.md](22-employee-lifecycle.md) | Not Started | 04, 07, 08, 16, 20 |
| 23 | Account Security | [23-account-security.md](23-account-security.md) | Not Started | 02, 20 |
| 24 | Dashboards | [24-dashboards.md](24-dashboards.md) | Not Started | All prior |
| 25 | SaaS & Billing — Part 1: Plans & Limits | [25-saas-billing-part-1.md](25-saas-billing-part-1.md) | Skipped | 03, 04 |
| 26 | SaaS & Billing — Part 2: Paystack Integration | [26-saas-billing-part-2.md](26-saas-billing-part-2.md) | Skipped | 25 |
| 27 | Rate Limiting & API Throttling | [27-rate-limiting.md](27-rate-limiting.md) | Not Started | 02, 23 |
| 28 | API Documentation (OpenAPI/Scribe) | [28-api-documentation.md](28-api-documentation.md) | Not Started | All prior |
| 29 | Soft Deletes Consistency | [29-soft-deletes-consistency.md](29-soft-deletes-consistency.md) | Not Started | All prior |
| 30 | Bulk Operations | [30-bulk-operations.md](30-bulk-operations.md) | Not Started | 05, 08, 11, 13 |
| 31 | Dashboard Caching & Performance | [31-dashboard-caching.md](31-dashboard-caching.md) | Not Started | 24 |

## Status Legend

| Status | Meaning |
|---|---|
| Not Started | Module has not been started |
| In Progress | Module is currently being developed |
| Review | Module is complete but awaiting verification |
| Complete | Module is done and tested |

## How to Use

1. Start with Module 01 and work downwards
2. Each module file contains: models, migrations, endpoints, services, tests, and acceptance criteria
3. Check off acceptance criteria as you complete them
4. Update the status column in this index when starting/completing a module
5. Do not skip ahead — later modules depend on earlier ones being complete
