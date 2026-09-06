import { useQuery } from '@tanstack/react-query'
import { api } from '@/api/client'
import { useAuthStore } from '@/stores/auth-store'
import type { ApiError } from '@/types/api-error'
import type {
  ActivityLog,
  PaginatedActivityLogs,
  ActivityLogResourceType,
} from '@/types/activity-log'

export function usePersonalActivityLogs(params: { page?: number; per_page?: number } = {}) {
  return useQuery<PaginatedActivityLogs, ApiError>({
    queryKey: ['activity-logs', 'personal', params],
    queryFn: async () => {
      const res = await api.get<PaginatedActivityLogs>('/api/v1/activity-logs', params as Record<string, unknown>)
      return res
    },
  })
}

export function useCompanyActivityLogs(params: { page?: number; per_page?: number } = {}) {
  const tenantId = useAuthStore((s) => s.selectedTenantId)
  return useQuery<PaginatedActivityLogs, ApiError>({
    queryKey: ['activity-logs', 'company', tenantId, params],
    queryFn: async () => {
      const res = await api.get<PaginatedActivityLogs>(
        `/api/v1/tenants/${tenantId}/activity-logs`,
        params as Record<string, unknown>,
      )
      return res
    },
    enabled: tenantId !== null,
  })
}

export function useEmployeeActivityLogs(
  tenantId: number | null,
  userId: number,
  params: { page?: number; per_page?: number } = {},
) {
  return useQuery<PaginatedActivityLogs, ApiError>({
    queryKey: ['activity-logs', 'employee', tenantId, userId, params],
    queryFn: async () => {
      const res = await api.get<PaginatedActivityLogs>(
        `/api/v1/tenants/${tenantId}/members/${userId}/activity-logs`,
        params as Record<string, unknown>,
      )
      return res
    },
    enabled: tenantId !== null && userId > 0,
  })
}

export function useResourceActivityLogs(
  resource: ActivityLogResourceType,
  id: number,
  params: { page?: number; per_page?: number } = {},
) {
  return useQuery<PaginatedActivityLogs, ApiError>({
    queryKey: ['activity-logs', 'resource', resource, id, params],
    queryFn: async () => {
      const res = await api.get<PaginatedActivityLogs>(
        `/api/v1/${resource}/${id}/activity-logs`,
        params as Record<string, unknown>,
      )
      return res
    },
    enabled: id > 0,
  })
}

// Helper to get a flat list of logs (for inline panels)
export function useResourceActivityLogList(resource: ActivityLogResourceType, id: number) {
  return useQuery<ActivityLog[], ApiError>({
    queryKey: ['activity-logs', 'resource-list', resource, id],
    queryFn: async () => {
      const res = await api.get<PaginatedActivityLogs>(
        `/api/v1/${resource}/${id}/activity-logs`,
        { per_page: 10 } as Record<string, unknown>,
      )
      return res.data
    },
    enabled: id > 0,
  })
}
