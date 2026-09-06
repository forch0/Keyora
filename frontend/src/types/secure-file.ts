export interface SecureFile {
  id: number
  name: string
  mime_type: string
  size: string
  size_bytes: number
  checksum: string
  description: string | null
  download_enabled: boolean
  expires_at: string | null
  archived_at: string | null
  folder_id: number | null
  team_id: number | null
  uploaded_by: string
  uploaded_by_id: number
  download_url: string
  created_at: string | null
  updated_at: string | null
}

export interface FileFolder {
  id: number
  name: string
  team_id: number | null
  parent_id: number | null
  created_by: string
  created_at: string | null
  children_count: number
  files_count: number
}

export interface FileListParams {
  page?: number
  per_page?: number
  team_id?: number
  folder_id?: number
}

export interface UploadFileInput {
  file: File
  team_id?: number | null
  folder_id?: number | null
  description?: string | null
}

export interface BulkUploadFilesInput {
  files: File[]
  team_id?: number | null
  folder_id?: number | null
  description?: string | null
}

export interface UpdateFileInput {
  name?: string
  description?: string | null
  folder_id?: number | null
  download_enabled?: boolean
  expires_at?: string | null
}

export interface CreateFileFolderInput {
  name: string
  team_id?: number | null
  parent_id?: number | null
}

export interface UpdateFileFolderInput {
  name?: string
  parent_id?: number | null
}

export interface PaginatedFiles {
  data: SecureFile[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}
