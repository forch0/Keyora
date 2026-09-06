import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '@/api/client'
import type { ApiError } from '@/types/api-error'
import type {
  SecureNote,
  NoteFolder,
  NoteListParams,
  CreateNoteInput,
  UpdateNoteInput,
  CreateNoteFolderInput,
  UpdateNoteFolderInput,
  PaginatedNotes,
} from '@/types/secure-note'

// ─── Note queries ───────────────────────────────────────────────────────────

export function useNotes(params: NoteListParams = {}) {
  return useQuery<PaginatedNotes, ApiError>({
    queryKey: ['notes', params],
    queryFn: async () => {
      const res = await api.get<PaginatedNotes>('/api/v1/notes', params as Record<string, unknown>)
      return res
    },
  })
}

export function useNote(id: number) {
  return useQuery<SecureNote, ApiError>({
    queryKey: ['note', id],
    queryFn: async () => {
      const res = await api.get<{ data: SecureNote }>(`/api/v1/notes/${id}`)
      return res.data
    },
    enabled: id > 0,
  })
}

export function useSearchNotes(query: string) {
  return useQuery<SecureNote[], ApiError>({
    queryKey: ['notes', 'search', query],
    queryFn: async () => {
      const res = await api.get<PaginatedNotes>('/api/v1/notes/search', { q: query })
      return res.data ?? []
    },
    enabled: query.length > 0,
  })
}

export function useNoteTrash(params: { page?: number; per_page?: number } = {}) {
  return useQuery<PaginatedNotes, ApiError>({
    queryKey: ['notes', 'trash', params],
    queryFn: async () => {
      const res = await api.get<PaginatedNotes>('/api/v1/notes/trash', params as Record<string, unknown>)
      return res
    },
  })
}

// ─── Note mutations ─────────────────────────────────────────────────────────

export function useCreateNote() {
  const queryClient = useQueryClient()
  return useMutation<{ data: SecureNote }, ApiError, CreateNoteInput>({
    mutationFn: (data) => api.post<{ data: SecureNote }>('/api/v1/notes', data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['notes'] })
    },
  })
}

export function useUpdateNote(id: number) {
  const queryClient = useQueryClient()
  return useMutation<{ data: SecureNote }, ApiError, UpdateNoteInput>({
    mutationFn: (data) => api.put<{ data: SecureNote }>(`/api/v1/notes/${id}`, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['note', id] })
      queryClient.invalidateQueries({ queryKey: ['notes'] })
    },
  })
}

export function useDeleteNote() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (id) => api.delete(`/api/v1/notes/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['notes'] })
    },
  })
}

export function useTogglePin() {
  const queryClient = useQueryClient()
  return useMutation<{ data: SecureNote }, ApiError, number>({
    mutationFn: (id) => api.post<{ data: SecureNote }>(`/api/v1/notes/${id}/pin`),
    onSuccess: (res) => {
      queryClient.invalidateQueries({ queryKey: ['notes'] })
      queryClient.setQueryData(['note', res.data.id], res.data)
    },
  })
}

// ─── Trash mutations ────────────────────────────────────────────────────────

export function useRestoreNoteFromTrash() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (id) => api.post(`/api/v1/notes/trash/${id}/restore`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['notes', 'trash'] })
      queryClient.invalidateQueries({ queryKey: ['notes'] })
    },
  })
}

export function useForceDeleteNote() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (id) => api.delete(`/api/v1/notes/trash/${id}/force`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['notes', 'trash'] })
    },
  })
}

export function useEmptyNoteTrash() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, void>({
    mutationFn: () => api.delete('/api/v1/notes/trash'),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['notes', 'trash'] })
    },
  })
}

// ─── Note folders ───────────────────────────────────────────────────────────

export function useNoteFolders() {
  return useQuery<NoteFolder[], ApiError>({
    queryKey: ['note-folders'],
    queryFn: async () => {
      const res = await api.get<{ data: NoteFolder[] }>('/api/v1/notes/folders')
      return res.data
    },
  })
}

export function useCreateNoteFolder() {
  const queryClient = useQueryClient()
  return useMutation<{ data: NoteFolder }, ApiError, CreateNoteFolderInput>({
    mutationFn: (data) => api.post<{ data: NoteFolder }>('/api/v1/notes/folders', data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['note-folders'] })
    },
  })
}

export function useUpdateNoteFolder() {
  const queryClient = useQueryClient()
  return useMutation<{ data: NoteFolder }, ApiError, { id: number; data: UpdateNoteFolderInput }>({
    mutationFn: ({ id, data }) => api.put<{ data: NoteFolder }>(`/api/v1/notes/folders/${id}`, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['note-folders'] })
    },
  })
}

export function useDeleteNoteFolder() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (id) => api.delete(`/api/v1/notes/folders/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['note-folders'] })
      queryClient.invalidateQueries({ queryKey: ['notes'] })
    },
  })
}
