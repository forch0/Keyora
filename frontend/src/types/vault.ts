export interface VaultItemTag {
  id: number
  name: string
  color: string | null
}

export interface VaultItemCustomField {
  key: string
  value: string
}

export interface VaultItem {
  id: number
  name: string
  type: string
  username: string | null
  password: string | null
  url: string | null
  notes: string | null
  metadata: unknown[] | null
  custom_fields: VaultItemCustomField[] | null
  favorite: boolean
  folder_id: number | null
  last_accessed_at: string | null
  archived_at: string | null
  tags?: VaultItemTag[]
  created_at: string | null
  updated_at: string | null
}

export interface PaginationMeta {
  current_page: number
  from: number | null
  last_page: number
  per_page: number
  to: number | null
  total: number
}

export interface PaginationLinks {
  first: string | null
  last: string | null
  prev: string | null
  next: string | null
}

export interface PaginatedVaultItems {
  data: VaultItem[]
  links: PaginationLinks
  meta: PaginationMeta
}

export type ItemType =
  | 'password'
  | 'api_key'
  | 'server_credential'
  | 'database_credential'
  | string

export interface VaultItemListParams {
  page?: number
  per_page?: number
  sort?: string
  type?: string
  favorite?: boolean
  archived?: boolean
}
