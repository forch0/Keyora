export interface SecurityAlert {
  id: number
  type: string
  severity: string
  title: string
  message: string
  properties: unknown[] | null
  read_at: string | null
  dismissed_at: string | null
  created_at: string | null
}

export interface UnreadAlertCount {
  count: number
}

export interface PaginatedSecurityAlerts {
  data: SecurityAlert[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}

export interface Device {
  id: number
  browser: string
  os: string
  device_type: string
  ip_address: string
  last_seen_at: string
  first_seen_at: string
  is_current_device?: string | boolean
}
