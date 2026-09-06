# Module F20 — Security Alerts & Devices

| Field | Value |
|---|---|
| **Module** | F20 |
| **Name** | Security Alerts & Devices |
| **Dependencies** | F03 |
| **Status** | Not Started |

---

## Objective

Build the security alerts dropdown (navbar bell), alerts page, and device/session management.

---

## Tasks

### F20.1 Security alerts bell

- [ ] Add bell icon to navbar
- [ ] GET `/api/v1/security-alerts/unread-count` — badge with count
- [ ] Dropdown shows recent unread alerts
- [ ] "View all" link → alerts page
- [ ] "Mark all as read" button: POST `/api/v1/security-alerts/read-all`

### F20.2 Alerts page

- [ ] Route: `/settings/alerts` or `/security-alerts`
- [ ] GET `/api/v1/security-alerts` (paginated)
- [ ] List: type, severity, title, message, timestamp, read/unread
- [ ] Mark as read: POST `/api/v1/security-alerts/{alert}/read`
- [ ] Dismiss: POST `/api/v1/security-alerts/{alert}/dismiss`
- [ ] Filter by severity (info, warning, critical)

### F20.3 Device management

- [ ] Route: `/settings/devices`
- [ ] GET `/api/v1/devices`
- [ ] List: device name, browser, OS, IP, last active, current device indicator
- [ ] Revoke device: DELETE `/api/v1/devices/{device}` with confirmation
- [ ] "This will log out that device" warning

### F20.4 Hooks

- [ ] `useSecurityAlerts(params)` — query
- [ ] `useUnreadAlertCount()` — query (polled every 60s)
- [ ] `useMarkAlertRead()` — mutation
- [ ] `useDismissAlert()` — mutation
- [ ] `useMarkAllAlertsRead()` — mutation
- [ ] `useDevices()` — query
- [ ] `useRevokeDevice()` — mutation

---

## API Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/v1/security-alerts` | List alerts |
| `GET` | `/api/v1/security-alerts/unread-count` | Unread count |
| `POST` | `/api/v1/security-alerts/{alert}/read` | Mark read |
| `POST` | `/api/v1/security-alerts/{alert}/dismiss` | Dismiss |
| `POST` | `/api/v1/security-alerts/read-all` | Mark all read |
| `GET` | `/api/v1/devices` | List devices |
| `DELETE` | `/api/v1/devices/{device}` | Revoke device |

---

## Acceptance Criteria

- [ ] Navbar bell shows unread alert count badge
- [ ] Dropdown shows recent alerts
- [ ] Alerts page lists all alerts with severity indicators
- [ ] User can mark alerts as read and dismiss them
- [ ] User can view and revoke active devices/sessions
- [ ] Unread count polls periodically to stay fresh

---

## What This Module Does NOT Include

- Activity logs (Module F19)
- 2FA settings (Module F23)
