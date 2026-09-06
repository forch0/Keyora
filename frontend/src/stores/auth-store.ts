import { create } from 'zustand'

export type AuthStatus =
  | 'unauthenticated'
  | 'logging_in'
  | 'requires_2fa'
  | 'authenticated'

export interface User {
  id: number
  name: string
  email: string
  two_factor_enabled: boolean
  email_verified_at: string | null
  created_at: string
  updated_at: string
}

interface AuthState {
  status: AuthStatus
  user: User | null
  twoFactorToken: string | null
  selectedTenantId: number | null
  setUser: (user: User | null) => void
  setStatus: (status: AuthStatus) => void
  setTwoFactorToken: (token: string | null) => void
  setTenant: (tenantId: number | null) => void
  clear: () => void
}

export const useAuthStore = create<AuthState>((set) => ({
  status: 'unauthenticated',
  user: null,
  twoFactorToken: null,
  selectedTenantId: null,

  setUser: (user) =>
    set((state) => ({
      user,
      status: user ? 'authenticated' : state.status,
    })),

  setStatus: (status) => set({ status }),

  setTwoFactorToken: (twoFactorToken) => set({ twoFactorToken }),

  setTenant: (selectedTenantId) => set({ selectedTenantId }),

  clear: () =>
    set({
      status: 'unauthenticated',
      user: null,
      twoFactorToken: null,
      selectedTenantId: null,
    }),
}))
