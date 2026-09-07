# Module F28 — Testing & QA

| Field | Value |
|---|---|
| **Module** | F28 |
| **Name** | Testing & QA |
| **Dependencies** | All prior |
| **Status** | Complete |

---

## Objective

Write tests for the frontend: unit tests for utilities/hooks, component tests for shared components, and integration tests for critical flows. Set up MSW for API mocking.

---

## Tasks

### F28.1 Test infrastructure

- [ ] Configure Vitest with jsdom environment
- [ ] Set up Testing Library (`@testing-library/react`, `@testing-library/jest-dom`, `@testing-library/user-event`)
- [ ] Configure MSW (Mock Service Worker) for API mocking
- [ ] Create test setup file (`src/test/setup.ts`)
- [ ] Create test utilities: `renderWithProviders()` (wraps with QueryClient + Router + stores)

### F28.2 Unit tests — utilities

- [ ] Test `src/lib/` utilities (formatters, helpers)
- [ ] Test `src/api/client.ts` error handling logic
- [ ] Test `src/api/csrf.ts`

### F28.3 Hook tests

- [ ] Test auth hooks: `useLogin`, `useLogout`, `useVerify2fa`
- [ ] Test vault hooks: `useVaultItems`, `useVaultItem`, `useCreateVaultItem`
- [ ] Test access hooks: `useGrantAccess`, `useRevokeGrant`
- [ ] Use `renderHook` with wrapper providers

### F28.4 Component tests

- [ ] Test `CopyButton` — copies to clipboard, shows "Copied!", auto-clears
- [ ] Test `PasswordField` — show/hide toggle, copy button
- [ ] Test `ProtectedRoute` — redirects unauthenticated users
- [ ] Test `ReauthModal` — shows on 423, submits password, retries request
- [ ] Test `EmptyState` — renders correctly

### F28.5 Integration tests — critical flows

- [ ] Login flow: enter credentials → submit → authenticated → redirected
- [ ] Login with 2FA: enter credentials → 2FA step → enter code → authenticated
- [ ] Create vault item: fill form → submit → item appears in list
- [ ] Grant access: select user → set permission → grant appears in list
- [ ] Revoke access: click revoke → confirmation → grant removed

### F28.6 E2E tests (optional)

- [ ] Set up Playwright
- [ ] Test: full login → create vault item → copy password → logout
- [ ] Test: admin invite member → member accepts → member creates item

---

## API Endpoints Used

None directly — all API calls are mocked with MSW.

---

## Acceptance Criteria

- [ ] `npm run test` runs all tests with no failures
- [ ] Unit tests cover utilities and hooks
- [ ] Component tests cover shared components
- [ ] Integration tests cover critical user flows
- [ ] MSW mocks all API endpoints used in tests
- [ ] Test coverage report shows > 70% for critical paths

---

## What This Module Does NOT Include

- Visual regression testing (can be added with Chromatic later)
- Performance testing (Module F29)
