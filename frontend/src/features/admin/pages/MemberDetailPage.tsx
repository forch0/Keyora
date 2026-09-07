import { useState } from 'react'
import { useParams, Link, useNavigate } from 'react-router-dom'
import {
  ArrowLeft,
  Shield,
  Ban,
  RotateCcw,
  Trash2,
  AlertTriangle,
  UserCog,
  Activity,
} from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import {
  useTenantMember,
  useChangeMemberRole,
  useSuspendMember,
  useRestoreMember,
  useRemoveMember,
  useRevokeAllForUser,
} from '@/features/admin/hooks/use-admin'

export function MemberDetailPage() {
  const { id } = useParams<{ id: string }>()
  const userId = parseInt(id ?? '0', 10)
  const navigate = useNavigate()

  const { data: member, isLoading } = useTenantMember(userId)
  const roleMutation = useChangeMemberRole()
  const suspendMutation = useSuspendMember()
  const restoreMutation = useRestoreMember()
  const removeMutation = useRemoveMember()
  const revokeAllMutation = useRevokeAllForUser()

  const [confirmingRevoke, setConfirmingRevoke] = useState(false)
  const [confirmingRemove, setConfirmingRemove] = useState(false)

  const isOwner = member?.role === 'owner'
  const isSuspended = member?.status === 'suspended'

  const handleRoleChange = (role: 'admin' | 'member') => {
    roleMutation.mutate(
      { userId, role },
      {
        onSuccess: () => toast.success(`Role changed to ${role}.`),
        onError: () => toast.error('Failed to change role.'),
      },
    )
  }

  const handleSuspend = () => {
    suspendMutation.mutate(userId, {
      onSuccess: () => toast.success('Member suspended.'),
      onError: () => toast.error('Failed to suspend member.'),
    })
  }

  const handleRestore = () => {
    restoreMutation.mutate(userId, {
      onSuccess: () => toast.success('Member restored.'),
      onError: () => toast.error('Failed to restore member.'),
    })
  }

  const handleRemove = () => {
    removeMutation.mutate(userId, {
      onSuccess: () => {
        toast.success('Member removed.')
        navigate('/admin/members')
      },
      onError: () => toast.error('Failed to remove member.'),
    })
  }

  const handleRevokeAll = () => {
    revokeAllMutation.mutate(userId, {
      onSuccess: (res) => {
        toast.success(`${res.revoked} access grant(s) revoked.`)
        setConfirmingRevoke(false)
      },
      onError: () => toast.error('Failed to revoke access.'),
    })
  }

  if (isLoading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-32" />
        <Skeleton className="h-48" />
      </div>
    )
  }

  if (!member) {
    return (
      <div className="py-12 text-center">
        <p className="text-muted-foreground">Member not found.</p>
        <Button variant="link" asChild>
          <Link to="/admin/members">Back to Members</Link>
        </Button>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" asChild>
          <Link to="/admin/members">
            <ArrowLeft className="mr-1 h-4 w-4" />
            Members
          </Link>
        </Button>
      </div>

      {/* Header */}
      <div className="flex items-start gap-4">
        <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-muted">
          <span className="text-xl font-bold">
            {member.name.charAt(0).toUpperCase()}
          </span>
        </div>
        <div className="min-w-0 flex-1">
          <h1 className="text-2xl font-bold">{member.name}</h1>
          <div className="mt-1 flex flex-wrap items-center gap-2 text-muted-foreground text-sm">
            <span>{member.email}</span>
            <span>·</span>
            {member.role === 'owner' ? (
              <Badge variant="default">Owner</Badge>
            ) : member.role === 'admin' ? (
              <Badge variant="secondary">Admin</Badge>
            ) : (
              <Badge variant="outline">Member</Badge>
            )}
            {isSuspended && <Badge variant="destructive">Suspended</Badge>}
          </div>
          {member.joined_at && (
            <p className="mt-1 text-muted-foreground text-xs">
              Joined {new Date(member.joined_at).toLocaleString()}
            </p>
          )}
        </div>
      </div>

      {/* Role management */}
      {!isOwner && (
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <UserCog className="h-5 w-5" />
              Role
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-2">
              <Button
                variant={member.role === 'member' ? 'default' : 'outline'}
                size="sm"
                onClick={() => handleRoleChange('member')}
                disabled={roleMutation.isPending || member.role === 'member'}
              >
                Member
              </Button>
              <Button
                variant={member.role === 'admin' ? 'default' : 'outline'}
                size="sm"
                onClick={() => handleRoleChange('admin')}
                disabled={roleMutation.isPending || member.role === 'admin'}
              >
                Admin
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Status management */}
      {!isOwner && (
        <Card>
          <CardHeader>
            <CardTitle>Account Status</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {isSuspended ? (
              <Button
                variant="default"
                size="sm"
                onClick={handleRestore}
                disabled={restoreMutation.isPending}
              >
                <RotateCcw className="mr-1 h-4 w-4" />
                {restoreMutation.isPending ? 'Restoring...' : 'Restore Member'}
              </Button>
            ) : (
              <Button
                variant="outline"
                size="sm"
                onClick={handleSuspend}
                disabled={suspendMutation.isPending}
                className="text-destructive"
              >
                <Ban className="mr-1 h-4 w-4" />
                {suspendMutation.isPending ? 'Suspending...' : 'Suspend Member'}
              </Button>
            )}
            <p className="text-muted-foreground text-xs">
              Suspended members cannot access the workspace but their data is preserved.
            </p>
          </CardContent>
        </Card>
      )}

      {/* Emergency revoke */}
      {!isOwner && (
        <Card className="border-destructive">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-destructive">
              <AlertTriangle className="h-5 w-5" />
              Emergency Revoke
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p className="mb-3 text-sm">
              Immediately revoke all access grants for this user. This will prevent
              them from accessing any shared resources.
            </p>
            {confirmingRevoke ? (
              <div className="flex items-center gap-2">
                <Button
                  variant="destructive"
                  size="sm"
                  onClick={handleRevokeAll}
                  disabled={revokeAllMutation.isPending}
                >
                  {revokeAllMutation.isPending ? 'Revoking...' : 'Confirm Revoke All'}
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => setConfirmingRevoke(false)}
                >
                  Cancel
                </Button>
              </div>
            ) : (
              <Button
                variant="destructive"
                size="sm"
                onClick={() => setConfirmingRevoke(true)}
              >
                <Shield className="mr-1 h-4 w-4" />
                Revoke All Access
              </Button>
            )}
          </CardContent>
        </Card>
      )}

      {/* Remove member */}
      {!isOwner && (
        <Card className="border-destructive">
          <CardHeader>
            <CardTitle className="text-destructive">Remove Member</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="mb-3 text-sm">
              Remove this member from the workspace. They will lose all access.
              This can be undone by re-inviting them.
            </p>
            {confirmingRemove ? (
              <div className="flex items-center gap-2">
                <Button
                  variant="destructive"
                  size="sm"
                  onClick={handleRemove}
                  disabled={removeMutation.isPending}
                >
                  <Trash2 className="mr-1 h-4 w-4" />
                  {removeMutation.isPending ? 'Removing...' : 'Confirm Remove'}
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => setConfirmingRemove(false)}
                >
                  Cancel
                </Button>
              </div>
            ) : (
              <Button
                variant="destructive"
                size="sm"
                onClick={() => setConfirmingRemove(true)}
              >
                <Trash2 className="mr-1 h-4 w-4" />
                Remove Member
              </Button>
            )}
          </CardContent>
        </Card>
      )}

      {/* Activity history */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Activity className="h-5 w-5" />
            Recent Activity
          </CardTitle>
        </CardHeader>
        <CardContent>
          <p className="text-muted-foreground text-sm">
            View detailed activity logs for this member in the{' '}
            <Link to="/activity" className="text-primary hover:underline">
              Activity Logs
            </Link>{' '}
            page.
          </p>
        </CardContent>
      </Card>
    </div>
  )
}
