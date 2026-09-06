import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Plus, Users, Pencil, Trash2, FolderLock } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
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
  useTeamsList,
  useCreateTeam,
  useUpdateTeam,
  useDeleteTeam,
} from '@/features/teams/hooks/use-teams'
import type { Team } from '@/types/shared-vault'
import { Lock } from 'lucide-react'

export function TeamsListPage() {
  const { data: teams, isLoading, isError } = useTeamsList()
  const [showCreateDialog, setShowCreateDialog] = useState(false)
  const [editTeam, setEditTeam] = useState<Team | null>(null)
  const [deleteTeam, setDeleteTeam] = useState<Team | null>(null)

  if (isLoading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-48" />
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {Array.from({ length: 3 }).map((_, i) => (
            <Skeleton key={i} className="h-32" />
          ))}
        </div>
      </div>
    )
  }

  if (isError) {
    return (
      <EmptyState
        icon={Lock}
        title="Access denied"
        description="You don't have permission to view teams, or no workspace is selected."
      />
    )
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Teams</h1>
          <p className="text-muted-foreground text-sm">Manage teams and their members</p>
        </div>
        <Button onClick={() => setShowCreateDialog(true)}>
          <Plus className="mr-2 h-4 w-4" />
          Create Team
        </Button>
      </div>

      {!teams || teams.length === 0 ? (
        <EmptyState
          icon={Users}
          title="No teams"
          description="Create a team to organize vault items and collaborate."
          action={
            <Button onClick={() => setShowCreateDialog(true)}>
              <Plus className="mr-2 h-4 w-4" />
              Create Team
            </Button>
          }
        />
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {teams.map((team) => (
            <Card key={team.id}>
              <CardHeader className="pb-3">
                <div className="flex items-start justify-between">
                  <div className="flex items-center gap-2">
                    {team.color && (
                      <span
                        className="h-3 w-3 rounded-full"
                        style={{ backgroundColor: team.color }}
                      />
                    )}
                    <CardTitle className="text-lg">{team.name}</CardTitle>
                  </div>
                  <div className="flex gap-1">
                    <button
                      onClick={() => setEditTeam(team)}
                      className="rounded p-1 hover:bg-accent"
                      title="Edit"
                    >
                      <Pencil className="h-3.5 w-3.5" />
                    </button>
                    <button
                      onClick={() => setDeleteTeam(team)}
                      className="rounded p-1 text-destructive hover:bg-accent"
                      title="Delete"
                    >
                      <Trash2 className="h-3.5 w-3.5" />
                    </button>
                  </div>
                </div>
              </CardHeader>
              <CardContent className="space-y-3">
                {team.description && (
                  <p className="text-muted-foreground text-sm">{team.description}</p>
                )}
                <div className="flex gap-4 text-muted-foreground text-sm">
                  <span className="flex items-center gap-1">
                    <Users className="h-3.5 w-3.5" />
                    {team.members_count ?? 0} members
                  </span>
                  <span className="flex items-center gap-1">
                    <FolderLock className="h-3.5 w-3.5" />
                    {team.vault_items_count ?? 0} items
                  </span>
                </div>
                <div className="flex gap-2 pt-1">
                  <Button asChild variant="outline" size="sm">
                    <Link to={`/admin/teams/${team.id}/members`}>Manage members</Link>
                  </Button>
                  <Button asChild variant="ghost" size="sm">
                    <Link to={`/shared/teams/${team.id}`}>View items</Link>
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {/* Create dialog */}
      <TeamDialog
        open={showCreateDialog}
        onOpenChange={setShowCreateDialog}
        mode="create"
      />

      {/* Edit dialog */}
      <TeamDialog
        open={!!editTeam}
        onOpenChange={(open) => !open && setEditTeam(null)}
        mode="edit"
        team={editTeam}
      />

      {/* Delete dialog */}
      <DeleteTeamDialog
        team={deleteTeam}
        open={!!deleteTeam}
        onOpenChange={(open) => !open && setDeleteTeam(null)}
      />
    </div>
  )
}

// ─── Create / Edit dialog ───────────────────────────────────────────────────

function TeamDialog({
  open,
  onOpenChange,
  mode,
  team,
}: {
  open: boolean
  onOpenChange: (open: boolean) => void
  mode: 'create' | 'edit'
  team?: Team | null
}) {
  const createTeam = useCreateTeam()
  const updateTeam = useUpdateTeam(team?.id ?? 0)

  const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    const formData = new FormData(e.currentTarget)
    const name = formData.get('name') as string
    const description = formData.get('description') as string
    const color = formData.get('color') as string

    if (mode === 'create') {
      createTeam.mutate(
        { name, description: description || null, color: color || null },
        {
          onSuccess: () => {
            toast.success('Team created.')
            onOpenChange(false)
          },
          onError: () => toast.error('Failed to create team.'),
        },
      )
    } else if (team) {
      updateTeam.mutate(
        { name, description: description || null, color: color || null },
        {
          onSuccess: () => {
            toast.success('Team updated.')
            onOpenChange(false)
          },
          onError: () => toast.error('Failed to update team.'),
        },
      )
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{mode === 'create' ? 'Create Team' : 'Edit Team'}</DialogTitle>
          <DialogDescription>
            {mode === 'create'
              ? 'Create a new team for this workspace.'
              : 'Update team details.'}
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="team-name">Name *</Label>
            <Input id="team-name" name="name" defaultValue={team?.name ?? ''} required autoFocus />
          </div>
          <div className="space-y-2">
            <Label htmlFor="team-description">Description</Label>
            <Input
              id="team-description"
              name="description"
              defaultValue={team?.description ?? ''}
              placeholder="Optional"
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="team-color">Color</Label>
            <div className="flex items-center gap-2">
              <Input
                id="team-color"
                name="color"
                type="color"
                defaultValue={team?.color ?? '#6366f1'}
                className="h-10 w-16 p-1"
              />
              <span className="text-muted-foreground text-sm">Team color for badges</span>
            </div>
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
              Cancel
            </Button>
            <Button type="submit" disabled={createTeam.isPending || updateTeam.isPending}>
              {mode === 'create' ? 'Create' : 'Save'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}

// ─── Delete dialog ──────────────────────────────────────────────────────────

function DeleteTeamDialog({
  team,
  open,
  onOpenChange,
}: {
  team: Team | null
  open: boolean
  onOpenChange: (open: boolean) => void
}) {
  const deleteTeam = useDeleteTeam()

  const handleDelete = () => {
    if (!team) return
    deleteTeam.mutate(team.id, {
      onSuccess: () => {
        toast.success('Team deleted.')
        onOpenChange(false)
      },
      onError: () => toast.error('Failed to delete team.'),
    })
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete team?</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete &ldquo;{team?.name}&rdquo;? Team vault items will
            remain but will be unassigned from this team.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancel
          </Button>
          <Button variant="destructive" onClick={handleDelete} disabled={deleteTeam.isPending}>
            {deleteTeam.isPending ? 'Deleting...' : 'Delete'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
