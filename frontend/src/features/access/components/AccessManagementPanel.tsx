import { useState } from 'react'
import {
  User as UserIcon,
  Users,
  Building2,
  Trash2,
  Plus,
  Shield,
  Clock,
  Eye,
  AlertCircle,
} from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { GrantAccessDialog } from '@/features/access/components/GrantAccessDialog'
import {
  useAccessGrants,
  useAccessSummary,
  useUpdateGrant,
  useRevokeGrant,
  useRevokeAllAccess,
} from '@/features/access/hooks/use-access'
import type { ResourceType, Permission, AccessGrant } from '@/types/access'

const PERMISSION_LABELS: Record<string, string> = {
  view: 'View',
  download: 'Download',
  edit: 'Edit',
  share: 'Share',
  manage: 'Manage',
}

const PERMISSIONS: Permission[] = ['view', 'download', 'edit', 'share', 'manage']

function subjectIcon(type: string) {
  if (type === 'App\\Models\\Team') return Users
  if (type === 'App\\Models\\Tenant') return Building2
  return UserIcon
}

function subjectLabel(type: string) {
  if (type === 'App\\Models\\Team') return 'Team'
  if (type === 'App\\Models\\Tenant') return 'Workspace'
  return 'User'
}

function formatExpiry(expiresAt: string | null): string {
  if (!expiresAt) return 'No expiry'
  const date = new Date(expiresAt)
  const now = new Date()
  const diffMs = date.getTime() - now.getTime()
  if (diffMs <= 0) return 'Expired'
  const diffH = Math.floor(diffMs / (1000 * 60 * 60))
  if (diffH < 1) return `< 1 hour left`
  if (diffH < 24) return `${diffH}h left`
  const diffD = Math.floor(diffH / 24)
  return `${diffD}d left`
}

