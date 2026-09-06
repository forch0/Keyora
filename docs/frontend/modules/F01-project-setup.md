# Module F01 — Project Setup & Foundation

| Field | Value |
|---|---|
| **Module** | F01 |
| **Name** | Project Setup & Foundation |
| **Dependencies** | — |
| **Status** | Complete |

---

## Objective

Scaffold the React SPA project with Vite, TypeScript, Tailwind, shadcn/ui, TanStack Query, Zustand, and React Router. Configure the Vite dev proxy, path aliases, linting, and the API client wrapper. This module produces no visible UI — it's the foundation every other module builds on.

---

## Tasks

### F01.1 Project scaffolding

- [ ] Create `frontend/` directory at repo root
- [ ] Scaffold Vite + React + TypeScript project
- [ ] Install dependencies: `@tanstack/react-query`, `react-router-dom`, `zustand`, `tailwindcss`, `@tailwindcss/vite`
- [ ] Initialize shadcn/ui (`npx shadcn@latest init`)
- [ ] Add base shadcn/ui components: `button`, `input`, `label`, `dialog`, `toast`, `skeleton`, `dropdown-menu`, `select`, `table`, `tabs`, `form`
- [ ] Install dev dependencies: `eslint`, `prettier`, `vitest`, `@testing-library/react`, `openapi-typescript`

### F01.2 Configuration

- [ ] Configure Vite proxy (`vite.config.ts`):
  - `/api` → `http://localhost:8000`
  - `/sanctum` → `http://localhost:8000`
- [ ] Configure path alias `@/` → `src/` in `tsconfig.json` and `vite.config.ts`
- [ ] Configure ESLint + Prettier
- [ ] Configure Tailwind CSS v4
- [ ] Add npm scripts: `dev`, `build`, `preview`, `test`, `lint`, `format`, `gen:api`

### F01.3 API client

- [ ] Run `npm run gen:api` to generate TypeScript types from `http://localhost:8000/docs/api.json`
- [ ] Create `src/api/client.ts` — fetch wrapper with:
  - CSRF token injection (reads `XSRF-TOKEN` cookie, sends `X-CSRF-TOKEN` header)
  - `X-Tenant-ID` header injection (from Zustand store)
  - `Accept: application/json` header
  - 401 → clear auth state, redirect to `/login`
  - 423 → trigger re-auth modal (event emitter)
  - 429 → show rate limit toast
  - Error response normalization (extract `error.code`, `error.message`, `error.errors`)
- [ ] Create `src/api/csrf.ts` — helper to fetch `/sanctum/csrf-cookie`

### F01.4 Global providers

- [ ] Create `src/App.tsx` with providers:
  - `QueryClientProvider` (TanStack Query)
  - `BrowserRouter` (React Router)
  - `Toaster` (shadcn/ui toast)
- [ ] Configure `QueryClient` defaults: `staleTime: 30_000`, `retry: 1`

### F01.5 Folder structure

- [ ] Create the folder structure defined in `CONVENTIONS.md`:
  - `src/api/`, `src/components/`, `src/features/`, `src/hooks/`, `src/lib/`, `src/routes/`, `src/stores/`, `src/types/`

### F01.6 Environment

- [ ] Create `.env.example` with `VITE_API_URL=`
- [ ] Create `.gitignore` (node_modules, dist, .env.local)

---

## Acceptance Criteria

- [ ] `npm run dev` starts the Vite dev server on `localhost:3000`
- [ ] Vite proxy forwards `/api` requests to `localhost:8000`
- [ ] `curl localhost:3000/api/v1/health` returns `{"status":"ok"}`
- [ ] `npm run gen:api` generates `src/api/generated.ts` from the API spec
- [ ] `npm run build` produces a production build with no TypeScript errors
- [ ] `npm run lint` passes with no errors
- [ ] Path alias `@/` resolves to `src/`
- [ ] shadcn/ui components are installed and render correctly

---

## What This Module Does NOT Include

- Any visible UI or pages (Module F02+)
- Authentication logic (Module F02)
- Routing with protected routes (Module F02, F03)
