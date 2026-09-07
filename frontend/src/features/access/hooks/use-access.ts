import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '@/api/client'
import type { ApiError } from '@/types/api-error'
import type {
  ResourceType,
  AccessGrant,
  AccessSummary,
  AccessCountdown,
  RevokeResult,
  RevokeTeamResult,
  GrantAccessInput,
  BulkGrantInput,
  UpdateGrantInput,
} from '@/types/access'

// ─── Hooks ──────────────────────────────────────────────────────────────────

export function useAccessGrants(resource: ResourceType, id: number) {
  return useQuery<AccessGrant[], ApiError>({
    queryKey: ['access-grants', resource, id],
    queryFn: async () => {
      const res = await api.get<{ data: AccessGrant[] }>(
        `/api/v1/${resource}/${id}/access`,
      )
      return res.data
    },
    enabled: id > 0,
  })
}

export function useAccessSummary(resource: ResourceType, id: number) {
  return useQuery<AccessSummary, ApiError>({
    queryKey: ['access-summary', resource, id],
    queryFn: () => api.get<AccessSummary>(`/api/v1/${resource}/${id}/access/summary`),
    enabled: id > 0 && resource === 'vault/items',
  })
}

export function useAccessCountdown(resource: ResourceType, id: number) {
  return useQuery<AccessCountdown, ApiError>({
    queryKey: ['access-countdown', resource, id],
    queryFn: async () => {
      const res = await api.get<{ data: AccessCountdown }>(
        `/api/v1/${resource}/${id}/access/countdown`,
      )
      return res.data
    },
    enabled: id > 0 && resource === 'vault/items',
  })
}

export function useGrantAccess(resource: ResourceType, id: number) {
  const queryClient = useQueryClient()
  return useMutation<{ data: AccessGrant }, ApiError, GrantAccessInput>({
    mutationFn: (data) =>
      api.post<{ data: AccessGrant }>(`/api/v1/${resource}/${id}/access`, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['access-grants', resource, id] })
      queryClient.invalidateQueries({ queryKey: ['access-summary', resource, id] })
    },
  })
}

export function useBulkGrantAccess(resource: ResourceType, id: number) {
  const queryClient = useQueryClient()
  return useMutation<{ data: AccessGrant[] }, ApiError, BulkGrantInput>({
    mutationFn: (data) =>
      api.post<{ data: AccessGrant[] }>(`/api/v1/${resource}/${id}/access/bulk`, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['access-grants', resource, id] })
      queryClient.invalidateQueries({ queryKey: ['access-summary', resource, id] })
    },
  })
}

export function useUpdateGrant(resource: ResourceType, id: number) {
  const queryClient = useQueryClient()
  return useMutation<{ data: AccessGrant }, ApiError, { grantId: number; data: UpdateGrantInput }>({
    mutationFn: ({ grantId, data }) =>
      api.put<{ data: AccessGrant }>(`/api/v1/${resource}/${id}/access/${grantId}`, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['access-grants', resource, id] })
      queryClient.invalidateQueries({ queryKey: ['access-summary', resource, id] })
    },
  })
}

export function useRevokeGrant(resource: ResourceType, id: number) {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (grantId) => api.delete(`/api/v1/${resource}/${id}/access/${grantId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['access-grants', resource, id] })
      queryClient.invalidateQueries({ queryKey: ['access-summary', resource, id] })
    },
  })
}

export function useRevokeAllAccess(resource: ResourceType, id: number) {
  const queryClient = useQueryClient()
  return useMutation<RevokeResult, ApiError, void>({
    mutationFn: () => api.post<RevokeResult>(`/api/v1/${resource}/${id}/access/revoke-all`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['access-grants', resource, id] })
      queryClient.invalidateQueries({ queryKey: ['access-summary', resource, id] })
    },
  })
}

export function useRevokeTeamAccess(resource: ResourceType, id: number) {
  const queryClient = useQueryClient()
  return useMutation<RevokeTeamResult, ApiError, number>({
    mutationFn: (teamId) =>
      api.post<RevokeTeamResult>(`/api/v1/${resource}/${id}/access/revoke-team/${teamId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['access-grants', resource, id] })
      queryClient.invalidateQueries({ queryKey: ['access-summary', resource, id] })
    },
  })
}
