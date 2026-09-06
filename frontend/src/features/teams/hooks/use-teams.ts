import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '@/api/client'
import { useAuthStore } from '@/stores/auth-store'
import type { ApiError } from '@/types/api-error'
import type { Team } from '@/types/shared-vault'

// ─── Types ──────────────────────────────────────────────────────────────────

export interface TeamMember {
  id: number
  name: string
  email: string
  role: string | null
  joined_at: string | null
}

interface CreateTeamInput {
  name: string
  description?: string | null
  color?: string | null
}

interface UpdateTeamInput {
  name?: string
  description?: string | null
  color?: string | null
}

interface AddMemberInput {
  user_id: number
  role: 'lead' | 'member'
}

function useTenantId(): number {
  return useAuthStore((s) => s.selectedTenantId) ?? 0
}

// ─── Team CRUD hooks ────────────────────────────────────────────────────────

export function useTeamsList() {
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

export function useCreateTeam() {
  const tenantId = useTenantId()
  const queryClient = useQueryClient()
  return useMutation<{ data: Team }, ApiError, CreateTeamInput>({
    mutationFn: (data) => api.post<{ data: Team }>(`/api/v1/tenants/${tenantId}/teams`, data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['teams'] }),
  })
}

export function useUpdateTeam(teamId: number) {
  const tenantId = useTenantId()
  const queryClient = useQueryClient()
  return useMutation<{ data: Team }, ApiError, UpdateTeamInput>({
    mutationFn: (data) =>
      api.put<{ data: Team }>(`/api/v1/tenants/${tenantId}/teams/${teamId}`, data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['teams'] }),
  })
}

export function useDeleteTeam() {
  const tenantId = useTenantId()
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (id) => api.delete(`/api/v1/tenants/${tenantId}/teams/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['teams'] }),
  })
}

// ─── Team member hooks ──────────────────────────────────────────────────────

export function useTeamMembers(teamId: number) {
  const tenantId = useTenantId()
  return useQuery<TeamMember[], ApiError>({
    queryKey: ['team-members', tenantId, teamId],
    queryFn: async () => {
      const res = await api.get<{ data: TeamMember[] }>(
        `/api/v1/tenants/${tenantId}/teams/${teamId}/members`,
      )
      return res.data
    },
    enabled: tenantId > 0 && teamId > 0,
  })
}

export function useAddTeamMember(teamId: number) {
  const tenantId = useTenantId()
  const queryClient = useQueryClient()
  return useMutation<{ data: TeamMember }, ApiError, AddMemberInput>({
    mutationFn: (data) =>
      api.post<{ data: TeamMember }>(
        `/api/v1/tenants/${tenantId}/teams/${teamId}/members`,
        data,
      ),
    onSuccess: () =>
      queryClient.invalidateQueries({ queryKey: ['team-members', tenantId, teamId] }),
  })
}

export function useUpdateTeamMember(teamId: number, userId: number) {
  const tenantId = useTenantId()
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, { role: 'lead' | 'member' }>({
    mutationFn: (data) =>
      api.put(
        `/api/v1/tenants/${tenantId}/teams/${teamId}/members/${userId}`,
        data,
      ),
    onSuccess: () =>
      queryClient.invalidateQueries({ queryKey: ['team-members', tenantId, teamId] }),
  })
}

export function useRemoveTeamMember(teamId: number) {
  const tenantId = useTenantId()
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (userId) =>
      api.delete(`/api/v1/tenants/${tenantId}/teams/${teamId}/members/${userId}`),
    onSuccess: () =>
      queryClient.invalidateQueries({ queryKey: ['team-members', tenantId, teamId] }),
  })
}
