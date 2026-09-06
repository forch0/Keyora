import { Link } from 'react-router-dom'
import {
  Clock,
  AlertTriangle,
  Link2,
  KeyRound,
  File as FileIcon,
  StickyNote,
  ArrowRight,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import { EmptyState } from '@/components/shared/EmptyState'
import { useExpiringAccess } from '@/features/search/hooks/use-search'
import type { ExpiringAccessGrant, ExpiringSecureLink } from '@/types/search'

function resourcePath(type: string, id: number): string {
  if (type.includes('VaultItem')) return `/shared/items/${id}`
  if (type.includes('SecureFile')) return `/files/${id}`
  if (type.includes('SecureNote')) return `/notes/${id}`
  return '#'
}

function resourceLabel(type: string): string {
  if (type.includes('VaultItem')) return 'Vault Item'
  if (type.includes('SecureFile')) return 'File'
  if (type.includes('SecureNote')) return 'Note'
  return type
}

function resourceIcon(type: string) {
  if (type.includes('VaultItem')) return KeyRound
  if (type.includes('SecureFile')) return FileIcon
  if (type.includes('SecureNote')) return StickyNote
  return KeyRound
}

function timeUntil(expiresAt: string | null): string {
  if (!expiresAt) return 'No expiry'
  const date = new Date(expiresAt)
  const now = new Date()
  const diffMs = date.getTime() - now.getTime()
  if (diffMs <= 0) return 'Expired'
  const diffH = Math.floor(diffMs / (1000 * 60 * 60))
  if (diffH < 1) return '< 1 hour'
  if (diffH < 24) return `${diffH}h`
  const diffD = Math.floor(diffH / 24)
  return `${diffD}d`
}

function isExpired(expiresAt: string | null): boolean {
  if (!expiresAt) return false
  return new Date(expiresAt).getTime() < Date.now()
}

export function ExpiringAccessPage() {
  const { data, isLoading } = useExpiringAccess()

  const grants = data?.access_grants ?? []
  const links = data?.secure_links ?? []

  // Sort by expiration time
  const sortedGrants = [...grants].sort(
    (a, b) =>
      new Date(a.expires_at ?? '9999').getTime() -
      new Date(b.expires_at ?? '9999').getTime(),
  )
  const sortedLinks = [...links].sort(
    (a, b) =>
      new Date(a.expires_at ?? '9999').getTime() -
      new Date(b.expires_at ?? '9999').getTime(),
  )

  const hasItems = sortedGrants.length > 0 || sortedLinks.length > 0

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">Expiring Access</h1>
        <p className="text-muted-foreground text-sm">
          Resources where your access is expiring soon
        </p>
      </div>

      {isLoading ? (
        <div className="space-y-2">
          {Array.from({ length: 3 }).map((_, i) => (
            <Skeleton key={i} className="h-16" />
          ))}
        </div>
      ) : !hasItems ? (
        <EmptyState
          icon={Clock}
          title="No expiring access"
          description="You have no access grants or links expiring soon."
        />
      ) : (
        <div className="space-y-6">
          {/* Access grants */}
          {sortedGrants.length > 0 && (
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <KeyRound className="h-5 w-5" />
                  Access Grants ({sortedGrants.length})
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-2">
                {sortedGrants.map((grant) => (
                  <ExpiringGrantRow key={grant.id} grant={grant} />
                ))}
              </CardContent>
            </Card>
          )}

          {/* Secure links */}
          {sortedLinks.length > 0 && (
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Link2 className="h-5 w-5" />
                  Secure Links ({sortedLinks.length})
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-2">
                {sortedLinks.map((link) => (
                  <ExpiringLinkRow key={link.id} link={link} />
                ))}
              </CardContent>
            </Card>
          )}
        </div>
      )}
    </div>
  )
}

function ExpiringGrantRow({ grant }: { grant: ExpiringAccessGrant }) {
  const Icon = resourceIcon(grant.resource_type)
  const path = resourcePath(grant.resource_type, grant.resource_id)
  const expired = isExpired(grant.expires_at)
  const time = timeUntil(grant.expires_at)

  return (
    <div className="flex items-center gap-3 rounded-lg border p-3">
      <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded bg-muted">
        <Icon className="h-4 w-4 text-muted-foreground" />
      </div>
      <div className="min-w-0 flex-1">
        <div className="flex items-center gap-2">
          <span className="text-sm font-medium">
            {resourceLabel(grant.resource_type)} #{grant.resource_id}
          </span>
          <Badge variant={expired ? 'destructive' : 'outline'} className="text-xs">
            {expired ? 'Expired' : `Expires in ${time}`}
          </Badge>
        </div>
        <p className="text-muted-foreground text-xs">
          {grant.expires_at ? new Date(grant.expires_at).toLocaleString() : 'No expiry set'}
        </p>
      </div>
      <div className="flex gap-2">
        <Button variant="outline" size="sm" asChild>
          <Link to={path}>
            View
            <ArrowRight className="ml-1 h-3.5 w-3.5" />
          </Link>
        </Button>
        <Button variant="ghost" size="sm" asChild>
          <Link to="/access-requests">
            <AlertTriangle className="mr-1 h-3.5 w-3.5" />
            Request Extension
          </Link>
        </Button>
      </div>
    </div>
  )
}

function ExpiringLinkRow({ link }: { link: ExpiringSecureLink }) {
  const Icon = resourceIcon(link.resource_type)
  const path = resourcePath(link.resource_type, link.resource_id)
  const expired = isExpired(link.expires_at)
  const time = timeUntil(link.expires_at)

  return (
    <div className="flex items-center gap-3 rounded-lg border p-3">
      <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded bg-muted">
        <Icon className="h-4 w-4 text-muted-foreground" />
      </div>
      <div className="min-w-0 flex-1">
        <div className="flex items-center gap-2">
          <span className="text-sm font-medium">
            {resourceLabel(link.resource_type)} #{link.resource_id}
          </span>
          <Badge variant={expired ? 'destructive' : 'outline'} className="text-xs">
            {expired ? 'Expired' : `Expires in ${time}`}
          </Badge>
        </div>
        <p className="text-muted-foreground text-xs">
          {link.expires_at ? new Date(link.expires_at).toLocaleString() : 'No expiry set'}
        </p>
      </div>
      <Button variant="outline" size="sm" asChild>
        <Link to={path}>
          View
          <ArrowRight className="ml-1 h-3.5 w-3.5" />
        </Link>
      </Button>
    </div>
  )
}
