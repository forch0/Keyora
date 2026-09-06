import { useMutation, useQueryClient } from '@tanstack/react-query'
import { api } from '@/api/client'
import type { ApiError } from '@/types/api-error'
import type { VaultItem } from '@/types/vault'

// ─── Types ──────────────────────────────────────────────────────────────────

export type ItemTypeCode = 'password' | 'api_key' | 'server' | 'database'

interface ItemMetadata {
  host?: string | null
  port?: number | null
  protocol?: string | null
  provider?: string | null
  key_label?: string | null
  db_type?: string | null
  database_name?: string | null
}

export interface VaultItemFormData {
  name: string
  type: ItemTypeCode
  username?: string | null
  password?: string | null
  url?: string | null
  notes?: string | null
  metadata?: ItemMetadata
  custom_fields?: { key: string; value: string }[] | null
  folder_id?: number | null
  tag_ids?: number[] | null
}

interface CreateResponse {
  data: VaultItem
}

interface UpdateResponse {
  data: VaultItem
}

// ─── useCreateVaultItem ─────────────────────────────────────────────────────

export function useCreateVaultItem() {
  const queryClient = useQueryClient()

  return useMutation<CreateResponse, ApiError, VaultItemFormData>({
    mutationFn: (data) => api.post<CreateResponse>('/api/v1/vault/items', data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['vault-items'] })
    },
  })
}

// ─── useUpdateVaultItem ─────────────────────────────────────────────────────

export function useUpdateVaultItem(id: number) {
  const queryClient = useQueryClient()

  return useMutation<UpdateResponse, ApiError, Partial<VaultItemFormData>>({
    mutationFn: (data) => api.put<UpdateResponse>(`/api/v1/vault/items/${id}`, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['vault-items'] })
    },
  })
}

// ─── useDeleteVaultItem ─────────────────────────────────────────────────────

export function useDeleteVaultItem() {
  const queryClient = useQueryClient()

  return useMutation<void, ApiError, number>({
    mutationFn: (id) => api.delete(`/api/v1/vault/items/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['vault-items'] })
    },
  })
}

// ─── useArchiveVaultItem ────────────────────────────────────────────────────

export function useArchiveVaultItem() {
  const queryClient = useQueryClient()

  return useMutation<void, ApiError, number>({
    mutationFn: (id) => api.post(`/api/v1/vault/items/${id}/archive`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['vault-items'] })
    },
  })
}

// ─── useRestoreVaultItem ────────────────────────────────────────────────────

export function useRestoreVaultItem() {
  const queryClient = useQueryClient()

  return useMutation<void, ApiError, number>({
    mutationFn: (id) => api.post(`/api/v1/vault/items/${id}/restore`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['vault-items'] })
    },
  })
}
