# Module F26 — Auto-lock & Session Management

| Field | Value |
|---|---|
| **Module** | F26 |
| **Name** | Auto-lock & Session Management |
| **Dependencies** | F02 |
| **Status** | Not Started |

---

## Objective

Implement auto-lock after inactivity, session expiry detection, and clipboard auto-clear. These are security-critical for a password manager.

---

## Tasks

### F26.1 Auto-lock timer

- [ ] Track user activity (mouse, keyboard, scroll)
- [ ] After 15 minutes of inactivity (configurable), lock the app
- [ ] Show lock screen: "Your session has been locked due to inactivity"
- [ ] Require password re-entry to unlock (use re-auth endpoint)
- [ ] Configurable timeout in settings (5, 15, 30, 60 minutes)

### F26.2 Lock screen

- [ ] Create `src/components/LockScreen.tsx`
- [ ] Shows when auto-locked or manually locked
- [ ] Password input to unlock
- [ ] POST `/api/v1/auth/reauthenticate` to verify password
- [ ] On success → unlock app
- [ ] "Logout" link as alternative

### F26.3 Manual lock

- [ ] "Lock" button in user menu (navbar)
- [ ] Immediately shows lock screen
- [ ] Quick access keyboard shortcut (Cmd/Ctrl+L)

### F26.4 Clipboard auto-clear

- [ ] Track clipboard copies (from `CopyButton` component)
- [ ] After 30 seconds, clear the clipboard
- [ ] Show toast: "Clipboard cleared for security"
- [ ] Only clears if the clipboard still contains the copied value

### F26.5 Session expiry detection

- [ ] Intercept 401 responses globally (from Module F25)
- [ ] Show "Your session has expired" message
- [ ] Redirect to login
- [ ] Preserve intended URL for post-login redirect

### F26.6 Activity tracking

- [ ] Create `src/hooks/useActivityTracker.ts`
- [ ] Listen to: mousemove, keydown, scroll, touchstart
- [ ] Reset inactivity timer on activity
- [ ] Throttle events (don't reset more than once per 10 seconds)

---

## API Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `POST` | `/api/v1/auth/reauthenticate` | Unlock with password |
| `GET` | `/api/v1/auth/reauthenticate/status` | Check auth status |

---

## Acceptance Criteria

- [ ] App auto-locks after configurable inactivity period (default 15 min)
- [ ] Lock screen requires password to unlock
- [ ] User can manually lock via menu or keyboard shortcut
- [ ] Clipboard auto-clears 30 seconds after copying a secret
- [ ] Session expiry (401) shows message and redirects to login
- [ ] Activity tracking resets the lock timer

---

## What This Module Does NOT Include

- Re-auth for sensitive actions (Module F24)
- Device management (Module F20)
