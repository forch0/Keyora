# Keyora Frontend — Conventions & Standards

> Coding standards, project structure, and tooling for the React SPA.

---

## 1. Tech stack

| Layer | Choice | Why |
|---|---|---|
| Build tool | Vite | Fast HMR, modern, zero-config |
| Framework | React 19 + TypeScript | Type safety, ecosystem maturity |
| Routing | React Router v7 | De facto standard, nested routes |
| Server state | TanStack Query v5 | Caching, invalidation, optimistic updates |
| Client state | Zustand | Lightweight, no boilerplate, good for auth state |
| Styling | Tailwind CSS v4 | Utility-first, fast iteration, consistent design |
| UI components | shadcn/ui | Accessible (Radix UI), copy-paste, no lock-in |
| Forms | React Hook Form + Zod | Performant, schema validation |
| API types | openapi-typescript | Auto-generated from Scramble's `/docs/api.json` |
| Testing | Vitest + Testing Library | Fast, integrates with Vite |
| Linting | ESLint + Prettier | Consistent code style |

---

## 2. Project structure

```
frontend/
├── public/
├── src/
│   ├── api/                    # Auto-generated types + manual API wrappers
│   │   ├── generated.ts        # ← from openapi-typescript (do not edit)
│   │   └── client.ts           # Fetch wrapper, interceptors, headers
│   ├── components/
│   │   ├── ui/                 # shadcn/ui components (Button, Dialog, etc.)
│   │   ├── forms/              # Form components (LoginForm, VaultItemForm, etc.)
│   │   ├── layout/             # AppLayout, Sidebar, Navbar, etc.
│   │   └── shared/             # Reusable bits (CopyButton, EmptyState, etc.)
│   ├── features/               # Feature-based modules
│   │   ├── auth/
│   │   │   ├── stores/         # Zustand auth store
│   │   │   ├── hooks/          # useLogin, useLogout, use2fa
│   │   │   └── pages/          # LoginPage, RegisterPage, etc.
│   │   ├── vault/
│   │   │   ├── hooks/          # useVaultItems, useVaultItem, etc.
│   │   │   ├── pages/          # VaultListPage, VaultItemDetailPage
│   │   │   └── components/     # VaultItemCard, FolderTree, etc.
│   │   ├── shared-vault/
│   │   ├── access/
│   │   ├── files/
│   │   ├── notes/
│   │   ├── dashboard/
│   │   ├── admin/
│   │   ├── tools/
│   │   └── settings/
│   ├── hooks/                  # Global hooks (useDebounce, useCopyToClipboard)
│   ├── lib/                    # Utilities (cn, formatters, constants)
│   ├── routes/                 # React Router route definitions
│   ├── stores/                 # Global Zustand stores (auth, tenant)
│   ├── types/                  # Shared TypeScript types
│   ├── App.tsx
│   └── main.tsx
├── tests/                      # Test files (mirror src/ structure)
├── .eslintrc.cjs
├── .prettierrc
├── index.html
├── package.json
├── tsconfig.json
├── vite.config.ts
└── tailwind.config.ts
```

### Why feature-based, not layer-based

Each feature is self-contained: its hooks, pages, and components live together. This makes it easy to find everything related to a feature and avoids a sprawling `components/` folder with 100 unrelated files.

---

## 3. API client generation

### Generate types from OpenAPI

```bash
npx openapi-typescript http://localhost:8000/docs/api.json -o src/api/generated.ts
```

Add this as an npm script:

```json
{
  "scripts": {
    "gen:api": "openapi-typescript http://localhost:8000/docs/api.json -o src/api/generated.ts"
  }
}
```

Run `npm run gen:api` whenever the API changes. The generated file provides TypeScript types for all request/response shapes.

### Using generated types

```ts
import type { paths } from '@/api/generated'

// Type for a vault item response
type VaultItem = paths['/api/v1/vault/items/{item}']['get']['responses']['200']['content']['application/json']['data']

// Type for creating a vault item
type CreateVaultItemBody = paths['/api/v1/vault/items']['post']['requestBody']['content']['application/json']
```

### API client wrapper

