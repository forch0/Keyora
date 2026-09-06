export type ResourceType = 'vault/items' | 'files' | 'notes'
export type SubjectType = 'App\\Models\\User' | 'App\\Models\\Team' | 'App\\Models\\Tenant'
export type Permission = 'view' | 'download' | 'edit' | 'share' | 'manage'
export type Duration = '15m' | '30m' | '1h' | '24h' | null

export interface AccessGrant {
  id: number
  subject_type: string
  subject_id: number
  subject_name: unknown
  permission: string
  expires_at: string | null
  max_views: number | null
  views_count: number
  starts_at: string | null
  start_on_first_view: boolean
  first_viewed_at: string | null
  granted_by: string
  granted_by_id: number
  created_at: string | null
  is_active: boolean
}

export interface AccessSummary {
  total: number
  groups: string
}

export interface AccessCountdown {
  grant_id: number
  expires_at: string | null
  seconds_remaining: number | null
  views_remaining: number | null
  max_views: number | null
  views_count: number
  starts_at: string | null
  start_on_first_view: boolean
  first_viewed_at: string | null
}

export interface RevokeResult {
  resource_type: string
  resource_id: number
  revoked_count: number
  reason: string
}

export interface RevokeTeamResult extends RevokeResult {
  team_id: number
}

export interface GrantAccessInput {
  subject_type: SubjectType
  subject_id: number
  permission: Permission
  duration?: Duration
  expires_at?: string | null
  max_views?: number | null
  start_on_first_view?: boolean | null
  starts_at?: string | null
}

export interface BulkGrantInput {
  team_ids: number[]
  permission: Permission
}

export interface UpdateGrantInput {
  permission: Permission
  expires_at?: string | null
  max_views?: number | null
}
