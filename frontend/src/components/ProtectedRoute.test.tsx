import { describe, it, expect } from 'vitest'
import { screen } from '@testing-library/react'
import { renderWithProviders } from '@/test/utils'
import { ProtectedRoute } from '@/components/ProtectedRoute'
import { useAuthStore } from '@/stores/auth-store'

describe('ProtectedRoute', () => {
  it('redirects to /login when no token', () => {
    useAuthStore.getState().clear()
    renderWithProviders(
      <ProtectedRoute>
        <div>Protected Content</div>
      </ProtectedRoute>,
    )
    expect(screen.queryByText('Protected Content')).not.toBeInTheDocument()
  })

  it('renders children when authenticated', () => {
    useAuthStore.getState().setToken('test-token')
    useAuthStore.getState().setUser({
      id: 1,
      name: 'Test User',
      email: 'test@example.com',
      email_verified_at: null,
      two_factor_enabled: false,
      created_at: null,
      updated_at: null,
    })

    renderWithProviders(
      <ProtectedRoute>
        <div>Protected Content</div>
      </ProtectedRoute>,
    )
    expect(screen.getByText('Protected Content')).toBeInTheDocument()
  })
})