Create a thin fetch wrapper that handles:
- CSRF token header injection
- `X-Tenant-ID` header injection
- 401 → logout redirect
- 423 → re-auth modal trigger
- 429 → rate limit message
- Error response normalization

```ts
// src/api/client.ts
const api = {
  async get<T>(path: string, params?: Record<string, unknown>): Promise<T> { ... },
  async post<T>(path: string, body?: unknown): Promise<T> { ... },
  async put<T>(path: string, body?: unknown): Promise<T> { ... },
  async delete(path: string): Promise<void> { ... },
}
```

---

## 4. State management

### TanStack Query (server state)

All data from the API is managed by TanStack Query. No server data in Zustand.

**Conventions:**
- Query keys: `['vault-items', { page, filter, search }]` — be specific
- Mutation invalidation: after a successful mutation, invalidate related queries
- Optimistic updates for simple toggles (favorite, archive)
- `staleTime: 30_000` default (30 seconds) — the API has rate limits

```ts
// Example hook
function useVaultItems(params: VaultItemListParams) {
  return useQuery({
    queryKey: ['vault-items', params],
    queryFn: () => api.get('/api/v1/vault/items', params),
  })
}

function useCreateVaultItem() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (body: CreateVaultItemBody) => api.post('/api/v1/vault/items', body),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['vault-items'] })
    },
  })
}
```

### Zustand (client state)

Only use Zustand for:
- **Auth state** (user, status, selected tenant)
- **UI state** (sidebar open/closed, theme)

```ts
// stores/auth-store.ts
interface AuthStore {
  status: AuthStatus
  user: User | null
  selectedTenantId: number | null
  // actions...
}
```

---

## 5. Component conventions

### shadcn/ui

Install components via CLI:

```bash
npx shadcn@latest init
npx shadcn@latest add button dialog input form select table toast
```

Components live in `src/components/ui/`. They're copy-paste — own them, modify them as needed.

### Naming

| Type | Convention | Example |
|---|---|---|
| Components | PascalCase | `VaultItemCard.tsx` |
| Hooks | camelCase, `use` prefix | `useVaultItems.ts` |
| Stores | camelCase, `Store` suffix | `auth-store.ts` |
| Utils | camelCase | `formatDate.ts` |
| Types | PascalCase | `VaultItem.ts` |
| Pages | PascalCase, `Page` suffix | `VaultListPage.tsx` |
| Routes | kebab-case URLs | `/vault/items/:id` |

### File naming

- Components: `PascalCase.tsx`
- Hooks: `camelCase.ts`
- Stores: `camelCase.ts`
- Tests: `*.test.ts` or `*.test.tsx` next to the source file

### Component structure

```tsx
// 1. Imports
import { useVaultItem } from '@/features/vault/hooks/useVaultItem'

// 2. Props interface
interface VaultItemCardProps {
  itemId: number
  onClick?: (id: number) => void
}

// 3. Component
export function VaultItemCard({ itemId, onClick }: VaultItemCardProps) {
  const { data: item, isLoading } = useVaultItem(itemId)

  if (isLoading) return <Skeleton />
  if (!item) return null

  return ( ... )
}

// 4. Sub-components (if any) below
```

---

## 6. Routing

### Route structure

```tsx
// routes/index.tsx
const router = createBrowserRouter([
  {
    path: '/login',
    element: <LoginPage />,
  },
  {
    path: '/register',
    element: <RegisterPage />,
  },
  {
    element: <ProtectedRoute />,
    children: [
      {
        element: <AppLayout />,
        children: [
          { path: '/', element: <DashboardPage /> },
          { path: '/vault', element: <VaultListPage /> },
          { path: '/vault/items/:id', element: <VaultItemDetailPage /> },
          { path: '/vault/trash', element: <TrashPage /> },
          { path: '/shared', element: <SharedVaultPage /> },
          { path: '/files', element: <FileListPage /> },
          { path: '/notes', element: <NoteListPage /> },
          { path: '/admin', element: <AdminPage /> },
          { path: '/settings', element: <SettingsPage /> },
        ],
      },
    ],
  },
])
```

