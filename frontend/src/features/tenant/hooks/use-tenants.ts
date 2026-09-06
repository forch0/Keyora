import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '@/api/client'
import type { ApiError } from '@/types/api-error'
import type { Tenant } from '@/types/tenant'

// ─── Types ──────────────────────────────────────────────────────────────────

interface TenantListResponse { data: Tenant[] }
interface TenantResponse { data: Tenant }

interface CreateTenantInput {
  name: string
  slug?: string | null
}

// ─── useTenants ─────────────────────────────────────────────────────────────

export function useTenants() {
  return useQuery<Tenant[], ApiError>({
    queryKey: ['tenants'],
    queryFn: async () => {
      const res = await api.get<TenantListResponse>('/api/v1/tenants')
      return res.data
    },
    staleTime: 5 * 60 * 1000, // 5 minutes
  })
}

// ─── useTenant ──────────────────────────────────────────────────────────────

export function useTenant(id: number) {
  return useQuery<Tenant, ApiError>({
    queryKey: ['tenants', id],
    queryFn: async () => {
      const res = await api.get<TenantResponse>(`/api/v1/tenants/${id}`)
      return res.data
    },
    enabled: id > 0,
  })
}

// ─── useCreateTenant ────────────────────────────────────────────────────────

export function useCreateTenant() {
  const queryClient = useQueryClient()
  return useMutation<TenantResponse, ApiError, CreateTenantInput>({
    mutationFn: (data) => api.post<TenantResponse>('/api/v1/tenants', data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['tenants'] }),
  })
}
