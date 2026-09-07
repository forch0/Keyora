import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '@/api/client'
import type { ApiError } from '@/types/api-error'
import type {
  UnreadAlertCount,
  PaginatedSecurityAlerts,
  Device,
} from '@/types/security-alert'

// ─── Security alerts ────────────────────────────────────────────────────────

export function useSecurityAlerts(params: { page?: number; per_page?: number; severity?: string } = {}) {
  return useQuery<PaginatedSecurityAlerts, ApiError>({
    queryKey: ['security-alerts', params],
    queryFn: async () => {
      const res = await api.get<PaginatedSecurityAlerts>(
        '/api/v1/security-alerts',
        params as Record<string, unknown>,
      )
      return res
    },
  })
}

export function useUnreadAlertCount() {
  return useQuery<UnreadAlertCount, ApiError>({
    queryKey: ['security-alerts', 'unread-count'],
    queryFn: async () => {
      const res = await api.get<UnreadAlertCount>('/api/v1/security-alerts/unread-count')
      return res
    },
    refetchInterval: 60 * 1000, // poll every 60s
  })
}

export function useMarkAlertRead() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (id) => api.post(`/api/v1/security-alerts/${id}/read`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['security-alerts'] })
    },
  })
}

export function useDismissAlert() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (id) => api.post(`/api/v1/security-alerts/${id}/dismiss`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['security-alerts'] })
    },
  })
}

export function useMarkAllAlertsRead() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, void>({
    mutationFn: () => api.post('/api/v1/security-alerts/read-all'),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['security-alerts'] })
    },
  })
}

// ─── Devices ────────────────────────────────────────────────────────────────

export function useDevices() {
  return useQuery<Device[], ApiError>({
    queryKey: ['devices'],
    queryFn: async () => {
      const res = await api.get<{ data: Device[] }>('/api/v1/devices')
      return res.data
    },
  })
}

export function useRevokeDevice() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (id) => api.delete(`/api/v1/devices/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['devices'] })
    },
  })
}
