# Zekura Frontend — Auth Flows

> Complete authentication state machine for the React SPA. Every flow, every endpoint, every edge case.

---

## 1. Auth state overview

The frontend auth state can be in one of these states:

```
unauthenticated → logging_in → requires_2fa → authenticated
                                              ↓
                                    reauth_required (sensitive actions)
                                              ↓
                                    reauthenticating → authenticated
```

### State definitions

| State | Meaning | UI behavior |
|---|---|---|
| `unauthenticated` | No session, or session expired | Show login page |
| `logging_in` | Login request in flight | Show loading spinner on login form |
| `requires_2fa` | User has 2FA enabled, needs to enter code | Show 2FA code input |
| `authenticated` | Valid session, can access app | Show main app |
| `reauth_required` | Sensitive action needs recent auth | Show re-auth modal (password prompt) |
| `reauthenticating` | Re-auth request in flight | Show loading on modal |

---

## 2. Login flow (with 2FA)

### Step 1: Get CSRF cookie

```
GET /sanctum/csrf-cookie
```

This sets an `XSRF-TOKEN` cookie. Read this cookie and send its value as the `X-CSRF-TOKEN` header on all subsequent non-GET requests.

**Frontend implementation:**
```ts
// Call this before login, and anytime the CSRF cookie expires
await fetch('/sanctum/csrf-cookie', { credentials: 'include' })
```

### Step 2: Submit credentials

```
POST /api/v1/auth/login
Content-Type: application/json
X-CSRF-TOKEN: <from cookie>

{
  "email": "user@company.com",
  "password": "secret"
}
```

**Response — 2FA not enabled (200):**
```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "user@company.com",
    "two_factor_enabled": false,
    ...
  },
  "token": "1|abcdef..."
}
```

> **Note**: The API returns a `token` in the response. With cookie-based SPA auth, you can ignore this token — the session cookie handles authentication. The token is for API token auth (mobile/CLI clients).

**Response — 2FA enabled (200):**
```json
{
  "data": {
    "requires_2fa": true,
    "2fa_token": "temp-token-string"
  }
}
```

**Response — invalid credentials (422):**
```json
{
  "error": {
    "code": "AUTH_INVALID_CREDENTIALS",
    "message": "Invalid email or password."
  }
}
```

### Step 3a: No 2FA — done

The user is authenticated. Fetch their profile:

```
GET /api/v1/auth/me
```

Proceed to the main app.

### Step 3b: 2FA required — verify code

```
POST /api/v1/auth/2fa/verify
Content-Type: application/json
X-CSRF-TOKEN: <from cookie>

{
  "2fa_token": "temp-token-string",
  "code": "123456"
}
```

Or use a recovery code instead of a TOTP code:

```json
{
  "2fa_token": "temp-token-string",
  "recovery_code": "recovery-code-here"
}
```

**Response — success (200):**
```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "user@company.com",
    "two_factor_enabled": true,
    ...
  },
  "token": "2|xyz123..."
}
```

**Response — invalid code (422):**
```json
{
  "error": {
    "code": "TWO_FACTOR_INVALID",
    "message": "Invalid 2FA token, code, or recovery code."
  }
}
```

### Step 4: Fetch profile

```
GET /api/v1/auth/me
```

Store the user profile in client state (Zustand). The user is now `authenticated`.

---

## 3. Registration flow

```
POST /api/v1/auth/register
Content-Type: application/json
X-CSRF-TOKEN: <from cookie>

{
  "name": "John Doe",
  "email": "user@company.com",
  "password": "securepassword",
  "password_confirmation": "securepassword"
}
```

**Response (201):**
```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "user@company.com"
  },
  "token": "1|abcdef..."
}
```

After registration, the user is authenticated. Fetch `/api/v1/auth/me` and proceed.

---

## 4. Logout flow

### Single device logout

```
POST /api/v1/auth/logout
X-CSRF-TOKEN: <from cookie>
```

Revokes the current session/token. Response: `204 No Content`.

Clear all client state (Zustand store, TanStack Query cache) and redirect to login.

### Logout all devices

```
POST /api/v1/auth/logout-all
X-CSRF-TOKEN: <from cookie>
```

Revokes all sessions/tokens for the user. Response: `204 No Content`.

---

## 5. Password reset flow

### Step 1: Request reset link

```
POST /api/v1/auth/forgot-password
Content-Type: application/json

{
  "email": "user@company.com"
}
```

