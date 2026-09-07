import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '@/api/client'
import type { ApiError } from '@/types/api-error'
import type {
  VaultItem,
  VaultItemListParams,
  PaginatedVaultItems,
} from '@/types/vault'

// ─── useVaultItems ──────────────────────────────────────────────────────────

export function useVaultItems(params: VaultItemListParams = {}) {
  return useQuery<PaginatedVaultItems, ApiError>({
    queryKey: ['vault-items', params],
    queryFn: () => api.get<PaginatedVaultItems>('/api/v1/vault/items', params as Record<string, unknown>),
    placeholderData: (prev) => prev,
  })
}

// ─── useVaultItem ───────────────────────────────────────────────────────────

export function useVaultItem(id: number) {
  return useQuery<VaultItem, ApiError>({
    queryKey: ['vault-items', 'detail', id],
    queryFn: async () => {
      const response = await api.get<{ data: VaultItem }>(`/api/v1/vault/items/${id}`)
      return response.data
    },
    enabled: !!id,
  })
}

// ─── useRecentVaultItems ────────────────────────────────────────────────────

export function useRecentVaultItems() {
  return useQuery<VaultItem[], ApiError>({
    queryKey: ['vault-items', 'recent'],
    queryFn: async () => {
      const response = await api.get<{ data: VaultItem[] }>('/api/v1/vault/items/recent')
      return response.data
    },
  })
}

// ─── useFavoriteVaultItems ──────────────────────────────────────────────────

export function useFavoriteVaultItems() {
  return useQuery<VaultItem[], ApiError>({
    queryKey: ['vault-items', 'favorites'],
    queryFn: async () => {
      const response = await api.get<{ data: VaultItem[] }>('/api/v1/vault/items/favorites')
      return response.data
    },
  })
}

// ─── useArchivedVaultItems ──────────────────────────────────────────────────

export function useArchivedVaultItems() {
  return useQuery<VaultItem[], ApiError>({
    queryKey: ['vault-items', 'archived'],
    queryFn: async () => {
      const response = await api.get<{ data: VaultItem[] }>('/api/v1/vault/items/archived')
      return response.data
    },
  })
}

// ─── useSearchVaultItems ────────────────────────────────────────────────────

export function useSearchVaultItems(q: string, enabled = true) {
  return useQuery<VaultItem[], ApiError>({
    queryKey: ['vault-items', 'search', q],
    queryFn: async () => {
      const response = await api.get<{ data: VaultItem[] }>('/api/v1/vault/search', { q })
      return response.data
    },
    enabled: enabled && q.length > 0,
  })
}

// ─── useToggleFavorite ──────────────────────────────────────────────────────

export function useToggleFavorite() {
  const queryClient = useQueryClient()

  return useMutation<void, ApiError, { id: number; favorite: boolean }, { previousQueries: [readonly unknown[], unknown][] }>({
    mutationFn: ({ id }) => api.post(`/api/v1/vault/items/${id}/favorite`),

    // Optimistic update: toggle favorite on the cached item immediately
    onMutate: async ({ id, favorite }) => {
      await queryClient.cancelQueries({ queryKey: ['vault-items'] })

      // Snapshot current cache for rollback
      const previousQueries = queryClient.getQueriesData({ queryKey: ['vault-items'] })

      // Update list cache
      queryClient.setQueriesData(
        { queryKey: ['vault-items'] },
        (old: PaginatedVaultItems | undefined) => {
          if (!old) return old
          return {
            ...old,
            data: old.data.map((item) =>
              item.id === id ? { ...item, favorite } : item,
            ),
          }
        },
      )

      // Update detail cache
      queryClient.setQueryData<VaultItem>(['vault-items', 'detail', id], (old) =>
        old ? { ...old, favorite } : old,
      )

      return { previousQueries }
    },

    onError: (_err, _vars, context) => {
      // Rollback on error
      if (context?.previousQueries) {
        for (const [key, data] of context.previousQueries) {
          queryClient.setQueryData(key, data)
        }
      }
    },

    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: ['vault-items'] })
    },
  })
}
