import { type ReactNode } from 'react'
import { render, type RenderOptions } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { MemoryRouter } from 'react-router-dom'
import { useAuthStore } from '@/stores/auth-store'

/**
 * Create a fresh QueryClient for each test to avoid cache leakage.
 */
function createTestQueryClient() {
  return new QueryClient({
    defaultOptions: {
      queries: {
        retry: false,
        gcTime: 0,
        staleTime: 0,
        refetchOnWindowFocus: false,
      },
      mutations: {
        retry: false,
      },
    },
  })
}

interface RenderWithProvidersOptions extends Omit<RenderOptions, 'wrapper'> {
  initialEntries?: string[]
  authState?: Partial<{
    token: string | null
    user: ReturnType<typeof useAuthStore.getState>['user']
  }>
}

/**
 * Render a component wrapped with all required providers:
 * QueryClientProvider, MemoryRouter, and pre-configured auth store.
 */
export function renderWithProviders(
  ui: ReactNode,
  options: RenderWithProvidersOptions = {},
) {
  const { initialEntries = ['/'], authState } = options

  // Reset auth store
  useAuthStore.getState().clear()
  if (authState?.token) {
    useAuthStore.getState().setToken(authState.token)
  }
  if (authState?.user) {
    useAuthStore.getState().setUser(authState.user)
  }

  const queryClient = createTestQueryClient()

  function Wrapper({ children }: { children: ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={initialEntries}>{children}</MemoryRouter>
      </QueryClientProvider>
    )
  }

  return {
    ...render(ui, { wrapper: Wrapper }),
    queryClient,
  }
}

/**
 * Create a wrapper for renderHook that provides QueryClient + Router.
 */
export function createHookWrapper() {
  const queryClient = createTestQueryClient()

  function Wrapper({ children }: { children: ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        <MemoryRouter>{children}</MemoryRouter>
      </QueryClientProvider>
    )
  }

  return { Wrapper, queryClient }
}
