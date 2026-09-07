import { useState, useMemo } from 'react'
import { Link } from 'react-router-dom'
import {
  Users,
  Search,
  UserPlus,
  Settings,
  ArrowLeft,
  Shield,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { EmptyState } from '@/components/shared/EmptyState'
import { InviteMemberDialog } from '@/features/admin/components/InviteMemberDialog'
import { InvitationsPanel } from '@/features/admin/components/InvitationsPanel'
import {
  useTenantMembers,
} from '@/features/admin/hooks/use-admin'
import type { TenantMember } from '@/features/admin/hooks/use-admin'

const ROLE_FILTERS = ['all', 'owner', 'admin', 'member'] as const
const STATUS_FILTERS = ['all', 'active', 'suspended', 'left'] as const

function roleBadge(role: string | null) {
  if (role === 'owner') return <Badge variant="default">Owner</Badge>
  if (role === 'admin') return <Badge variant="secondary">Admin</Badge>
  return <Badge variant="outline">Member</Badge>
}

function statusBadge(status: string | null) {
  if (status === 'suspended') return <Badge variant="destructive">Suspended</Badge>
  if (status === 'left') return <Badge variant="outline">Offboarded</Badge>
  return <Badge variant="default" className="bg-green-600">Active</Badge>
}

export function MembersListPage() {
  const { data: members, isLoading } = useTenantMembers()
  const [search, setSearch] = useState('')
  const [roleFilter, setRoleFilter] = useState<string>('all')
  const [statusFilter, setStatusFilter] = useState<string>('all')
  const [showInvite, setShowInvite] = useState(false)

  const filtered = useMemo(() => {
    if (!members) return []
    return members.filter((m) => {
      if (search) {
        const q = search.toLowerCase()
        if (!m.name.toLowerCase().includes(q) && !m.email.toLowerCase().includes(q)) {
          return false
        }
      }
      if (roleFilter !== 'all' && m.role !== roleFilter) return false
      if (statusFilter !== 'all' && m.status !== statusFilter) return false
      return true
    })
  }, [members, search, roleFilter, statusFilter])

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="sm" asChild className="shrink-0">
            <Link to="/admin">
              <ArrowLeft className="mr-1 h-4 w-4" />
              Admin
            </Link>
          </Button>
          <div>
            <h1 className="text-xl font-bold sm:text-2xl">Members</h1>
            <p className="text-muted-foreground text-sm">
              Manage workspace members and roles
            </p>
          </div>
        </div>
        <Button onClick={() => setShowInvite(true)}>
          <UserPlus className="mr-2 h-4 w-4" />
          Invite Member
        </Button>
      </div>

      {/* Search + filters */}
      <div className="space-y-3">
        <div className="relative max-w-md">
          <Search className="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground" />
          <Input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search by name or email..."
            className="pl-8"
          />
        </div>
        <div className="flex flex-wrap gap-4">
          <div className="flex items-center gap-1">
            <span className="text-muted-foreground text-xs">Role:</span>
            {ROLE_FILTERS.map((r) => (
              <Button
                key={r}
                variant={roleFilter === r ? 'default' : 'outline'}
                size="sm"
                onClick={() => setRoleFilter(r)}
                className="h-7 capitalize"
              >
                {r}
              </Button>
            ))}
          </div>
          <div className="flex items-center gap-1">
            <span className="text-muted-foreground text-xs">Status:</span>
            {STATUS_FILTERS.map((s) => (
              <Button
                key={s}
                variant={statusFilter === s ? 'default' : 'outline'}
                size="sm"
                onClick={() => setStatusFilter(s)}
                className="h-7 capitalize"
              >
                {s === 'left' ? 'Offboarded' : s}
              </Button>
            ))}
          </div>
        </div>
      </div>

      {/* Members list */}
      {isLoading ? (
        <div className="space-y-2">
          {Array.from({ length: 5 }).map((_, i) => (
            <Skeleton key={i} className="h-16" />
          ))}
        </div>
      ) : filtered.length === 0 ? (
        <EmptyState
          icon={Users}
          title={search || roleFilter !== 'all' || statusFilter !== 'all' ? 'No members found' : 'No members yet'}
          description={search || roleFilter !== 'all' || statusFilter !== 'all' ? 'Try adjusting your filters.' : 'Invite members to your workspace.'}
          action={
            !search && roleFilter === 'all' && statusFilter === 'all' ? (
              <Button onClick={() => setShowInvite(true)}>
                <UserPlus className="mr-2 h-4 w-4" />
                Invite Member
              </Button>
            ) : undefined
          }
        />
      ) : (
        <div className="space-y-2">
          {filtered.map((member) => (
            <MemberRow key={member.id} member={member} />
          ))}
        </div>
      )}

      {/* Invitations */}
      <InvitationsPanel />

      <InviteMemberDialog open={showInvite} onOpenChange={setShowInvite} />
    </div>
  )
}

function MemberRow({ member }: { member: TenantMember }) {
  const isSuspended = member.status === 'suspended'
  const isLeft = member.status === 'left'
  const isOwner = member.role === 'owner'

  return (
    <Card className={isSuspended ? 'border-destructive/40' : isLeft ? 'opacity-60' : ''}>
      <CardContent className="flex items-center gap-3 p-3">
        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-muted">
          <span className="font-medium text-sm">
            {member.name.charAt(0).toUpperCase()}
          </span>
        </div>
        <div className="min-w-0 flex-1">
          <div className="flex items-center gap-2">
            <span className="truncate font-medium text-sm">{member.name}</span>
            {roleBadge(member.role)}
            {statusBadge(member.status)}
          </div>
          <div className="mt-0.5 flex items-center gap-2 text-muted-foreground text-xs">
            <span className="truncate">{member.email}</span>
            {member.joined_at && (
              <>
                <span>·</span>
                <span>Joined {new Date(member.joined_at).toLocaleDateString()}</span>
              </>
            )}
            {member.teams_count !== undefined && member.teams_count > 0 && (
              <>
                <span>·</span>
                <span>{member.teams_count} team{member.teams_count !== 1 ? 's' : ''}</span>
              </>
            )}
          </div>
        </div>
        {!isLeft && !isOwner && (
          <Button variant="outline" size="sm" asChild>
            <Link to={`/admin/members/${member.id}`}>
              <Settings className="mr-1 h-3.5 w-3.5" />
              Manage
            </Link>
          </Button>
        )}
        {isLeft && (
          <Button variant="outline" size="sm" asChild>
            <Link to={`/admin/members/${member.id}`}>
              View
            </Link>
          </Button>
        )}
        {isOwner && (
          <Shield className="h-4 w-4 text-muted-foreground" />
        )}
      </CardContent>
    </Card>
  )
}
