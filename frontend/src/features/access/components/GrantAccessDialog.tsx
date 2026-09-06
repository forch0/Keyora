import { useState } from 'react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '@/components/ui/tabs'
import { useTenantMembers } from '@/features/tenant/hooks/use-tenant-members'
import { useTeamsList } from '@/features/teams/hooks/use-teams'
import { useGrantAccess, useBulkGrantAccess } from '@/features/access/hooks/use-access'
import type { ResourceType, Permission, Duration } from '@/types/access'

const PERMISSIONS: { value: Permission; label: string }[] = [
  { value: 'view', label: 'View' },
  { value: 'download', label: 'Download' },
  { value: 'edit', label: 'Edit' },
  { value: 'share', label: 'Share' },
  { value: 'manage', label: 'Manage' },
]

const DURATIONS: { value: Duration; label: string }[] = [
  { value: null, label: 'No expiry' },
  { value: '15m', label: '15 minutes' },
  { value: '30m', label: '30 minutes' },
  { value: '1h', label: '1 hour' },
  { value: '24h', label: '24 hours' },
]

export function GrantAccessDialog({
  resource,
  id,
  open,
  onOpenChange,
}: {
  resource: ResourceType
  id: number
  open: boolean
  onOpenChange: (open: boolean) => void
}) {
  const [tab, setTab] = useState<'user' | 'team' | 'tenant'>('user')
  const [userId, setUserId] = useState('')
  const [teamIds, setTeamIds] = useState<number[]>([])
  const [permission, setPermission] = useState<Permission>('view')
  const [duration, setDuration] = useState<Duration>(null)
  const [maxViews, setMaxViews] = useState('')
  const [startOnFirstView, setStartOnFirstView] = useState(false)

  const { data: members } = useTenantMembers()
  const { data: teams } = useTeamsList()
  const grantAccess = useGrantAccess(resource, id)
  const bulkGrant = useBulkGrantAccess(resource, id)

  const reset = () => {
    setUserId('')
    setTeamIds([])
    setPermission('view')
    setDuration(null)
    setMaxViews('')
    setStartOnFirstView(false)
  }

  const handleSubmit = () => {
    const maxViewsNum = maxViews ? parseInt(maxViews, 10) : null

    if (tab === 'user') {
      const uid = parseInt(userId, 10)
      if (!uid) {
        toast.error('Select a user.')
        return
      }
      grantAccess.mutate(
        {
          subject_type: 'App\\Models\\User',
          subject_id: uid,
          permission,
          duration,
          max_views: maxViewsNum,
          start_on_first_view: startOnFirstView || null,
        },
        {
          onSuccess: () => {
            toast.success('Access granted.')
            reset()
            onOpenChange(false)
          },
          onError: () => toast.error('Failed to grant access.'),
        },
      )
    } else if (tab === 'team') {
      if (teamIds.length === 0) {
        toast.error('Select at least one team.')
        return
      }
      if (teamIds.length === 1) {
        grantAccess.mutate(
          {
            subject_type: 'App\\Models\\Team',
            subject_id: teamIds[0],
            permission,
            duration,
            max_views: maxViewsNum,
            start_on_first_view: startOnFirstView || null,
          },
          {
            onSuccess: () => {
              toast.success('Access granted to team.')
              reset()
              onOpenChange(false)
            },
            onError: () => toast.error('Failed to grant access.'),
          },
        )
      } else {
        bulkGrant.mutate(
          { team_ids: teamIds, permission },
          {
            onSuccess: () => {
              toast.success(`Access granted to ${teamIds.length} teams.`)
              reset()
              onOpenChange(false)
            },
            onError: () => toast.error('Failed to grant bulk access.'),
          },
        )
      }
    } else if (tab === 'tenant') {
      grantAccess.mutate(
        {
          subject_type: 'App\\Models\\Tenant',
          subject_id: 0, // backend uses current tenant
          permission,
          duration,
          max_views: maxViewsNum,
          start_on_first_view: startOnFirstView || null,
        },
        {
          onSuccess: () => {
            toast.success('Access granted to entire workspace.')
            reset()
            onOpenChange(false)
          },
          onError: () => toast.error('Failed to grant access.'),
        },
      )
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Grant Access</DialogTitle>
          <DialogDescription>
            Share this resource with users, teams, or the entire workspace.
          </DialogDescription>
        </DialogHeader>

        <Tabs value={tab} onValueChange={(v) => setTab(v as typeof tab)}>
          <TabsList className="grid w-full grid-cols-3">
            <TabsTrigger value="user">User</TabsTrigger>
            <TabsTrigger value="team">Team</TabsTrigger>
            <TabsTrigger value="tenant">Workspace</TabsTrigger>
          </TabsList>

          {/* User tab */}
          <TabsContent value="user" className="space-y-3">
            <div className="space-y-2">
              <Label>Select user</Label>
              <Select value={userId} onValueChange={setUserId}>
                <SelectTrigger>
                  <SelectValue placeholder="Choose a member..." />
                </SelectTrigger>
                <SelectContent>
                  {members?.map((m) => (
                    <SelectItem key={m.id} value={String(m.id)}>
                      {m.name} ({m.email})
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </TabsContent>

          {/* Team tab */}
          <TabsContent value="team" className="space-y-3">
            <div className="space-y-2">
              <Label>Select teams {teamIds.length > 1 && '(bulk grant)'}</Label>
              <div className="space-y-2">
                {teams?.length === 0 && (
                  <p className="text-muted-foreground text-sm">No teams available.</p>
                )}
                {teams?.map((team) => (
                  <label
                    key={team.id}
                    className="flex items-center gap-2 rounded border p-2 text-sm"
                  >
                    <input
                      type="checkbox"
                      checked={teamIds.includes(team.id)}
                      onChange={(e) => {
                        if (e.target.checked) setTeamIds([...teamIds, team.id])
                        else setTeamIds(teamIds.filter((t) => t !== team.id))
                      }}
                    />
                    {team.name}
                  </label>
                ))}
              </div>
            </div>
          </TabsContent>

          {/* Tenant tab */}
          <TabsContent value="tenant" className="space-y-3">
            <p className="rounded bg-muted p-3 text-muted-foreground text-sm">
              Grant access to every member of this workspace.
            </p>
          </TabsContent>
        </Tabs>

        {/* Common fields */}
        <div className="space-y-3">
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-2">
              <Label>Permission</Label>
              <Select value={permission} onValueChange={(v) => setPermission(v as Permission)}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {PERMISSIONS.map((p) => (
                    <SelectItem key={p.value} value={p.value}>
                      {p.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Time limit</Label>
              <Select
                value={duration ?? 'none'}
                onValueChange={(v) => setDuration(v === 'none' ? null : (v as Duration))}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {DURATIONS.map((d) => (
                    <SelectItem key={d.value ?? 'none'} value={d.value ?? 'none'}>
                      {d.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="max-views">Max views (optional)</Label>
            <Input
              id="max-views"
              type="number"
              min="1"
              value={maxViews}
              onChange={(e) => setMaxViews(e.target.value)}
              placeholder="Unlimited"
            />
          </div>

          <div className="flex items-center gap-2">
            <Switch
              id="start-on-first-view"
              checked={startOnFirstView}
              onCheckedChange={setStartOnFirstView}
            />
            <Label htmlFor="start-on-first-view" className="text-sm">
              Start countdown on first view
            </Label>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancel
          </Button>
          <Button
            onClick={handleSubmit}
            disabled={grantAccess.isPending || bulkGrant.isPending}
          >
            {grantAccess.isPending || bulkGrant.isPending ? 'Granting...' : 'Grant Access'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
