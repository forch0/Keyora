# Keyora Frontend — Module Index

This document tracks all frontend build modules in dependency order. Each module is a single-responsibility unit of work. Complete modules in order — later modules depend on earlier ones being complete.

## Build Order

| # | Module | File | Status | Dependencies |
|---|---|---|---|---|
| F01 | Project Setup & Foundation | [F01-project-setup.md](modules/F01-project-setup.md) | Complete | — |
| F02 | Authentication & 2FA | [F02-authentication.md](modules/F02-authentication.md) | Complete | F01 |
| F03 | App Shell & Navigation | [F03-app-shell.md](modules/F03-app-shell.md) | Complete | F02 |
| F04 | Personal Vault — Part 1: List & Detail | [F04-personal-vault-part-1.md](modules/F04-personal-vault-part-1.md) | Complete | F02, F03 |
| F05 | Personal Vault — Part 2: Create, Edit & Delete | [F05-personal-vault-part-2.md](modules/F05-personal-vault-part-2.md) | Complete | F04 |
| F06 | Personal Vault — Part 3: Folders, Tags & Organization | [F06-personal-vault-part-3.md](modules/F06-personal-vault-part-3.md) | Complete | F04, F05 |
| F07 | Personal Vault — Part 4: Trash & Bulk Operations | [F07-personal-vault-part-4.md](modules/F07-personal-vault-part-4.md) | Not Started | F05, F06 |
| F08 | Password Tools | [F08-password-tools.md](modules/F08-password-tools.md) | Complete | F03 |
| F09 | Dashboard | [F09-dashboard.md](modules/F09-dashboard.md) | Complete | F03, F04 |
| F10 | Tenant Context & Workspace Switcher | [F10-tenant-context.md](modules/F10-tenant-context.md) | Complete | F02, F03 |
| F11 | Shared Vault — Org & Team Items | [F11-shared-vault.md](modules/F11-shared-vault.md) | Not Started | F04, F05, F10 |
| F12 | Teams Management | [F12-teams.md](modules/F12-teams.md) | Complete | F10, F11 |
| F13 | Access Management — Grants & Sharing | [F13-access-management.md](modules/F13-access-management.md) | Complete | F05, F11 |
| F14 | Access Requests & Approval Workflow | [F14-access-requests.md](modules/F14-access-requests.md) | Complete | F13 |
| F15 | Secure External Sharing (Links) | [F15-secure-sharing.md](modules/F15-secure-sharing.md) | Complete | F13 |
| F16 | Secure Files | [F16-secure-files.md](modules/F16-secure-files.md) | Complete | F03, F10, F13 |
| F17 | Secure Notes | [F17-secure-notes.md](modules/F17-secure-notes.md) | Complete | F03, F10, F13 |
| F18 | Search & Discovery | [F18-search.md](modules/F18-search.md) | Complete | F04, F11, F16, F17 |
| F19 | Activity Logs | [F19-activity-logs.md](modules/F19-activity-logs.md) | Complete | F03, F10 |
| F20 | Security Alerts & Devices | [F20-security-alerts.md](modules/F20-security-alerts.md) | Complete | F03 |
| F21 | Admin — Tenant & Member Management | [F21-admin-tenant-management.md](modules/F21-admin-tenant-management.md) | Complete | F10, F12 |
| F22 | Admin — Employee Lifecycle & Offboarding | [F22-employee-lifecycle.md](modules/F22-employee-lifecycle.md) | Complete | F13, F21 |
| F23 | Settings — Profile, Password & 2FA | [F23-settings.md](modules/F23-settings.md) | Complete | F02 |
| F24 | Re-authentication Flow | [F24-reauthentication.md](modules/F24-reauthentication.md) | Complete | F02, F13 |
| F25 | Global Error Handling & Rate Limit UX | [F25-error-handling.md](modules/F25-error-handling.md) | Complete | F01 |
| F26 | Auto-lock & Session Management | [F26-auto-lock.md](modules/F26-auto-lock.md) | Complete | F02 |
| F27 | Responsive Design & Mobile | [F27-responsive.md](modules/F27-responsive.md) | Complete | All prior |
| F28 | Testing & QA | [F28-testing.md](modules/F28-testing.md) | Complete | All prior |
| F29 | Production Build & Deployment | [F29-deployment.md](modules/F29-deployment.md) | Not Started | F28 |

## Status Legend

| Status | Meaning |
|---|---|
| Not Started | Module has not been started |
| In Progress | Module is currently being developed |
| Review | Module is complete but awaiting verification |
| Complete | Module is done and tested |

## How to Use

1. Start with Module F01 and work downwards
2. Each module file contains: screens, components, hooks, endpoints, and acceptance criteria
3. Check off acceptance criteria as you complete them
4. Update the status column in this index when starting/completing a module
5. Do not skip ahead — later modules depend on earlier ones being complete

## Module Sizing

Each module is designed to be a **1-3 day unit of work**. Larger features (personal vault, shared vault, access management) are split across multiple modules to keep them manageable.

## API Coverage

These 29 modules cover **all 100+ API endpoints** in the Keyora backend. No endpoint is left unhandled. See `UI_ROADMAP.md` for the full endpoint-to-screen mapping.
