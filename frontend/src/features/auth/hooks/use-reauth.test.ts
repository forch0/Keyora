import { describe, it, expect, beforeEach } from 'vitest'
import { renderHook, waitFor } from '@testing-library/react'
import { useAuthStore } from '@/stores/auth-store'
import { createHookWrapper } from '@/test/utils'
import { useReauthStatus, useReauthenticate } from '@/features/auth/hooks/use-reauth'

describe('useReauthStatus', () => {
  beforeEach(() => {
    useAuthStore.getState().clear()
    useAuthStore.getState().setToken('test-token')
  })

  it('fetches reauth status', async () => {
    const { Wrapper } = createHookWrapper()
    const { result } = renderHook(() => useReauthStatus(), { wrapper: Wrapper })

    await waitFor(() => expect(result.current.isSuccess).toBe(true))
    expect(result.current.data?.data.reauth_required).toBe(false)
  })
})

describe('useReauthenticate', () => {
  beforeEach(() => {
    useAuthStore.getState().clear()
    useAuthStore.getState().setToken('test-token')
  })

  it('re-authenticates with correct password', async () => {
    const { Wrapper } = createHookWrapper()
    const { result } = renderHook(() => useReauthenticate(), { wrapper: Wrapper })

    result.current.mutate('password123')

    await waitFor(() => expect(result.current.isSuccess).toBe(true))
    expect(result.current.data?.data.reauthenticated).toBe(true)
  })
})
