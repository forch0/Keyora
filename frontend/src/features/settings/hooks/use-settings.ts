import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '@/api/client'
import { useAuthStore, type User } from '@/stores/auth-store'
import type { ApiError } from '@/types/api-error'

// ─── Types ──────────────────────────────────────────────────────────────────

interface UserResponse { data: User }

interface UpdateProfileInput {
  name: string
  email: string
}

interface ChangePasswordInput {
  current_password: string
  password: string
  password_confirmation: string
}

interface Enable2faResponse {
  data: {
    secret: string
    qr_code_uri: string
  }
}

interface Confirm2faResponse {
  data: {
    recovery_codes: string
    message: string
  }
}

interface RecoveryCodesResponse {
  data: {
    recovery_codes: string[]
    message: string
  }
}

// ─── Profile ────────────────────────────────────────────────────────────────

export function useProfile() {
  return useQuery<User, ApiError>({
    queryKey: ['auth', 'me'],
    queryFn: async () => {
      const res = await api.get<UserResponse>('/api/v1/auth/me')
      return res.data
    },
    staleTime: 0,
  })
}

export function useUpdateProfile() {
  const queryClient = useQueryClient()
  const setUser = useAuthStore((s) => s.setUser)
  return useMutation<UserResponse, ApiError, UpdateProfileInput>({
    mutationFn: (data) => api.put<UserResponse>('/api/v1/auth/me', data),
    onSuccess: (res) => {
      setUser(res.data)
      queryClient.invalidateQueries({ queryKey: ['auth', 'me'] })
    },
  })
}

// ─── Password ───────────────────────────────────────────────────────────────

export function useChangePassword() {
  return useMutation<UserResponse, ApiError, ChangePasswordInput>({
    mutationFn: (data) => api.post<UserResponse>('/api/v1/auth/password', data),
  })
}

// ─── 2FA ────────────────────────────────────────────────────────────────────

export function useEnable2fa() {
  return useMutation<Enable2faResponse, ApiError, void>({
    mutationFn: () => api.post<Enable2faResponse>('/api/v1/auth/2fa/enable'),
  })
}

export function useConfirm2fa() {
  const queryClient = useQueryClient()
  return useMutation<Confirm2faResponse, ApiError, string>({
    mutationFn: (code) => api.post<Confirm2faResponse>('/api/v1/auth/2fa/confirm', { code }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['auth', 'me'] })
    },
  })
}

export function useDisable2fa() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, string>({
    mutationFn: (password) => api.post('/api/v1/auth/2fa/disable', { password }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['auth', 'me'] })
    },
  })
}

export function useRecoveryCodes() {
  return useQuery<RecoveryCodesResponse, ApiError>({
    queryKey: ['auth', '2fa', 'recovery-codes'],
    queryFn: () => api.get<RecoveryCodesResponse>('/api/v1/auth/2fa/recovery-codes'),
    enabled: false, // manually triggered via refetch
  })
}