export function AccessManagementPanel({
  resource,
  id,
}: {
  resource: ResourceType
  id: number
}) {
  const { data: grants, isLoading } = useAccessGrants(resource, id)
  const { data: summary } = useAccessSummary(resource, id)
  const revokeAll = useRevokeAllAccess(resource, id)

  const [showGrantDialog, setShowGrantDialog] = useState(false)
  const [showRevokeAllDialog, setShowRevokeAllDialog] = useState(false)

  const handleRevokeAll = () => {
    revokeAll.mutate(undefined, {
      onSuccess: (res) => {
        toast.success(`Revoked ${res.revoked_count} grant(s).`)
        setShowRevokeAllDialog(false)
      },
      onError: () => toast.error('Failed to revoke all access.'),
    })
  }

  if (isLoading) {
    return (
      <Card>
        <CardHeader>
          <Skeleton className="h-6 w-40" />
        </CardHeader>
        <CardContent className="space-y-3">
          {Array.from({ length: 2 }).map((_, i) => (
            <Skeleton key={i} className="h-16" />
          ))}
        </CardContent>
      </Card>
    )
  }

  return (
    <Card>
      <CardHeader>
        <div className="flex items-center justify-between">
          <CardTitle className="flex items-center gap-2">
            <Shield className="h-5 w-5" />
            Access Management
            {summary && (
              <Badge variant="secondary" className="ml-1">
                {summary.total} grant{summary.total !== 1 ? 's' : ''}
              </Badge>
            )}
          </CardTitle>
          <div className="flex gap-2">
            <Button size="sm" variant="outline" onClick={() => setShowGrantDialog(true)}>
              <Plus className="mr-2 h-4 w-4" />
              Grant
            </Button>
            {grants && grants.length > 0 && (
              <Button
                size="sm"
                variant="destructive"
                onClick={() => setShowRevokeAllDialog(true)}
              >
                <Trash2 className="mr-2 h-4 w-4" />
                Revoke All
              </Button>
            )}
          </div>
        </div>
      </CardHeader>
      <CardContent>
        {!grants || grants.length === 0 ? (
          <div className="flex flex-col items-center gap-2 py-8 text-center">
            <Shield className="h-8 w-8 text-muted-foreground" />
            <p className="text-muted-foreground text-sm">
              No one else has access to this resource yet.
            </p>
            <Button size="sm" variant="outline" onClick={() => setShowGrantDialog(true)}>
              <Plus className="mr-2 h-4 w-4" />
              Grant Access
            </Button>
          </div>
        ) : (
          <div className="space-y-2">
            {grants.map((grant) => (
              <GrantRow key={grant.id} resource={resource} id={id} grant={grant} />
            ))}
          </div>
        )}
      </CardContent>

      {/* Grant dialog */}
      <GrantAccessDialog
        resource={resource}
        id={id}
        open={showGrantDialog}
        onOpenChange={setShowGrantDialog}
      />

      {/* Revoke all dialog */}
      <Dialog open={showRevokeAllDialog} onOpenChange={setShowRevokeAllDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Revoke all access?</DialogTitle>
            <DialogDescription>
              This will immediately revoke all access grants for this resource. This action
              cannot be undone.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setShowRevokeAllDialog(false)}>
              Cancel
            </Button>
            <Button
              variant="destructive"
              onClick={handleRevokeAll}
              disabled={revokeAll.isPending}
            >
              {revokeAll.isPending ? 'Revoking...' : 'Revoke All'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </Card>
  )
}

// ─── Grant row ──────────────────────────────────────────────────────────────

function GrantRow({
  resource,
  id,
  grant,
}: {
  resource: ResourceType
  id: number
  grant: AccessGrant
}) {
  const updateGrant = useUpdateGrant(resource, id)
  const revokeGrant = useRevokeGrant(resource, id)
  const Icon = subjectIcon(grant.subject_type)

  const handlePermissionChange = (newPerm: Permission) => {
    updateGrant.mutate(
      { grantId: grant.id, data: { permission: newPerm, expires_at: grant.expires_at, max_views: grant.max_views } },
      {
        onSuccess: () => toast.success('Permission updated.'),
        onError: () => toast.error('Failed to update permission.'),
      },
    )
  }

  const handleRevoke = () => {
    revokeGrant.mutate(grant.id, {
      onSuccess: () => toast.success('Access revoked.'),
      onError: () => toast.error('Failed to revoke access.'),
    })
  }

  const subjectName = String(grant.subject_name ?? 'Unknown')
  const isExpiringSoon = grant.expires_at
    ? new Date(grant.expires_at).getTime() - Date.now() < 60 * 60 * 1000
    : false

  return (
    <div className="flex items-center gap-3 rounded-lg border p-3">
      <div className="rounded-md bg-muted p-2">
        <Icon className="h-4 w-4" />
      </div>
      <div className="min-w-0 flex-1">
        <div className="flex items-center gap-2">
          <p className="truncate font-medium text-sm">{subjectName}</p>
          <Badge variant="outline" className="text-xs">
            {subjectLabel(grant.subject_type)}
          </Badge>
          {!grant.is_active && (
            <Badge variant="destructive" className="text-xs">Inactive</Badge>
          )}
        </div>
        <div className="mt-0.5 flex flex-wrap items-center gap-3 text-muted-foreground text-xs">
          <span className="flex items-center gap-1">
            <Clock className="h-3 w-3" />
            {formatExpiry(grant.expires_at)}
            {isExpiringSoon && grant.expires_at && (
              <AlertCircle className="h-3 w-3 text-orange-500" />
            )}
          </span>
          {grant.max_views !== null && (
            <span className="flex items-center gap-1">
              <Eye className="h-3 w-3" />
              {grant.views_count}/{grant.max_views} views
            </span>
          )}
          {grant.start_on_first_view && (
            <span className="text-xs">Starts on first view</span>
          )}
          <span>by {grant.granted_by}</span>
        </div>
      </div>

      <Select value={grant.permission} onValueChange={(v) => handlePermissionChange(v as Permission)}>
        <SelectTrigger className="h-8 w-28">
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          {PERMISSIONS.map((p) => (
            <SelectItem key={p} value={p}>
              {PERMISSION_LABELS[p]}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>

      <Button
        variant="ghost"
        size="sm"
        onClick={handleRevoke}
        disabled={revokeGrant.isPending}
        className="text-destructive"
      >
        <Trash2 className="h-4 w-4" />
      </Button>
    </div>
  )
}
