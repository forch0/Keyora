# Module F10 — Tenant Context & Workspace Switcher

| Field | Value |
|---|---|
| **Module** | F10 |
| **Name** | Tenant Context & Workspace Switcher |
| **Dependencies** | F02, F03 |
| **Status** | Not Started |

---

## Objective

Build the workspace switcher that lets users switch between tenants. This sets the `X-Tenant-ID` header globally for all subsequent API requests. Required before any tenant-scoped feature (shared vault, teams, files, notes, admin).

---

## Tasks

### F10.1 Tenant switcher component

- [ ] Create `src/components/layout/TenantSwitcher.tsx`
- [ ] Fetch tenants: GET `/api/v1/tenants`
- [ ] Dropdown showing all workspaces the user belongs to
- [ ] Show tenant name + role
- [ ] Selecting a tenant updates `selectedTenantId` in auth store
- [ ] Display current tenant in navbar

### F10.2 Global header injection

- [ ] Update `src/api/client.ts` to read `selectedTenantId` from auth store
- [ ] Send `X-Tenant-ID: {id}` header on all requests when a tenant is selected
- [ ] Skip header for auth and personal vault endpoints (not tenant-scoped)

### F10.3 Tenant onboarding

- [ ] If user has no tenants, show "Create or join a workspace" prompt
- [ ] Create tenant form: POST `/api/v1/tenants`
- [ ] Accept invitation flow (if applicable)

### F10.4 Auto-select tenant

- [ ] On login, if user has tenants, auto-select the first one
- [ ] Persist selected tenant in localStorage (survives page refresh)
- [ ] If selected tenant is no longer accessible, clear and prompt re-select

### F10.5 Hooks

- [ ] `useTenants()` — query: GET `/api/v1/tenants`
- [ ] `useCreateTenant()` — mutation: POST `/api/v1/tenants`
- [ ] `useTenant(id)` — query: GET `/api/v1/tenants/{tenant}`

---

## API Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/v1/tenants` | List user's tenants |
| `POST` | `/api/v1/tenants` | Create tenant |
| `GET` | `/api/v1/tenants/{tenant}` | Get tenant details |

---

## Acceptance Criteria

- [ ] User can see all workspaces they belong to
- [ ] User can switch between workspaces
- [ ] All subsequent API requests include the correct `X-Tenant-ID` header
- [ ] Selected tenant persists across page refreshes
- [ ] User with no tenants is prompted to create one
- [ ] Personal vault endpoints work without tenant context

---

## What This Module Does NOT Include

- Tenant settings/management (Module F21)
- Team management (Module F12)
