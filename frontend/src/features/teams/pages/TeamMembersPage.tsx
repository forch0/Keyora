import { useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { ArrowLeft, Users, UserPlus, Trash2, Lock } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { EmptyState } from '@/components/shared/EmptyState'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  useTeamMembers,
  useTeamsList,
  useAddTeamMember,
  useUpdateTeamMember,
  useRemoveTeamMember,
} from '@/features/teams/hooks/use-teams'

export function TeamMembersPage() {
  const { teamId } = useParams<{ teamId: string }>()
  const tid = parseInt(teamId ?? '0', 10)

  const { data: teams } = useTeamsList()
  const { data: members, isLoading, isError } = useTeamMembers(tid)
  const addMember = useAddTeamMember(tid)
  const removeMember = useRemoveTeamMember(tid)

  const [showAddDialog, setShowAddDialog] = useState(false)
  const [userId, setUserId] = useState('')
  const [role, setRole] = useState<'lead' | 'member'>('member')

  const currentTeam = teams?.find((t) => t.id === tid)

  const handleAdd = () => {
    const id = parseInt(userId, 10)
    if (!id) return
    addMember.mutate(
      { user_id: id, role },
      {
        onSuccess: () => {
          toast.success('Member added.')
          setShowAddDialog(false)
          setUserId('')
          setRole('member')
        },
        onError: () => toast.error('Failed to add member.'),
      },
    )
  }

  const handleRemove = (userId: number, name: string) => {
    removeMember.mutate(userId, {
      onSuccess: () => toast.success(`${name} removed from team.`),
      onError: () => toast.error('Failed to remove member.'),
    })
  }

  if (isLoading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-48" />
        <Skeleton className="h-64 w-full" />
      </div>
    )
  }

  if (isError) {
    return (
      <EmptyState
        icon={Lock}
        title="Access denied"
        description="You don't have permission to view this team's members."
      />
    )
  }

  return (
    <div className="space-y-4">
      <Link to="/admin/teams">
        <Button variant="ghost" size="sm">
          <ArrowLeft className="mr-2 h-4 w-4" />
          Back to teams
        </Button>
      </Link>

      <div className="flex items-center justify-between">
        <div>
          <h1 className="flex items-center gap-2 text-2xl font-bold">
            <Users className="h-6 w-6" />
            {currentTeam?.name ?? 'Team'} — Members
          </h1>
          <p className="text-muted-foreground text-sm">
            {members?.length ?? 0} member{(members?.length ?? 0) !== 1 ? 's' : ''}
          </p>
        </div>
        <Button onClick={() => setShowAddDialog(true)}>
          <UserPlus className="mr-2 h-4 w-4" />
          Add Member
        </Button>
      </div>

      {!members || members.length === 0 ? (
        <EmptyState
          icon={Users}
          title="No members"
          description="Add members to this team to collaborate on vault items."
          action={
            <Button onClick={() => setShowAddDialog(true)}>
              <UserPlus className="mr-2 h-4 w-4" />
              Add Member
            </Button>
          }
        />
      ) : (
        <Card>
          <CardContent className="divide-y p-0">
            {members.map((member) => (
              <MemberRow
                key={member.id}
                teamId={tid}
                member={member}
                onRemove={() => handleRemove(member.id, member.name)}
                isRemoving={removeMember.isPending}
              />
            ))}
          </CardContent>
        </Card>
      )}

      {/* Add member dialog */}
      <Dialog open={showAddDialog} onOpenChange={setShowAddDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Add Member</DialogTitle>
            <DialogDescription>
              Add a workspace member to this team by their user ID.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="user-id">User ID</Label>
              <Input
                id="user-id"
                type="number"
                value={userId}
                onChange={(e) => setUserId(e.target.value)}
                placeholder="e.g. 12"
                autoFocus
              />
            </div>
            <div className="space-y-2">
              <Label>Role</Label>
              <Select value={role} onValueChange={(v) => setRole(v as 'lead' | 'member')}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="member">Member</SelectItem>
                  <SelectItem value="lead">Lead</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setShowAddDialog(false)}>
              Cancel
            </Button>
            <Button onClick={handleAdd} disabled={!userId || addMember.isPending}>
              {addMember.isPending ? 'Adding...' : 'Add'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  )
}

// ─── Member row ─────────────────────────────────────────────────────────────

function MemberRow({
  teamId,
  member,
  onRemove,
  isRemoving,
}: {
  teamId: number
  member: { id: number; name: string; email: string; role: string | null; joined_at: string | null }
  onRemove: () => void
  isRemoving: boolean
}) {
  const updateMember = useUpdateTeamMember(teamId, member.id)

  const handleRoleChange = (newRole: 'lead' | 'member') => {
    updateMember.mutate(
      { role: newRole },
      {
        onSuccess: () => toast.success('Role updated.'),
        onError: () => toast.error('Failed to update role.'),
      },
    )
  }

  return (
    <div className="flex items-center gap-3 p-4">
      <div className="flex h-9 w-9 items-center justify-center rounded-full bg-muted text-sm font-medium">
        {member.name.charAt(0).toUpperCase()}
      </div>
      <div className="min-w-0 flex-1">
        <p className="truncate font-medium">{member.name}</p>
        <p className="truncate text-muted-foreground text-sm">{member.email}</p>
      </div>
      <Select
        value={member.role ?? 'member'}
        onValueChange={(v) => handleRoleChange(v as 'lead' | 'member')}
      >
        <SelectTrigger className="w-28">
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="member">Member</SelectItem>
          <SelectItem value="lead">Lead</SelectItem>
        </SelectContent>
      </Select>
      <Button
        variant="ghost"
        size="sm"
        onClick={onRemove}
        disabled={isRemoving}
        className="text-destructive"
      >
        <Trash2 className="h-4 w-4" />
      </Button>
    </div>
  )
}
