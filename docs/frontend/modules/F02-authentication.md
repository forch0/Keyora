# Module F02 — Authentication & 2FA

| Field | Value |
|---|---|
| **Module** | F02 |
| **Name** | Authentication & 2FA |
| **Dependencies** | F01 |
| **Status** | Not Started |

---

## Objective

Implement the complete authentication flow: login, 2FA verification, registration, password reset, and logout. This module produces the auth Zustand store, auth hooks, and the login/register/2FA/reset pages.

---

## Tasks

### F02.1 Auth Zustand store

- [ ] Create `src/stores/auth-store.ts`:
  - State: `status` (`unauthenticated` | `logging_in` | `requires_2fa` | `authenticated`), `user`, `twoFactorToken`, `selectedTenantId`
  - Actions: `login()`, `verify2fa()`, `logout()`, `setUser()`, `setTenant()`, `clear()`
- [ ] On `authenticated`, fetch `/api/v1/auth/me` and store user
- [ ] On `logout`, clear store + TanStack Query cache

### F02.2 Auth hooks

- [ ] `useLogin()` — mutation: POST `/api/v1/auth/login`, handle 2FA challenge response
- [ ] `useVerify2fa()` — mutation: POST `/api/v1/auth/2fa/verify`
- [ ] `useRegister()` — mutation: POST `/api/v1/auth/register`
- [ ] `useLogout()` — mutation: POST `/api/v1/auth/logout`
- [ ] `useForgotPassword()` — mutation: POST `/api/v1/auth/forgot-password`
- [ ] `useResetPassword()` — mutation: POST `/api/v1/auth/reset-password`
- [ ] `useCurrentUser()` — query: GET `/api/v1/auth/me`

### F02.3 Login page

- [ ] Route: `/login`
- [ ] Email + password form (React Hook Form + Zod)
- [ ] "Forgot password?" link
- [ ] Loading state during submit
- [ ] Error display for invalid credentials (422)
- [ ] Rate limit error display (429)
- [ ] On success without 2FA → redirect to `/`
- [ ] On success with 2FA → show 2FA verify step

### F02.4 2FA verify step

- [ ] 6-digit TOTP code input (auto-advance, paste support)
- [ ] "Use recovery code" toggle
- [ ] Recovery code input field
- [ ] Submit → POST `/api/v1/auth/2fa/verify`
- [ ] Error display for invalid code (422)
- [ ] On success → redirect to `/`
- [ ] "Back to login" link

### F02.5 Register page

- [ ] Route: `/register`
- [ ] Name, email, password, confirm password form
- [ ] Inline validation errors
- [ ] On success → redirect to `/`

### F02.6 Forgot password page

- [ ] Route: `/forgot-password`
- [ ] Email input
- [ ] Submit → always show success message (don't leak if email exists)
- [ ] Link back to login

### F02.7 Reset password page

- [ ] Route: `/reset-password` (reads `token` + `email` from query params)
- [ ] New password + confirm password
- [ ] Submit → POST `/api/v1/auth/reset-password`
- [ ] On success → redirect to `/login` with success message
- [ ] On failure → show error (invalid/expired token)

### F02.8 CSRF helper

- [ ] Create `src/api/csrf.ts`:
  - `fetchCsrfToken()` — GET `/sanctum/csrf-cookie`
  - Called before login and register

### F02.9 Protected route guard

- [ ] Create `src/components/ProtectedRoute.tsx`
- [ ] If `status !== 'authenticated'` → `<Navigate to="/login" />`
- [ ] Preserve intended URL for post-login redirect

---

## API Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/sanctum/csrf-cookie` | Fetch CSRF token |
| `POST` | `/api/v1/auth/login` | Login |
| `POST` | `/api/v1/auth/2fa/verify` | Verify 2FA code |
| `POST` | `/api/v1/auth/register` | Register |
| `POST` | `/api/v1/auth/logout` | Logout |
| `GET` | `/api/v1/auth/me` | Fetch current user |
| `POST` | `/api/v1/auth/forgot-password` | Request reset link |
| `POST` | `/api/v1/auth/reset-password` | Reset password |

---

## Acceptance Criteria

- [ ] User can log in with email + password
- [ ] User with 2FA sees the 2FA code input after login
- [ ] User can enter TOTP code or recovery code to complete 2FA
- [ ] Invalid credentials show error message
- [ ] User can register a new account
- [ ] User can request a password reset link
- [ ] User can reset password with token from email
- [ ] User can log out
- [ ] Unauthenticated users are redirected to `/login`
- [ ] After login, user is redirected to their intended page (or `/`)
- [ ] Rate-limited login attempts show a "too many attempts" message
- [ ] Auth state persists across page refreshes (cookie-based session)

---

## What This Module Does NOT Include

- App shell / navigation (Module F03)
- Profile settings / change password (Module F23)
- 2FA management (enable/disable) (Module F23)
- Re-authentication for sensitive actions (Module F24)
- Auto-lock / session expiry (Module F26)
