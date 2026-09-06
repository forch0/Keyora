import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '@/api/client'
import type { ApiError } from '@/types/api-error'
import type {
  AccessRequest,
  CreateAccessRequestInput,
  ApproveAccessRequestInput,
  RejectAccessRequestInput,
  RequestDirection,
  RequestStatus,
} from '@/types/access-request'
import type { PaginatedResponse } from '@/types/shared-vault'

interface ListParams {
  direction?: RequestDirection
  status?: RequestStatus
  page?: number
  [key: string]: unknown
}

const QUERY_KEY = ['access-requests']

export function useAccessRequests(params: ListParams = {}) {
  return useQuery<PaginatedResponse<AccessRequest>, ApiError>({
    queryKey: [...QUERY_KEY, 'list', params],
    queryFn: () => api.get<PaginatedResponse<AccessRequest>>('/api/v1/access-requests', params),
  })
}

export function useAccessRequestHistory(params: { status?: RequestStatus; page?: number } = {}) {
  return useQuery<PaginatedResponse<AccessRequest>, ApiError>({
    queryKey: [...QUERY_KEY, 'history', params],
    queryFn: () => api.get<PaginatedResponse<AccessRequest>>('/api/v1/access-requests/history', params),
  })
}

export function useCreateAccessRequest() {
  const queryClient = useQueryClient()
  return useMutation<{ data: AccessRequest }, ApiError, CreateAccessRequestInput>({
    mutationFn: (data) => api.post<{ data: AccessRequest }>('/api/v1/access-requests', data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: QUERY_KEY }),
  })
}

export function useApproveRequest() {
  const queryClient = useQueryClient()
  return useMutation<{ data: AccessRequest }, ApiError, { id: number; data: ApproveAccessRequestInput }>({
    mutationFn: ({ id, data }) =>
      api.put<{ data: AccessRequest }>(`/api/v1/access-requests/${id}/approve`, data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: QUERY_KEY }),
  })
}

export function useRejectRequest() {
  const queryClient = useQueryClient()
  return useMutation<{ data: AccessRequest }, ApiError, { id: number; data: RejectAccessRequestInput }>({
    mutationFn: ({ id, data }) =>
      api.put<{ data: AccessRequest }>(`/api/v1/access-requests/${id}/reject`, data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: QUERY_KEY }),
  })
}

export function useCancelRequest() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (id) => api.delete(`/api/v1/access-requests/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: QUERY_KEY }),
  })
}
