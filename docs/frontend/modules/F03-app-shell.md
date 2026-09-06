# Module F03 — App Shell & Navigation

| Field | Value |
|---|---|
| **Module** | F03 |
| **Name** | App Shell & Navigation |
| **Dependencies** | F02 |
| **Status** | Complete |

---

## Objective

Build the main application layout: sidebar navigation, top navbar, and the route structure for all authenticated pages. This is the skeleton that all feature modules plug into.

---

## Tasks

### F03.1 App layout

- [ ] Create `src/components/layout/AppLayout.tsx`:
  - Sidebar (collapsible) with navigation links
  - Top navbar with: logo, global search trigger, security alerts bell, user menu
  - Main content area (renders `<Outlet />`)
- [ ] Responsive: sidebar collapses to hamburger menu on mobile

### F03.2 Sidebar navigation

- [ ] Navigation items (some disabled until their module is built):
  - Dashboard (`/`)
  - Personal Vault (`/vault`)
  - Shared Vault (`/shared`)
  - Secure Files (`/files`)
  - Secure Notes (`/notes`)
  - Access Requests (`/access-requests`)
  - Activity Logs (`/activity`)
  - Admin (`/admin`)
  - Settings (`/settings`)
- [ ] Active route highlighting
- [ ] Collapsible sidebar (persisted in Zustand UI store)

### F03.3 Top navbar

- [ ] Logo / app name
- [ ] Global search button (opens search modal — Module F18)
- [ ] Security alerts bell with unread count badge (Module F20)
- [ ] User dropdown menu:
  - Profile name + email
  - Settings link
  - Logout button

### F03.4 Route structure

- [ ] Define all routes in `src/routes/index.tsx`:
  - Public: `/login`, `/register`, `/forgot-password`, `/reset-password`
  - Protected (wrapped in `<ProtectedRoute>` + `<AppLayout>`):
    - `/` → Dashboard (placeholder for now)
    - `/vault` → Personal Vault (placeholder)
    - `/vault/items/:id` → Vault Item Detail (placeholder)
    - `/vault/trash` → Trash (placeholder)
    - `/shared` → Shared Vault (placeholder)
    - `/files` → Secure Files (placeholder)
    - `/notes` → Secure Notes (placeholder)
    - `/access-requests` → Access Requests (placeholder)
    - `/activity` → Activity Logs (placeholder)
    - `/admin` → Admin (placeholder)
    - `/settings` → Settings (placeholder)
- [ ] 404 catch-all route

### F03.5 Placeholder pages

- [ ] Create simple placeholder components for each route that say "Coming soon — Module FXX"
- [ ] These get replaced as each module is built

### F03.6 User menu component

- [ ] Create `src/components/layout/UserMenu.tsx`
- [ ] Shows user avatar (initials), name, email
- [ ] Dropdown: Settings, Logout
- [ ] Logout calls `useLogout()` mutation

---

## API Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/v1/auth/me` | Display user info in navbar |
| `POST` | `/api/v1/auth/logout` | Logout from user menu |

---

## Acceptance Criteria

- [ ] Authenticated user sees the app shell with sidebar + navbar
- [ ] Sidebar shows all navigation items with correct icons
- [ ] Active route is highlighted in sidebar
- [ ] User menu shows user name + email and has logout
- [ ] Sidebar can be collapsed/expanded
- [ ] Mobile layout shows hamburger menu
- [ ] Navigating to each route shows its placeholder page
- [ ] Unknown routes show 404 page
- [ ] Logout redirects to `/login`

---

## What This Module Does NOT Include

- Dashboard content (Module F09)
- Vault list/detail (Module F04)
- Global search modal (Module F18)
- Security alerts dropdown (Module F20)
- Tenant switcher (Module F10)
