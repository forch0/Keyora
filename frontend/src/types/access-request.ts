export type AccessRequestResourceType = 'App\\Models\\VaultItem' | 'App\\Models\\SecureFile' | 'App\\Models\\SecureNote'
export type RequestPermission = 'view' | 'download' | 'edit' | 'share' | 'manage'
export type RequestDuration = '15m' | '30m' | '1h' | '24h' | '7d' | '30d' | 'permanent' | null
export type RequestStatus = 'pending' | 'approved' | 'rejected' | 'cancelled' | 'expired'
export type RequestDirection = 'sent' | 'received'

export interface AccessRequest {
  id: number
  resource: {
    type: string
    id: number
    name: unknown
  }
  requester: {
    id: number
    name: string
  }
  resource_owner: {
    id: number
    name: string
  }
  requested_permission: string
  granted_permission: string | null
  requested_duration: string | null
  granted_expires_at: string | null
  reason: string
  status: string
  review_note: string | null
  reviewed_by: string | null
  reviewed_at: string | null
  created_at: string | null
}

export interface CreateAccessRequestInput {
  resource_type: AccessRequestResourceType
  resource_id: number
  requested_permission: RequestPermission
  requested_duration?: RequestDuration
  reason: string
}

export interface ApproveAccessRequestInput {
  granted_permission?: RequestPermission | null
  granted_duration?: RequestDuration | null
  review_note?: string | null
}

export interface RejectAccessRequestInput {
  review_note?: string | null
}