### Protected routes

```tsx
function ProtectedRoute() {
  const { status } = useAuthStore()
  if (status !== 'authenticated') return <Navigate to="/login" />
  return <Outlet />
}
```

---

## 7. Error handling

### Global error interceptor

The API client wrapper handles these globally:

| Status | Behavior |
|---|---|
| 401 | Clear auth state, redirect to `/login`, show "Session expired" toast |
| 403 | Show "You don't have permission" toast |
| 423 | Trigger re-auth modal |
| 429 | Show "Rate limited, try again in X seconds" toast |
| 500 | Show "Something went wrong" toast, log to console |
| Network error | Show "Connection error" toast |

### Form validation errors (422)

```ts
// In mutation onError
onError: (error) => {
  if (error.code === 'VALIDATION_ERROR' && error.errors) {
    // Map API field errors to React Hook Form
    Object.entries(error.errors).forEach(([field, messages]) => {
      form.setError(field, { message: messages[0] })
    })
  }
}
```

---

## 8. Testing

### What to test

- **Unit**: utility functions, formatters, hooks (with `renderHook`)
- **Component**: component rendering, user interactions (Testing Library)
- **Integration**: page-level tests with mocked API (MSW)
- **E2E** (optional): critical flows with Playwright (login, create vault item, share)

### Test conventions

```ts
// Co-located with source: src/features/vault/hooks/useVaultItem.test.ts
import { renderHook } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

describe('useVaultItem', () => {
  it('fetches vault item by id', async () => {
    // Arrange, Act, Assert
  })
})
```

---

## 9. Linting & formatting

### ESLint config

```json
{
  "extends": ["eslint:recommended", "plugin:@typescript-eslint/recommended", "plugin:react/recommended"],
  "rules": {
    "react/react-in-jsx-scope": "off",
    "@typescript-eslint/no-unused-vars": "warn"
  }
}
```

### Prettier config

```json
{
  "semi": false,
  "singleQuote": true,
  "tabWidth": 2,
  "trailingComma": "all",
  "printWidth": 100
}
```

### npm scripts

```json
{
  "scripts": {
    "dev": "vite",
    "build": "tsc && vite build",
    "preview": "vite preview",
    "test": "vitest",
    "lint": "eslint src --ext .ts,.tsx",
    "format": "prettier --write src",
    "gen:api": "openapi-typescript http://localhost:8000/docs/api.json -o src/api/generated.ts"
  }
}
```

---

## 10. Path aliases

In `tsconfig.json` and `vite.config.ts`:

```json
{
  "compilerOptions": {
    "paths": {
      "@/*": ["./src/*"]
    }
  }
}
```

```ts
// vite.config.ts
resolve: {
  alias: { '@': path.resolve(__dirname, './src') }
}
```

Use `@/` for all imports from `src/`:

```ts
import { useAuthStore } from '@/stores/auth-store'
import { Button } from '@/components/ui/button'
```

---

## 11. Environment variables

```env
# .env.local (not committed)
VITE_API_URL=http://localhost:8000
```

In development with the Vite proxy, this is empty (same origin). In production, also empty (Nginx reverse proxy). Only needed if the API is on a different origin.

```ts
const API_BASE = import.meta.env.VITE_API_URL ?? ''
```

---

## 12. Accessibility

Keyora is an internal tool but accessibility still matters:

- All interactive elements must be keyboard-accessible
- Forms must have proper labels (`<label>` or `aria-label`)
- Modals must trap focus and close on Escape
- Color contrast must meet WCAG AA
- Use semantic HTML (`<nav>`, `<main>`, `<aside>`, `<button>`)
- shadcn/ui components are accessible by default (Radix UI)

---

## 13. Security considerations for the frontend

- **Never store decrypted secrets in localStorage** — use in-memory state only
- **Clear clipboard** after 30 seconds when copying passwords
- **Auto-lock** the app after 15 minutes of inactivity (configurable)
- **No sensitive data in URL** — don't put passwords in query params or route state
- **Sanitize any user-generated HTML** (notes, custom fields) before rendering
- **Content-Security-Policy** header in production Nginx config
