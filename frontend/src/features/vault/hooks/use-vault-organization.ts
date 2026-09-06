import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '@/api/client'
import type { ApiError } from '@/types/api-error'
import type { VaultFolder, VaultTag } from '@/types/vault-organization'

// ─── Folder types ───────────────────────────────────────────────────────────

interface FolderResponse { data: VaultFolder[] }
interface SingleFolderResponse { data: VaultFolder }

interface CreateFolderInput {
  name: string
  parent_id?: number | null
  icon?: string | null
  color?: string | null
}

interface UpdateFolderInput {
  name?: string
  parent_id?: number | null
  icon?: string | null
  color?: string | null
  sort_order?: number
}

// ─── Folder hooks ───────────────────────────────────────────────────────────

export function useFolders() {
  return useQuery<VaultFolder[], ApiError>({
    queryKey: ['vault-folders'],
    queryFn: async () => {
      const res = await api.get<FolderResponse>('/api/v1/vault/folders')
      return res.data
    },
  })
}

export function useCreateFolder() {
  const queryClient = useQueryClient()
  return useMutation<SingleFolderResponse, ApiError, CreateFolderInput>({
    mutationFn: (data) => api.post<SingleFolderResponse>('/api/v1/vault/folders', data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['vault-folders'] }),
  })
}

export function useUpdateFolder(id: number) {
  const queryClient = useQueryClient()
  return useMutation<SingleFolderResponse, ApiError, UpdateFolderInput>({
    mutationFn: (data) => api.put<SingleFolderResponse>(`/api/v1/vault/folders/${id}`, data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['vault-folders'] }),
  })
}

export function useDeleteFolder() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (id) => api.delete(`/api/v1/vault/folders/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['vault-folders'] })
      queryClient.invalidateQueries({ queryKey: ['vault-items'] })
    },
  })
}

// ─── Tag types ──────────────────────────────────────────────────────────────

interface TagResponse { data: VaultTag[] }
interface SingleTagResponse { data: VaultTag }

interface CreateTagInput {
  name: string
  color?: string | null
}

interface UpdateTagInput {
  name?: string
  color?: string | null
}

// ─── Tag hooks ──────────────────────────────────────────────────────────────

export function useTags() {
  return useQuery<VaultTag[], ApiError>({
    queryKey: ['vault-tags'],
    queryFn: async () => {
      const res = await api.get<TagResponse>('/api/v1/vault/tags')
      return res.data
    },
  })
}

export function useCreateTag() {
  const queryClient = useQueryClient()
  return useMutation<SingleTagResponse, ApiError, CreateTagInput>({
    mutationFn: (data) => api.post<SingleTagResponse>('/api/v1/vault/tags', data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['vault-tags'] }),
  })
}

export function useUpdateTag(id: number) {
  const queryClient = useQueryClient()
  return useMutation<SingleTagResponse, ApiError, UpdateTagInput>({
    mutationFn: (data) => api.put<SingleTagResponse>(`/api/v1/vault/tags/${id}`, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['vault-tags'] })
      queryClient.invalidateQueries({ queryKey: ['vault-items'] })
    },
  })
}

export function useDeleteTag() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (id) => api.delete(`/api/v1/vault/tags/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['vault-tags'] })
      queryClient.invalidateQueries({ queryKey: ['vault-items'] })
    },
  })
}
