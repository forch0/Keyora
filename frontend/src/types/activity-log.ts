export interface ActivityLog {
  id: number
  action: string
  user?: {
    id: number | null
    name: string | null
  }
  subject?: {
    type: string | null
    id: number | null
  }
  properties: unknown[] | null
  ip_address: string | null
  created_at: string
}

export interface PaginatedActivityLogs {
  data: ActivityLog[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}

export type ActivityLogResourceType = 'vault/items' | 'files'
