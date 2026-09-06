import { useQuery } from '@tanstack/react-query'
import { api } from '@/api/client'
import type { ApiError } from '@/types/api-error'

// ─── Types ──────────────────────────────────────────────────────────────────

export interface PersonalDashboard {
  vault_summary: {
    total_items: string
    by_type: string
    favorites_count: string
    archived_count: string
  }
  recently_viewed: unknown[]
  recently_added: unknown[]
  shared_with_me: {
    total: number
    items: Record<string, unknown>
  }
  expiring_access: {
    count: number
    items: string
  }
  pending_requests: {
    sent: number
    received: number
  }
  security_alerts_unread: number
}

export interface CompanyDashboard {
  overview: {
    total_members: number
    active_members: number
    suspended_members: number
    total_teams: number
    total_vault_items: number
    total_files: number
    total_notes: number
  }
  members: unknown[]
  teams: unknown[]
  access_requests: {
    pending: number
    recent: unknown[]
  }
  temporary_access: {
    active: number
    expiring_24h: number
  }
  security_activity: {
    recent_alerts: number
    recent_revocations: number
    failed_logins_24h: number
  }
  expiring_access: {
    count: number
    items: string
  }
  recent_activity: unknown[]
}

export interface UsageDashboard {
  plan: string
  limits: {
    max_members: string | number
    max_storage_mb: string | number
    max_vault_items: string | number
  }
  usage: {
    members: number
    storage_used_mb: number
    vault_items: number
    files: number
    notes: number
  }
  percentages: {
    members: Record<string, never> | null
    storage: Record<string, never> | null
    vault_items: Record<string, never> | null
  }
}

// ─── Hooks ──────────────────────────────────────────────────────────────────

export function usePersonalDashboard() {
  return useQuery<PersonalDashboard, ApiError>({
    queryKey: ['dashboard', 'personal'],
    queryFn: async () => {
      const res = await api.get<{ data: PersonalDashboard }>('/api/v1/dashboard/personal')
      return res.data
    },
    staleTime: 60 * 1000, // 1 minute
  })
}

export function useCompanyDashboard() {
  return useQuery<CompanyDashboard, ApiError>({
    queryKey: ['dashboard', 'company'],
    queryFn: async () => {
      const res = await api.get<{ data: CompanyDashboard }>('/api/v1/dashboard/company')
      return res.data
    },
    staleTime: 60 * 1000,
  })
}

export function useUsageDashboard() {
  return useQuery<UsageDashboard, ApiError>({
    queryKey: ['dashboard', 'usage'],
    queryFn: async () => {
      const res = await api.get<{ data: UsageDashboard }>('/api/v1/dashboard/usage')
      return res.data
    },
    staleTime: 60 * 1000,
  })
}
