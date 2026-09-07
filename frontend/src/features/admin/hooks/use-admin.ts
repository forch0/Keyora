import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '@/api/client'
import { useAuthStore } from '@/stores/auth-store'
import type { ApiError } from '@/types/api-error'
import type { Tenant } from '@/types/tenant'

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

export interface TenantInvitation {
  id: number
  email: string
  role: string
  invited_by: {
    id: number | null
    name: string | null
  }
  accepted_at: string | null
  expires_at: string
  created_at: string | null
}

// ─── Tenant settings ────────────────────────────────────────────────────────

export function useTenantSettings(id: number | null) {
  return useQuery<Tenant, ApiError>({
    queryKey: ['tenants', id],
    queryFn: async () => {
      const res = await api.get<{ data: Tenant }>(`/api/v1/tenants/${id}`)
      return res.data
    },
    enabled: id !== null && id > 0,
  })
}

export function useUpdateTenant() {
  const queryClient = useQueryClient()
  return useMutation<{ data: Tenant }, ApiError, { id: number; data: { name?: string; slug?: string } }>({
    mutationFn: ({ id, data }) => api.put<{ data: Tenant }>(`/api/v1/tenants/${id}`, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['tenants'] })
    },
  })
}

export function useDeleteTenant() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (id) => api.delete(`/api/v1/tenants/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['tenants'] })
    },
  })
}

// ─── Members ────────────────────────────────────────────────────────────────

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

export function useTenantMember(userId: number) {
  const tenantId = useAuthStore((s) => s.selectedTenantId)
  return useQuery<TenantMember, ApiError>({
    queryKey: ['tenant-members', tenantId, userId],
    queryFn: async () => {
      const res = await api.get<{ data: TenantMember }>(
        `/api/v1/tenants/${tenantId}/members/${userId}`,
      )
      return res.data
    },
    enabled: tenantId !== null && userId > 0,
  })
}

export function useChangeMemberRole() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, { userId: number; role: 'admin' | 'member' }>({
    mutationFn: ({ userId, role }) =>
      api.put(`/api/v1/tenants/${useAuthStore.getState().selectedTenantId}/members/${userId}/role`, { role }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['tenant-members'] })
    },
  })
}

export function useAssignTeams() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, { userId: number; teamIds: number[] }>({
    mutationFn: ({ userId, teamIds }) =>
      api.post(`/api/v1/tenants/${useAuthStore.getState().selectedTenantId}/members/${userId}/teams`, { team_ids: teamIds }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['tenant-members'] })
    },
  })
}

export function useRemoveFromTeam() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, { userId: number; teamId: number }>({
    mutationFn: ({ userId, teamId }) =>
      api.delete(`/api/v1/tenants/${useAuthStore.getState().selectedTenantId}/members/${userId}/teams/${teamId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['tenant-members'] })
    },
  })
}

export function useSuspendMember() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (userId) =>
      api.post(`/api/v1/tenants/${useAuthStore.getState().selectedTenantId}/members/${userId}/suspend`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['tenant-members'] })
    },
  })
}

export function useRestoreMember() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (userId) =>
      api.post(`/api/v1/tenants/${useAuthStore.getState().selectedTenantId}/members/${userId}/restore`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['tenant-members'] })
    },
  })
}

export function useRemoveMember() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (userId) =>
      api.delete(`/api/v1/tenants/${useAuthStore.getState().selectedTenantId}/members/${userId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['tenant-members'] })
    },
  })
}

export function useRevokeAllForUser() {
  return useMutation<{ revoked: number }, ApiError, number>({
    mutationFn: async (userId) => {
      const res = await api.post<{ revoked: number }>(
        `/api/v1/tenants/${useAuthStore.getState().selectedTenantId}/members/${userId}/revoke-all`,
      )
      return res
    },
  })
}

// ─── Invitations ────────────────────────────────────────────────────────────

export function useInvitations() {
  const tenantId = useAuthStore((s) => s.selectedTenantId)
  return useQuery<TenantInvitation[], ApiError>({
    queryKey: ['tenant-invitations', tenantId],
    queryFn: async () => {
      const res = await api.get<{ data: TenantInvitation[] }>(
        `/api/v1/tenants/${tenantId}/invitations`,
      )
      return res.data
    },
    enabled: tenantId !== null,
  })
}

export function useInviteMember() {
  const queryClient = useQueryClient()
  return useMutation<{ data: TenantInvitation }, ApiError, {
    email: string
    role: 'admin' | 'member'
    team_ids?: number[] | null
  }>({
    mutationFn: (data) =>
      api.post<{ data: TenantInvitation }>(
        `/api/v1/tenants/${useAuthStore.getState().selectedTenantId}/members/invite`,
        data,
      ),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['tenant-invitations'] })
    },
  })
}

export function useCancelInvitation() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (id) =>
      api.delete(`/api/v1/tenants/${useAuthStore.getState().selectedTenantId}/invitations/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['tenant-invitations'] })
    },
  })
}
