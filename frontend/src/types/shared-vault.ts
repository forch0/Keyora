export interface SharedVaultItem {
  id: number
  team_id: number | null
  user_id: number
  name: string
  type: string
  username: string | null
  password: string | null
  url: string | null
  notes: string | null
  metadata: unknown[] | null
  custom_fields: { key: string; value: string }[] | null
  favorite: boolean
  folder_id: number | null
  created_at: string | null
  updated_at: string | null
}

export interface Team {
  id: number
  name: string
  description: string | null
  color: string | null
  created_by: number
  members_count?: number
  vault_items_count?: number
  created_at: string | null
  updated_at: string | null
}

export interface PaginatedResponse<T> {
  data: T[]
  links: {
    first: string | null
    last: string | null
    prev: string | null
    next: string | null
  }
  meta: {
    current_page: number
    from: number | null
    last_page: number
    links: { url: string | null; label: string; active: boolean }[]
    path: string | null
    per_page: number
    to: number | null
    total: number
  }
}
