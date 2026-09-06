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
  email_verified_at: string | null
  created_at: string | null
  updated_at: string | null
}

interface AuthState {
  status: AuthStatus
  user: User | null
  token: string | null
  twoFactorToken: string | null
  selectedTenantId: number | null
  setUser: (user: User | null) => void
  setToken: (token: string | null) => void
  setStatus: (status: AuthStatus) => void
  setTwoFactorToken: (token: string | null) => void
  setTenant: (tenantId: number | null) => void
  clear: () => void
}

const TOKEN_KEY = 'keyora_token'
const TENANT_KEY = 'keyora_tenant_id'

function getStoredToken(): string | null {
  try {
    return localStorage.getItem(TOKEN_KEY)
  } catch {
    return null
  }
}

function getStoredTenantId(): number | null {
  try {
    const raw = localStorage.getItem(TENANT_KEY)
    return raw ? Number(raw) : null
  } catch {
    return null
  }
}

export const useAuthStore = create<AuthState>((set) => ({
  status: 'unauthenticated',
  user: null,
  token: getStoredToken(),
  twoFactorToken: null,
  selectedTenantId: getStoredTenantId(),

  setUser: (user) =>
    set((state) => ({
      user,
      status: user ? 'authenticated' : state.status,
    })),

  setToken: (token) => {
    try {
      if (token) {
        localStorage.setItem(TOKEN_KEY, token)
      } else {
        localStorage.removeItem(TOKEN_KEY)
      }
    } catch {
      // localStorage may be unavailable
    }
    set({ token })
  },

  setStatus: (status) => set({ status }),

  setTwoFactorToken: (twoFactorToken) => set({ twoFactorToken }),

  setTenant: (selectedTenantId) => {
    try {
      if (selectedTenantId !== null) {
        localStorage.setItem(TENANT_KEY, String(selectedTenantId))
      } else {
        localStorage.removeItem(TENANT_KEY)
      }
    } catch {
      // localStorage may be unavailable
    }
    set({ selectedTenantId })
  },

  clear: () => {
    try {
      localStorage.removeItem(TOKEN_KEY)
      localStorage.removeItem(TENANT_KEY)
    } catch {
      // localStorage may be unavailable
    }
    set({
      status: 'unauthenticated',
      user: null,
      token: null,
      twoFactorToken: null,
      selectedTenantId: null,
    })
  },
}))
