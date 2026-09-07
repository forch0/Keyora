import { describe, it, expect, beforeEach } from 'vitest'
import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { renderWithProviders } from '@/test/utils'
import { useAuthStore } from '@/stores/auth-store'
import { LoginPage } from '@/features/auth/pages/LoginPage'

describe('Login flow', () => {
  beforeEach(() => {
    useAuthStore.getState().clear()
  })

  it('renders login form', () => {
    renderWithProviders(<LoginPage />)
    expect(screen.getByText('Sign in to Keyora')).toBeInTheDocument()
    expect(screen.getByLabelText(/email/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/password/i)).toBeInTheDocument()
  })

  it('shows validation error for empty fields', async () => {
    const user = userEvent.setup()
    renderWithProviders(<LoginPage />)

    const submitButton = screen.getByRole('button', { name: /sign in/i })
    await user.click(submitButton)

    // HTML5 validation should prevent submission
    expect(screen.getByText('Sign in to Keyora')).toBeInTheDocument()
  })

  it('logs in successfully with valid credentials', async () => {
    const user = userEvent.setup()
    renderWithProviders(<LoginPage />, { initialEntries: ['/login'] })

    await user.type(screen.getByLabelText(/email/i), 'test@example.com')
    await user.type(screen.getByLabelText(/password/i), 'password123')
    await user.click(screen.getByRole('button', { name: /sign in/i }))

    await waitFor(() => {
      expect(useAuthStore.getState().token).toBe('test-token-123')
    })
    expect(useAuthStore.getState().user?.email).toBe('test@example.com')
  })

  it('shows error for invalid credentials', async () => {
    const user = userEvent.setup()
    renderWithProviders(<LoginPage />)

    await user.type(screen.getByLabelText(/email/i), 'wrong@example.com')
    await user.type(screen.getByLabelText(/password/i), 'wrongpassword')
    await user.click(screen.getByRole('button', { name: /sign in/i }))

    await waitFor(() => {
      expect(screen.getByText(/invalid email or password/i)).toBeInTheDocument()
    })
    expect(useAuthStore.getState().token).toBeNull()
  })
})
