# Module F23 — Settings: Profile, Password & 2FA

| Field | Value |
|---|---|
| **Module** | F23 |
| **Name** | Settings — Profile, Password & 2FA |
| **Dependencies** | F02 |
| **Status** | Complete |

---

## Objective

Build the settings pages: profile editing, password change, and 2FA management (enable, confirm, view recovery codes, disable).

---

## Tasks

### F23.1 Profile settings

- [ ] Route: `/settings`
- [ ] GET `/api/v1/auth/me` — show current profile
- [ ] Update profile: PUT `/api/v1/auth/me` (name, email)
- [ ] Inline edit with save/cancel

### F23.2 Change password

- [ ] Section in settings page
- [ ] Fields: current password, new password, confirm new password
- [ ] POST `/api/v1/auth/password`
- [ ] Show success toast on change
- [ ] Password strength meter (Module F08)

### F23.3 2FA management

- [ ] Section in settings page
- [ ] If 2FA not enabled:
  - "Enable 2FA" button → POST `/api/v1/auth/2fa/enable`
  - Show QR code + secret
  - TOTP code input → POST `/api/v1/auth/2fa/confirm`
  - Show recovery codes (one-time view, download button)
- [ ] If 2FA enabled:
  - Show "2FA is enabled" status
  - "View recovery codes" → GET `/api/v1/auth/2fa/recovery-codes` (requires re-auth)
  - "Disable 2FA" → POST `/api/v1/auth/2fa/disable` (requires password)

### F23.4 Hooks

- [ ] `useUpdateProfile()` — mutation: PUT `/api/v1/auth/me`
- [ ] `useChangePassword()` — mutation: POST `/api/v1/auth/password`
- [ ] `useEnable2fa()` — mutation: POST `/api/v1/auth/2fa/enable`
- [ ] `useConfirm2fa()` — mutation: POST `/api/v1/auth/2fa/confirm`
- [ ] `useDisable2fa()` — mutation: POST `/api/v1/auth/2fa/disable`
- [ ] `useRecoveryCodes()` — query: GET `/api/v1/auth/2fa/recovery-codes`

---

## API Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/v1/auth/me` | View profile |
| `PUT` | `/api/v1/auth/me` | Update profile |
| `POST` | `/api/v1/auth/password` | Change password |
| `POST` | `/api/v1/auth/2fa/enable` | Enable 2FA |
| `POST` | `/api/v1/auth/2fa/confirm` | Confirm 2FA |
| `POST` | `/api/v1/auth/2fa/disable` | Disable 2FA |
| `GET` | `/api/v1/auth/2fa/recovery-codes` | View recovery codes |

---

## Acceptance Criteria

- [ ] User can update their name and email
- [ ] User can change their password with current password verification
- [ ] User can enable 2FA by scanning QR code and entering first TOTP code
- [ ] Recovery codes are shown once after enabling and can be downloaded
- [ ] User can view recovery codes again (requires re-auth)
- [ ] User can disable 2FA (requires password)
- [ ] Profile updates reflect immediately in navbar

---

## What This Module Does NOT Include

- Re-authentication modal (Module F24)
- Device management (Module F20)
- Security alerts (Module F20)
