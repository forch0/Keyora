import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '@/api/client'
import { useAuthStore } from '@/stores/auth-store'
import type { ApiError } from '@/types/api-error'
import type { SharedVaultItem, Team, PaginatedResponse } from '@/types/shared-vault'

// ─── Types ──────────────────────────────────────────────────────────────────

export type SharedItemTypeCode = 'password' | 'api_key' | 'server' | 'database' | 'note'

export interface SharedVaultItemFormData {
  name: string
  type: SharedItemTypeCode
  username?: string | null
  password?: string | null
  url?: string | null
  notes?: string | null
  custom_fields?: { key: string; value: string }[] | null
  folder_id?: number | null
}

interface ListParams {
  page?: number
  per_page?: number
  sort?: string
  type?: string
  [key: string]: unknown
}

// ─── Org vault hooks ────────────────────────────────────────────────────────

function useTenantId(): number {
  return useAuthStore((s) => s.selectedTenantId) ?? 0
}

export function useOrgVaultItems(params: ListParams = {}) {
  const tenantId = useTenantId()
  return useQuery<PaginatedResponse<SharedVaultItem>, ApiError>({
    queryKey: ['org-vault-items', tenantId, params],
    queryFn: () =>
      api.get<PaginatedResponse<SharedVaultItem>>(
        `/api/v1/tenants/${tenantId}/vault/items`,
        params,
      ),
    enabled: tenantId > 0,
  })
}

export function useOrgVaultItem(id: number) {
  const tenantId = useTenantId()
  return useQuery<SharedVaultItem, ApiError>({
    queryKey: ['org-vault-items', tenantId, id],
    queryFn: async () => {
      const res = await api.get<{ data: SharedVaultItem }>(
        `/api/v1/tenants/${tenantId}/vault/items/${id}`,
      )
      return res.data
    },
    enabled: tenantId > 0 && id > 0,
  })
}

export function useCreateOrgVaultItem() {
  const tenantId = useTenantId()
  const queryClient = useQueryClient()
  return useMutation<{ data: SharedVaultItem }, ApiError, SharedVaultItemFormData>({
    mutationFn: (data) =>
      api.post<{ data: SharedVaultItem }>(`/api/v1/tenants/${tenantId}/vault/items`, data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['org-vault-items'] }),
  })
}

export function useUpdateOrgVaultItem(id: number) {
  const tenantId = useTenantId()
  const queryClient = useQueryClient()
  return useMutation<{ data: SharedVaultItem }, ApiError, Partial<SharedVaultItemFormData>>({
    mutationFn: (data) =>
      api.put<{ data: SharedVaultItem }>(
        `/api/v1/tenants/${tenantId}/vault/items/${id}`,
        data,
      ),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['org-vault-items'] }),
  })
}

export function useDeleteOrgVaultItem() {
  const tenantId = useTenantId()
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (id) => api.delete(`/api/v1/tenants/${tenantId}/vault/items/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['org-vault-items'] }),
  })
}

// ─── Team vault hooks ───────────────────────────────────────────────────────

export function useTeams() {
  const tenantId = useTenantId()
  return useQuery<Team[], ApiError>({
    queryKey: ['teams', tenantId],
    queryFn: async () => {
      const res = await api.get<{ data: Team[] }>(`/api/v1/tenants/${tenantId}/teams`)
      return res.data
    },
    enabled: tenantId > 0,
  })
}

export function useTeamVaultItems(teamId: number, params: ListParams = {}) {
  const tenantId = useTenantId()
  return useQuery<PaginatedResponse<SharedVaultItem>, ApiError>({
    queryKey: ['team-vault-items', tenantId, teamId, params],
    queryFn: () =>
      api.get<PaginatedResponse<SharedVaultItem>>(
        `/api/v1/tenants/${tenantId}/teams/${teamId}/vault/items`,
        params,
      ),
    enabled: tenantId > 0 && teamId > 0,
  })
}

export function useTeamVaultItem(teamId: number, id: number) {
  const tenantId = useTenantId()
  return useQuery<SharedVaultItem, ApiError>({
    queryKey: ['team-vault-items', tenantId, teamId, id],
    queryFn: async () => {
      const res = await api.get<{ data: SharedVaultItem }>(
        `/api/v1/tenants/${tenantId}/teams/${teamId}/vault/items/${id}`,
      )
      return res.data
    },
    enabled: tenantId > 0 && teamId > 0 && id > 0,
  })
}

export function useCreateTeamVaultItem(teamId: number) {
  const tenantId = useTenantId()
  const queryClient = useQueryClient()
  return useMutation<{ data: SharedVaultItem }, ApiError, SharedVaultItemFormData>({
    mutationFn: (data) =>
      api.post<{ data: SharedVaultItem }>(
        `/api/v1/tenants/${tenantId}/teams/${teamId}/vault/items`,
        data,
      ),
    onSuccess: () =>
      queryClient.invalidateQueries({ queryKey: ['team-vault-items', tenantId, teamId] }),
  })
}

export function useUpdateTeamVaultItem(teamId: number, id: number) {
  const tenantId = useTenantId()
  const queryClient = useQueryClient()
  return useMutation<{ data: SharedVaultItem }, ApiError, Partial<SharedVaultItemFormData>>({
    mutationFn: (data) =>
      api.put<{ data: SharedVaultItem }>(
        `/api/v1/tenants/${tenantId}/teams/${teamId}/vault/items/${id}`,
        data,
      ),
    onSuccess: () =>
      queryClient.invalidateQueries({ queryKey: ['team-vault-items', tenantId, teamId] }),
  })
}

export function useDeleteTeamVaultItem(teamId: number) {
  const tenantId = useTenantId()
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (id) =>
      api.delete(`/api/v1/tenants/${tenantId}/teams/${teamId}/vault/items/${id}`),
    onSuccess: () =>
      queryClient.invalidateQueries({ queryKey: ['team-vault-items', tenantId, teamId] }),
  })
}
