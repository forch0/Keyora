# Module F24 — Re-authentication Flow

| Field | Value |
|---|---|
| **Module** | F24 |
| **Name** | Re-authentication Flow |
| **Dependencies** | F02, F13 |
| **Status** | Complete |

---

## Objective

Build the global re-authentication modal that intercepts 423 Locked responses for sensitive actions. Users must re-enter their password to proceed.

---

## Tasks

### F24.1 Re-auth modal component

- [ ] Create `src/components/ReauthModal.tsx`
- [ ] Triggered by a global event emitter when 423 is received
- [ ] Password input field
- [ ] "Re-authenticate" button
- [ ] "Cancel" button
- [ ] Loading state during submission
- [ ] Error display for wrong password

### F24.2 Global 423 interceptor

- [ ] Update `src/api/client.ts`:
  - On 423 response, store the failed request
  - Emit re-auth-required event
  - Show `ReauthModal`
  - On successful re-auth, retry the original request
  - On cancel, reject the original request

### F24.3 Re-auth status check

- [ ] GET `/api/v1/auth/reauthenticate/status`
- [ ] Can be called before sensitive actions to pre-emptively check
- [ ] If `reauth_required: true`, show modal before making the request

### F24.4 Re-auth mutation

- [ ] POST `/api/v1/auth/reauthenticate` with password
- [ ] On success: close modal, retry queued request
- [ ] On failure: show error, keep modal open

### F24.5 Hooks

- [ ] `useReauthStatus()` — query: GET `/api/v1/auth/reauthenticate/status`
- [ ] `useReauthenticate()` — mutation: POST `/api/v1/auth/reauthenticate`

---

## API Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/v1/auth/reauthenticate/status` | Check if needed |
| `POST` | `/api/v1/auth/reauthenticate` | Re-authenticate |

---

## Acceptance Criteria

- [ ] 423 Locked responses trigger the re-auth modal globally
- [ ] User enters password and the original request is retried automatically
- [ ] Wrong password shows error and keeps modal open
- [ ] Cancelling the modal rejects the original action
- [ ] Sensitive actions (2FA disable, emergency revoke, offboard, delete) trigger re-auth
- [ ] Re-auth status can be checked pre-emptively

---

## What This Module Does NOT Include

- Login flow (Module F02)
- 2FA verification (Module F02)
