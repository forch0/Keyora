export interface VaultFolder {
  id: number
  name: string
  parent_id: number | null
  icon: string | null
  color: string | null
  sort_order: number
  items_count?: number
  children?: VaultFolder[]
  created_at: string | null
  updated_at: string | null
}

export interface VaultTag {
  id: number
  name: string
  color: string | null
  items_count?: number
  created_at: string | null
  updated_at: string | null
}
