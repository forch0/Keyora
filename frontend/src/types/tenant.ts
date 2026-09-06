export interface Tenant {
  id: number
  name: string
  slug: string
  plan: string
  settings: unknown[] | null
  trial_ends_at: string | null
  role?: string | null
  created_at: string | null
}
