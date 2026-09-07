import { describe, it, expect, beforeEach } from 'vitest'
import { renderHook, waitFor } from '@testing-library/react'
import { useAuthStore } from '@/stores/auth-store'
import { createHookWrapper } from '@/test/utils'
import { useProfile } from '@/features/settings/hooks/use-settings'

describe('useProfile', () => {
  beforeEach(() => {
    useAuthStore.getState().clear()
    useAuthStore.getState().setToken('test-token')
  })

  it('fetches the current user profile', async () => {
    const { Wrapper } = createHookWrapper()
    const { result } = renderHook(() => useProfile(), { wrapper: Wrapper })

    await waitFor(() => expect(result.current.isSuccess).toBe(true))
    expect(result.current.data?.email).toBe('test@example.com')
    expect(result.current.data?.name).toBe('Test User')
  })
})
