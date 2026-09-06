import { useState } from 'react'
import {
  Link2,
  Plus,
  Trash2,
  Activity,
  Eye,
  Clock,
  Lock,
  Mail,
  CheckCircle2,
} from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { CopyButton } from '@/components/shared/CopyButton'
import { CreateSecureLinkDialog } from '@/features/secure-links/components/CreateSecureLinkDialog'
import { LinkActivityDialog } from '@/features/secure-links/components/LinkActivityDialog'
import {
  useSecureLinks,
  useRevokeSecureLink,
} from '@/features/secure-links/hooks/use-secure-links'
import type { SecureLinkResourceType, SecureLink } from '@/types/secure-link'

function formatExpiry(expiresAt: string | null): string {
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

function linkUrl(link: SecureLink): string {
  return `${window.location.origin}/s/${link.uuid}`
}

export function SecureLinksPanel({
  resource,
  id,
}: {
  resource: SecureLinkResourceType
  id: number
}) {
  const { data: links, isLoading } = useSecureLinks(resource, id)
  const revokeLink = useRevokeSecureLink()

  const [showCreateDialog, setShowCreateDialog] = useState(false)
  const [activityLinkId, setActivityLinkId] = useState<number | null>(null)
  const [revokeLink_, setRevokeLink_] = useState<SecureLink | null>(null)

  const handleRevoke = () => {
    if (!revokeLink_) return
    revokeLink.mutate(revokeLink_.id, {
      onSuccess: () => {
        toast.success('Link revoked.')
        setRevokeLink_(null)
      },
      onError: () => toast.error('Failed to revoke link.'),
    })
  }

  if (isLoading) {
    return (
      <Card>
        <CardHeader>
          <Skeleton className="h-6 w-40" />
        </CardHeader>
        <CardContent className="space-y-3">
          <Skeleton className="h-16" />
        </CardContent>
      </Card>
    )
  }

  return (
    <Card>
      <CardHeader>
        <div className="flex items-center justify-between">
          <CardTitle className="flex items-center gap-2">
            <Link2 className="h-5 w-5" />
            Secure Links
            {links && links.length > 0 && (
              <Badge variant="secondary" className="ml-1">
                {links.length}
              </Badge>
            )}
          </CardTitle>
          <Button size="sm" variant="outline" onClick={() => setShowCreateDialog(true)}>
            <Plus className="mr-2 h-4 w-4" />
            Create Link
          </Button>
        </div>
      </CardHeader>
      <CardContent>
        {!links || links.length === 0 ? (
          <div className="flex flex-col items-center gap-2 py-6 text-center">
            <Link2 className="h-8 w-8 text-muted-foreground" />
            <p className="text-muted-foreground text-sm">
              No secure links yet. Create one to share externally.
            </p>
            <Button size="sm" variant="outline" onClick={() => setShowCreateDialog(true)}>
              <Plus className="mr-2 h-4 w-4" />
              Create Link
            </Button>
          </div>
        ) : (
          <div className="space-y-2">
            {links.map((link) => (
              <LinkRow
                key={link.id}
                link={link}
                onActivity={() => setActivityLinkId(link.id)}
                onRevoke={() => setRevokeLink_(link)}
                isRevoking={revokeLink.isPending}
              />
            ))}
          </div>
        )}
      </CardContent>

      <CreateSecureLinkDialog
        resource={resource}
        id={id}
        open={showCreateDialog}
        onOpenChange={setShowCreateDialog}
      />

      <LinkActivityDialog
        linkId={activityLinkId}
        open={activityLinkId !== null}
        onOpenChange={(open) => !open && setActivityLinkId(null)}
      />

      {/* Revoke confirmation */}
      {revokeLink_ && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
          onClick={() => setRevokeLink_(null)}
        >
          <div
            className="rounded-lg bg-background p-6 shadow-lg"
            onClick={(e) => e.stopPropagation()}
          >
            <h3 className="text-lg font-semibold">Revoke this link?</h3>
            <p className="mt-2 text-muted-foreground text-sm">
              The link will immediately stop working. This cannot be undone.
            </p>
            <div className="mt-4 flex justify-end gap-2">
              <Button variant="outline" onClick={() => setRevokeLink_(null)}>
                Cancel
              </Button>
              <Button variant="destructive" onClick={handleRevoke} disabled={revokeLink.isPending}>
                {revokeLink.isPending ? 'Revoking...' : 'Revoke'}
              </Button>
            </div>
          </div>
        </div>
      )}
    </Card>
  )
}

// ─── Link row ───────────────────────────────────────────────────────────────

function LinkRow({
  link,
  onActivity,
  onRevoke,
  isRevoking,
}: {
  link: SecureLink
  onActivity: () => void
  onRevoke: () => void
  isRevoking: boolean
}) {
  const url = linkUrl(link)
  const isExpiredOrRevoked = link.is_expired || link.revoked_at !== null || !link.is_active

  return (
    <div className="rounded-lg border p-3">
      <div className="flex items-center gap-2">
        <code className="min-w-0 flex-1 truncate rounded bg-muted px-2 py-1 text-xs">
          {url}
        </code>
        <CopyButton value={url} />
      </div>

      <div className="mt-2 flex flex-wrap items-center gap-2 text-xs">
        {isExpiredOrRevoked ? (
          <Badge variant="outline" className="text-xs">
            {link.revoked_at ? 'Revoked' : link.is_expired ? 'Expired' : 'Inactive'}
          </Badge>
        ) : (
          <Badge variant="default" className="text-xs">
            <CheckCircle2 className="mr-1 h-3 w-3" />
            Active
          </Badge>
        )}

        <span className="flex items-center gap-1 text-muted-foreground">
          <Clock className="h-3 w-3" />
          {formatExpiry(link.expires_at)}
        </span>

        <span className="flex items-center gap-1 text-muted-foreground">
          <Eye className="h-3 w-3" />
          {link.views_count}
          {link.max_views !== null && `/${link.max_views}`} views
        </span>

        {link.has_password && (
          <span className="flex items-center gap-1 text-muted-foreground">
            <Lock className="h-3 w-3" />
            Password
          </span>
        )}

        {link.is_one_time && (
          <Badge variant="outline" className="text-xs">One-time</Badge>
        )}

        {link.recipient_email && (
          <span className="flex items-center gap-1 text-muted-foreground">
            <Mail className="h-3 w-3" />
            {link.recipient_email}
          </span>
        )}
      </div>

      <div className="mt-2 flex justify-end gap-2">
        <Button size="sm" variant="ghost" onClick={onActivity}>
          <Activity className="mr-1 h-3.5 w-3.5" />
          Activity
        </Button>
        {!isExpiredOrRevoked && (
          <Button
            size="sm"
            variant="ghost"
            onClick={onRevoke}
            disabled={isRevoking}
            className="text-destructive"
          >
            <Trash2 className="h-3.5 w-3.5" />
          </Button>
        )}
      </div>
    </div>
  )
}
