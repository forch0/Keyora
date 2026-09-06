export interface SecureNote {
  id: number
  title: string
  content: string
  content_format: string
  is_pinned: boolean
  folder_id: number | null
  team_id: number | null
  tenant_id: number | null
  created_by: string
  created_by_id: number
  tags?: {
    id: number
    name: string
    color: string | null
  }[]
  created_at: string | null
  updated_at: string | null
}

export interface NoteFolder {
  id: number
  name: string
  team_id: number | null
  parent_id: number | null
  created_by: string
  created_at: string | null
  children_count: number
  notes_count: number
}

export interface NoteListParams {
  page?: number
  per_page?: number
  team_id?: number
  folder_id?: number
}

export interface CreateNoteInput {
  title: string
  content: string
  content_format?: 'markdown' | 'html' | null
  team_id?: number | null
  folder_id?: number | null
  is_pinned?: boolean | null
}

export interface UpdateNoteInput {
  title?: string
  content?: string
  content_format?: 'markdown' | 'html' | null
  folder_id?: number | null
  is_pinned?: boolean
}

export interface CreateNoteFolderInput {
  name: string
  team_id?: number | null
  parent_id?: number | null
}

export interface UpdateNoteFolderInput {
  name?: string
  parent_id?: number | null
}

export interface PaginatedNotes {
  data: SecureNote[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}
