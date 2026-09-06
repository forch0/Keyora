# Module F25 — Global Error Handling & Rate Limit UX

| Field | Value |
|---|---|
| **Module** | F25 |
| **Name** | Global Error Handling & Rate Limit UX |
| **Dependencies** | F01 |
| **Status** | Not Started |

---

## Objective

Implement comprehensive global error handling: 401 session expiry, 403 forbidden, 404 not found, 422 validation, 423 re-auth, 429 rate limit, 500 server error, and network errors. Provide good UX for each.

---

## Tasks

### F25.1 Error boundary

- [ ] Create `src/components/ErrorBoundary.tsx`
- [ ] Catches React render errors
- [ ] Shows fallback UI with "Something went wrong" + reload button
- [ ] Logs error to console (and optionally to API in future)

### F25.2 Global API error handler

- [ ] Update `src/api/client.ts` with comprehensive error handling:
  - 401 → clear auth, redirect to `/login`, toast "Session expired"
  - 403 → toast "You don't have permission to do this"
  - 404 → toast "Resource not found" (or render 404 page for routes)
  - 422 → return validation errors for form handling
  - 423 → trigger re-auth modal (Module F24)
  - 429 → toast "Too many requests. Try again in X seconds" (read `Retry-After`)
  - 500 → toast "Something went wrong. Please try again."
  - Network error → toast "Connection error. Check your network."

### F25.3 Rate limit UX

- [ ] Disable submit buttons during rate limit window
- [ ] Show countdown timer on rate-limited forms
- [ ] Read `Retry-After` header for exact wait time
- [ ] Global rate limit banner if multiple endpoints are limited

### F25.4 Toast notifications

- [ ] Configure shadcn/ui toast (sonner or toast)
- [ ] Standard toast types: success, error, warning, info
- [ ] Auto-dismiss after 5 seconds (errors stay longer)
- [ ] Action toasts (e.g., "Undo" for delete operations)

### F25.5 404 page

- [ ] Create `src/pages/NotFoundPage.tsx`
- [ ] Friendly 404 with link back to dashboard
- [ ] Used for both route 404s and resource 404s

### F25.6 Loading states

- [ ] Create `src/components/shared/PageLoader.tsx` — full-page spinner
- [ ] Create `src/components/shared/SkeletonList.tsx` — skeleton for list pages
- [ ] Create `src/components/shared/SkeletonCard.tsx` — skeleton for card layouts
- [ ] Use consistently across all pages

---

## API Endpoints Used

None directly — this module handles errors from all other modules' API calls.

---

## Acceptance Criteria

- [ ] 401 errors redirect to login with "Session expired" message
- [ ] 403 errors show permission denied toast
- [ ] 404 routes show 404 page
- [ ] 422 errors populate form fields with validation messages
- [ ] 423 errors trigger re-auth modal
- [ ] 429 errors show rate limit message with retry countdown
- [ ] 500 errors show generic error toast
- [ ] Network errors show connection error toast
- [ ] React render errors caught by error boundary
- [ ] Loading states show skeletons/spinners consistently

---

## What This Module Does NOT Include

- Re-auth modal implementation (Module F24)
- Specific page error handling (handled per-module)
