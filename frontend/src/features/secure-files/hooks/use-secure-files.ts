import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '@/api/client'
import { useAuthStore } from '@/stores/auth-store'
import type { ApiError } from '@/types/api-error'
import type {
  SecureFile,
  FileFolder,
  FileListParams,
  UploadFileInput,
  BulkUploadFilesInput,
  UpdateFileInput,
  CreateFileFolderInput,
  UpdateFileFolderInput,
  PaginatedFiles,
} from '@/types/secure-file'

// ─── File queries ───────────────────────────────────────────────────────────

export function useFiles(params: FileListParams = {}) {
  return useQuery<PaginatedFiles, ApiError>({
    queryKey: ['files', params],
    queryFn: async () => {
      const res = await api.get<PaginatedFiles>('/api/v1/files', params as Record<string, unknown>)
      return res
    },
  })
}

export function useFile(id: number) {
  return useQuery<SecureFile, ApiError>({
    queryKey: ['file', id],
    queryFn: async () => {
      const res = await api.get<{ data: SecureFile }>(`/api/v1/files/${id}`)
      return res.data
    },
    enabled: id > 0,
  })
}

export function useFileTrash(params: { page?: number; per_page?: number } = {}) {
  return useQuery<PaginatedFiles, ApiError>({
    queryKey: ['files', 'trash', params],
    queryFn: async () => {
      const res = await api.get<PaginatedFiles>('/api/v1/files/trash', params as Record<string, unknown>)
      return res
    },
  })
}

// ─── File mutations ─────────────────────────────────────────────────────────

export function useUploadFile() {
  const queryClient = useQueryClient()
  return useMutation<{ data: SecureFile }, ApiError, UploadFileInput>({
    mutationFn: (input) => {
      const formData = new FormData()
      formData.append('file', input.file)
      if (input.team_id !== null && input.team_id !== undefined)
        formData.append('team_id', String(input.team_id))
      if (input.folder_id !== null && input.folder_id !== undefined)
        formData.append('folder_id', String(input.folder_id))
      if (input.description) formData.append('description', input.description)
      return api.postForm<{ data: SecureFile }>('/api/v1/files', formData)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['files'] })
    },
  })
}

export function useBulkUploadFiles() {
  const queryClient = useQueryClient()
  return useMutation<{ data: SecureFile[] }, ApiError, BulkUploadFilesInput>({
    mutationFn: (input) => {
      const formData = new FormData()
      input.files.forEach((file) => formData.append('files[]', file))
      if (input.team_id !== null && input.team_id !== undefined)
        formData.append('team_id', String(input.team_id))
      if (input.folder_id !== null && input.folder_id !== undefined)
        formData.append('folder_id', String(input.folder_id))
      if (input.description) formData.append('description', input.description)
      return api.postForm<{ data: SecureFile[] }>('/api/v1/files/bulk', formData)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['files'] })
    },
  })
}

export function useDownloadFile() {
  return useMutation<Blob, ApiError, number>({
    mutationFn: async (id) => {
      const { token } = useAuthStore.getState()
      const headers: Record<string, string> = {}
      if (token) headers['Authorization'] = `Bearer ${token}`
      const res = await fetch(`/api/v1/files/${id}/download`, { headers })
      if (!res.ok) throw { status: res.status, message: 'Download failed' } as ApiError
      return res.blob()
    },
  })
}

export function useUpdateFile(id: number) {
  const queryClient = useQueryClient()
  return useMutation<{ data: SecureFile }, ApiError, UpdateFileInput>({
    mutationFn: (data) => api.put<{ data: SecureFile }>(`/api/v1/files/${id}`, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['file', id] })
      queryClient.invalidateQueries({ queryKey: ['files'] })
    },
  })
}

export function useReplaceFile(id: number) {
  const queryClient = useQueryClient()
  const mutation = useMutation<{ data: SecureFile }, ApiError, File>({
    mutationFn: (file) => {
      const formData = new FormData()
      formData.append('file', file)
      return api.postForm<{ data: SecureFile }>(`/api/v1/files/${id}/replace`, formData)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['file', id] })
      queryClient.invalidateQueries({ queryKey: ['files'] })
    },
  })

  return mutation
}

export function useDeleteFile() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (id) => api.delete(`/api/v1/files/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['files'] })
    },
  })
}

export function useArchiveFile() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (id) => api.post(`/api/v1/files/${id}/archive`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['files'] })
    },
  })
}

export function useRestoreFile() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (id) => api.post(`/api/v1/files/${id}/restore`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['files'] })
    },
  })
}

// ─── Trash mutations ────────────────────────────────────────────────────────

export function useRestoreFromTrash() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (id) => api.post(`/api/v1/files/trash/${id}/restore`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['files', 'trash'] })
      queryClient.invalidateQueries({ queryKey: ['files'] })
    },
  })
}

export function useForceDeleteFile() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (id) => api.delete(`/api/v1/files/trash/${id}/force`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['files', 'trash'] })
    },
  })
}

export function useEmptyTrash() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, void>({
    mutationFn: () => api.delete('/api/v1/files/trash'),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['files', 'trash'] })
    },
  })
}

// ─── File folders ───────────────────────────────────────────────────────────

export function useFileFolders() {
  return useQuery<FileFolder[], ApiError>({
    queryKey: ['file-folders'],
    queryFn: async () => {
      const res = await api.get<{ data: FileFolder[] }>('/api/v1/files/folders')
      return res.data
    },
  })
}

export function useCreateFileFolder() {
  const queryClient = useQueryClient()
  return useMutation<{ data: FileFolder }, ApiError, CreateFileFolderInput>({
    mutationFn: (data) => api.post<{ data: FileFolder }>('/api/v1/files/folders', data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['file-folders'] })
    },
  })
}

export function useUpdateFileFolder() {
  const queryClient = useQueryClient()
  return useMutation<{ data: FileFolder }, ApiError, { id: number; data: UpdateFileFolderInput }>({
    mutationFn: ({ id, data }) => api.put<{ data: FileFolder }>(`/api/v1/files/folders/${id}`, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['file-folders'] })
    },
  })
}

export function useDeleteFileFolder() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (id) => api.delete(`/api/v1/files/folders/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['file-folders'] })
      queryClient.invalidateQueries({ queryKey: ['files'] })
    },
  })
}
