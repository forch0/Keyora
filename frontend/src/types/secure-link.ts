export type SecureLinkResourceType = 'vault/items' | 'files' | 'notes'

export interface SecureLink {
  id: number
  uuid: string
  url: string
  resource: {
    type: string
    id: number
    name: unknown
  }
  recipient_email: string | null
  permission: string
  download_enabled: boolean
  expires_at: string | null
  first_view_expires_hours: number | null
  max_views: number | null
  views_count: number
  is_one_time: boolean
  is_active: boolean
  is_expired: boolean
  first_viewed_at: string | null
  revoked_at: string | null
  revoke_reason: string | null
  has_password: boolean
  requires_otp: boolean
  requires_email_verification: boolean
  otp_code?: string
  created_at: string | null
}

export interface SecureLinkAccess {
  id: number
  ip_address: string
  user_agent: string
  email: string | null
  accessed_at: string | null
}

export interface CreateSecureLinkInput {
  recipient_email?: string | null
  password?: string | null
  require_otp?: boolean | null
  require_email_verification?: boolean | null
  permission: 'view' | 'download'
  download_enabled?: boolean | null
  expires_at?: string | null
  first_view_expires_hours?: number | null
  max_views?: number | null
  is_one_time?: boolean | null
}

// Public link types
export interface PublicLinkInfo {
  uuid: string
  resource_type: string
  resource_name: unknown
  requires_password: boolean
  requires_otp: boolean
  requires_email_verification: boolean
  is_expired: boolean
  is_revoked: boolean
  views_remaining: number | null
  expires_at: string | null
}

export interface PublicLinkResource {
  resource_type: string
  resource_id: unknown
  name: unknown
  content: string | null
  permission: string
  download_enabled: boolean
}
