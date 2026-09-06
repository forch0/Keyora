import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '@/api/client'
import { fetchCsrfToken } from '@/api/csrf'
import { useAuthStore, type User } from '@/stores/auth-store'
import type { ApiError } from '@/types/api-error'

// ─── Types ──────────────────────────────────────────────────────────────────

interface LoginResponse {
  data: User
  token: string
}

interface TwoFactorChallengeResponse {
  data: {
    requires_2fa: true
    '2fa_token': string
  }
}

interface ForgotPasswordResponse {
  data: {
    message: string
  }
}

interface ResetPasswordResponse {
  data: {
    message: string
  }
}

// ─── useLogin ───────────────────────────────────────────────────────────────

export function useLogin() {
  const { setUser, setStatus, setTwoFactorToken } = useAuthStore()

  return useMutation<LoginResponse | TwoFactorChallengeResponse, ApiError, { email: string; password: string }>({
    mutationFn: async ({ email, password }) => {
      await fetchCsrfToken()
      return api.post<LoginResponse | TwoFactorChallengeResponse>('/api/v1/auth/login', {
        email,
        password,
      })
    },
    onSuccess: (response) => {
      const data = response.data
      if ('requires_2fa' in data && data.requires_2fa) {
        setTwoFactorToken(data['2fa_token'])
        setStatus('requires_2fa')
        return
      }
      setUser(data as User)
    },
    onError: () => {
      setStatus('unauthenticated')
    },
  })
}

// ─── useVerify2fa ───────────────────────────────────────────────────────────

export function useVerify2fa() {
  const { twoFactorToken, setUser, setTwoFactorToken } = useAuthStore()

  return useMutation<LoginResponse, ApiError, { code?: string; recoveryCode?: string }>({
    mutationFn: async ({ code, recoveryCode }) => {
      if (!twoFactorToken) {
        throw { code: 'NO_2FA_TOKEN', message: 'No 2FA token found.', status: 400 } satisfies ApiError
      }
      return api.post<LoginResponse>('/api/v1/auth/2fa/verify', {
        '2fa_token': twoFactorToken,
        code: code ?? null,
        recovery_code: recoveryCode ?? null,
      })
    },
    onSuccess: (response) => {
      setUser(response.data)
      setTwoFactorToken(null)
    },
  })
}

// ─── useRegister ────────────────────────────────────────────────────────────

export function useRegister() {
  const { setUser } = useAuthStore()

  return useMutation<
    LoginResponse,
    ApiError,
    { name: string; email: string; password: string; passwordConfirmation: string }
  >({
    mutationFn: async ({ name, email, password, passwordConfirmation }) => {
      await fetchCsrfToken()
      return api.post<LoginResponse>('/api/v1/auth/register', {
        name,
        email,
        password,
        password_confirmation: passwordConfirmation,
      })
    },
    onSuccess: (response) => {
      setUser(response.data)
    },
  })
}

// ─── useLogout ──────────────────────────────────────────────────────────────

export function useLogout() {
  const { clear } = useAuthStore()
  const queryClient = useQueryClient()

  return useMutation<void, ApiError, void>({
    mutationFn: () => api.post('/api/v1/auth/logout'),
    onSuccess: () => {
      clear()
      queryClient.clear()
    },
    onError: () => {
      clear()
      queryClient.clear()
    },
  })
}

// ─── useForgotPassword ──────────────────────────────────────────────────────

export function useForgotPassword() {
  return useMutation<ForgotPasswordResponse, ApiError, { email: string }>({
    mutationFn: async ({ email }) => {
      await fetchCsrfToken()
      return api.post<ForgotPasswordResponse>('/api/v1/auth/forgot-password', { email })
    },
  })
}

// ─── useResetPassword ───────────────────────────────────────────────────────

export function useResetPassword() {
  return useMutation<
    ResetPasswordResponse,
    ApiError,
    { email: string; token: string; password: string; passwordConfirmation: string }
  >({
    mutationFn: async ({ email, token, password, passwordConfirmation }) => {
      await fetchCsrfToken()
      return api.post<ResetPasswordResponse>('/api/v1/auth/reset-password', {
        email,
        token,
        password,
        password_confirmation: passwordConfirmation,
      })
    },
  })
}

// ─── useCurrentUser ─────────────────────────────────────────────────────────

export function useCurrentUser() {
  const { user, setUser, clear } = useAuthStore()

  const query = useQuery<User, ApiError>({
    queryKey: ['auth', 'me'],
    queryFn: async () => {
      const response = await api.get<{ data: User }>('/api/v1/auth/me')
      return response.data
    },
    enabled: !!user,
    staleTime: 5 * 60 * 1000,
  })

  // Sync user data to store (v5 removed onSuccess/onError from useQuery)
  if (query.data && query.data !== user) {
    setUser(query.data)
  }
  if (query.isError && user) {
    clear()
  }

  return query
}
