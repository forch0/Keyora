import { useMutation, useQuery } from '@tanstack/react-query'
import { api } from '@/api/client'
import type { ApiError } from '@/types/api-error'

interface ReauthStatusResponse {
  data: {
    reauth_required: boolean
  }
}

interface ReauthenticateResponse {
  data: {
    reauthenticated: boolean
  }
}

export function useReauthStatus() {
  return useQuery<ReauthStatusResponse, ApiError>({
    queryKey: ['auth', 'reauth-status'],
    queryFn: () => api.get<ReauthStatusResponse>('/api/v1/auth/reauthenticate/status'),
    staleTime: 0,
  })
}

export function useReauthenticate() {
  return useMutation<ReauthenticateResponse, ApiError, string>({
    mutationFn: (password) =>
      api.post<ReauthenticateResponse>('/api/v1/auth/reauthenticate', { password }),
  })
}