**Response (200)** — always returns success (don't leak whether email exists):
```json
{
  "data": {
    "message": "If the email exists, a password reset link has been sent."
  }
}
```

The email contains a reset link with a token. The frontend should have a route like `/reset-password?token=xxx&email=xxx`.

### Step 2: Submit new password

```
POST /api/v1/auth/reset-password
Content-Type: application/json
X-CSRF-TOKEN: <from cookie>

{
  "email": "user@company.com",
  "token": "reset-token-from-email",
  "password": "newpassword",
  "password_confirmation": "newpassword"
}
```

**Response — success (200):**
```json
{
  "data": {
    "message": "Password has been reset successfully."
  }
}
```

**Response — failure (422):**
```json
{
  "error": {
    "code": "AUTH_PASSWORD_RESET_FAILED",
    "message": "The password reset failed.",
    "errors": {
      "token": ["Invalid token."]
    }
  }
}
```

---

## 6. Change password (authenticated)

```
POST /api/v1/auth/password
Content-Type: application/json
X-CSRF-TOKEN: <from cookie>

{
  "current_password": "oldpassword",
  "password": "newpassword",
  "password_confirmation": "newpassword"
}
```

**Response (200):** Success. **Response (422):** Current password incorrect.

---

## 7. Re-authentication flow (sensitive actions)

Some actions require recent authentication (within the last 15 minutes by default). These include:

- 2FA enable/disable
- Emergency revoke
- Offboarding employees
- Deleting vault items
- Creating secure links

### Check if re-auth is needed

```
GET /api/v1/auth/reauthenticate/status
```

**Response:**
```json
{
  "data": {
    "reauth_required": false
  }
}
```

### Handling 423 Locked

When performing a sensitive action, the API may return:

```
423 Locked
{
  "error": {
    "code": "REAUTHENTICATION_REQUIRED",
    "message": "Re-authentication required for this action."
  }
}
```

**Frontend behavior:**
1. Intercept `423` responses globally (axios interceptor or TanStack Query onError)
2. Show a re-authentication modal asking for the user's password
3. Submit re-auth:

```
POST /api/v1/auth/reauthenticate
Content-Type: application/json
X-CSRF-TOKEN: <from cookie>

{
  "password": "userpassword"
}
```

**Response — success (200):**
```json
{
  "data": {
    "reauthenticated": true
  }
}
```

**Response — invalid (422):**
```json
{
  "error": {
    "code": "AUTH_INVALID_PASSWORD",
    "message": "Current password is incorrect."
  }
}
```

4. After successful re-auth, retry the original request.

---

## 8. 2FA management (authenticated)

### Enable 2FA

```
POST /api/v1/auth/2fa/enable
X-CSRF-TOKEN: <from cookie>
```

**Response:**
```json
{
  "data": {
    "secret": "JBSWY3DPEHPK3PXP",
    "qr_code": "data:image/svg+xml;base64,..."
  }
}
```

Show the QR code to the user. They scan it with an authenticator app (Google Authenticator, Authy, etc.).

### Confirm 2FA

After scanning the QR code, the user enters their first TOTP code:

```
POST /api/v1/auth/2fa/confirm
Content-Type: application/json
X-CSRF-TOKEN: <from cookie>

{
  "code": "123456"
}
```

**Response (200):**
```json
{
  "data": {
    "recovery_codes": ["code1", "code2", ..., "code10"]
  }
}
```

Show the recovery codes to the user — they can only be viewed once.

### Disable 2FA

```
POST /api/v1/auth/2fa/disable
Content-Type: application/json
X-CSRF-TOKEN: <from cookie>

{
  "password": "userpassword"
}
```

### View recovery codes

```
GET /api/v1/auth/2fa/recovery-codes
```

Returns the existing recovery codes. (Requires re-authentication.)

---

## 9. Session expiry handling

### How sessions expire

- Session cookie expires after `SESSION_LIFETIME` minutes (default 120)
- Sanctum token expires based on `SANCTUM_TOKEN_EXPIRATION` config
- Server returns `401 Unauthorized` on expired session

### Frontend behavior

1. Intercept `401` responses globally
2. Clear auth state (Zustand store, TanStack Query cache)
3. Redirect to login page
4. Show "Your session has expired" message
5. Preserve the intended URL so after re-login the user returns to where they were

---

## 10. Auth state management (Zustand)

Recommended Zustand store shape:

```ts
interface AuthState {
  status: 'unauthenticated' | 'logging_in' | 'requires_2fa' | 'authenticated'
  user: User | null
  twoFactorToken: string | null
  selectedTenantId: number | null
  login: (email: string, password: string) => Promise<void>
  verify2fa: (code: string) => Promise<void>
  logout: () => Promise<void>
  setUser: (user: User) => void
  setTenant: (tenantId: number) => void
}
```

### TanStack Query integration

- Use `useQuery` for `/auth/me` to keep user profile fresh
- Use `useMutation` for login, 2FA verify, logout, re-authenticate
- Clear the query cache on logout: `queryClient.clear()`
- Set the `X-Tenant-ID` header globally via a custom fetcher or axios default
