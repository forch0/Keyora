import { useQuery } from '@tanstack/react-query'
import { api } from '@/api/client'
import { useAuthStore } from '@/stores/auth-store'
import type { ApiError } from '@/types/api-error'

export interface TenantMember {
  id: number
  name: string
  email: string
  role: string | null
  status: string | null
  joined_at: string | null
  suspended_at: string | null
  teams_count?: number
  created_at: string | null
}

export function useTenantMembers() {
  const tenantId = useAuthStore((s) => s.selectedTenantId)
  return useQuery<TenantMember[], ApiError>({
    queryKey: ['tenant-members', tenantId],
    queryFn: async () => {
      const res = await api.get<{ data: TenantMember[] }>(
        `/api/v1/tenants/${tenantId}/members`,
      )
      return res.data
    },
    enabled: tenantId !== null,
  })
}
