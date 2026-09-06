import { useQuery } from '@tanstack/react-query'
import { api } from '@/api/client'
import type { ApiError } from '@/types/api-error'
import type {
  SearchResults,
  RecentCreatedResults,
  ExpiringAccessResults,
} from '@/types/search'

export function useGlobalSearch(query: string) {
  return useQuery<SearchResults, ApiError>({
    queryKey: ['search', query],
    queryFn: async () => {
      const res = await api.get<{ data: SearchResults }>('/api/v1/search', { q: query })
      return res.data
    },
    enabled: query.length > 0,
    staleTime: 30 * 1000,
  })
}

export function useRecentItems() {
  return useQuery<RecentCreatedResults, ApiError>({
    queryKey: ['search', 'recent'],
    queryFn: async () => {
      const res = await api.get<{ data: RecentCreatedResults }>('/api/v1/search/recent')
      return res.data
    },
  })
}

export function useRecentCreated() {
  return useQuery<RecentCreatedResults, ApiError>({
    queryKey: ['search', 'recent-created'],
    queryFn: async () => {
      const res = await api.get<{ data: RecentCreatedResults }>('/api/v1/search/recent/created')
      return res.data
    },
  })
}

export function useExpiringAccess() {
  return useQuery<ExpiringAccessResults, ApiError>({
    queryKey: ['search', 'expiring'],
    queryFn: async () => {
      const res = await api.get<{ data: ExpiringAccessResults }>('/api/v1/search/expiring')
      return res.data
    },
  })
}
