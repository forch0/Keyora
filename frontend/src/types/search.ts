export interface SearchResultVaultItem {
  id: number
  name: string
  type: string
  url: string | null
}

export interface SearchResultFile {
  id: number
  name: string
  mime_type: unknown
}

export interface SearchResultNote {
  id: number
  title: string
}

export interface SearchResultPerson {
  id: number
  name: string
  email: string
}

export interface SearchResultTeam {
  id: number
  name: string
}

export interface SearchResults {
  vault_items: SearchResultVaultItem[]
  files: SearchResultFile[]
  notes: SearchResultNote[]
  people: SearchResultPerson[] | string[]
  teams: SearchResultTeam[] | string[]
}

export interface RecentCreatedItem {
  id: number
  name: string
  type: 'vault_item' | 'file' | 'note'
  created_at: string | null
}

export interface RecentCreatedResults {
  vault_items: {
    id: number
    name: string
    type: 'vault_item'
    created_at: string | null
  }[]
  files: {
    id: number
    name: string
    type: 'file'
    created_at: string | null
  }[]
  notes: {
    id: number
    title: string
    type: 'note'
    created_at: string | null
  }[]
}

export interface ExpiringAccessGrant {
  id: number
  resource_type: string
  resource_id: number
  expires_at: string | null
}

export interface ExpiringSecureLink {
  id: number
  uuid: string
  resource_type: string
  resource_id: number
  expires_at: string | null
}

export interface ExpiringAccessResults {
  access_grants: ExpiringAccessGrant[]
  secure_links: ExpiringSecureLink[]
}
