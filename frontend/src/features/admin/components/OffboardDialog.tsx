import { useState } from 'react'
import { toast } from 'sonner'
import {
  AlertTriangle,
  ShieldX,
  UsersRound,
  KeyRound,
  LinkIcon,
  Loader2,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  useOffboardMember,
  type TenantMember,
} from '@/features/admin/hooks/use-admin'

interface OffboardDialogProps {
  member: TenantMember
  open: boolean
  onOpenChange: (open: boolean) => void
  onSuccess?: () => void
}

const CHECKLIST_ITEMS = [
  {
    icon: ShieldX,
    label: 'All access grants revoked',
    description: 'The member will lose access to all shared vault items, files, and notes.',
  },
  {
    icon: UsersRound,
    label: 'Removed from all teams',
    description: 'Team memberships in this workspace will be removed.',
  },
  {
    icon: KeyRound,
    label: 'API tokens revoked',
    description: 'All API tokens for this workspace will be deleted.',
  },
  {
    icon: LinkIcon,
    label: 'Secure links revoked',
    description: 'Any active secure links created by this member will be revoked.',
  },
]

export function OffboardDialog({ member, open, onOpenChange, onSuccess }: OffboardDialogProps) {
  const [reason, setReason] = useState('')
  const [confirmed, setConfirmed] = useState(false)
  const offboardMutation = useOffboardMember()

  const handleOffboard = () => {
    offboardMutation.mutate(
      {
        userId: member.id,
        reason: reason.trim() || null,
      },
      {
        onSuccess: () => {
          toast.success(`${member.name} has been offboarded.`)
          setReason('')
          setConfirmed(false)
          onOpenChange(false)
          onSuccess?.()
        },
        onError: () => toast.error('Failed to offboard member.'),
      },
    )
  }

  const handleCancel = () => {
    setReason('')
    setConfirmed(false)
    onOpenChange(false)
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2 text-destructive">
            <AlertTriangle className="h-5 w-5" />
            Offboard {member.name}
          </DialogTitle>
          <DialogDescription>
            This will permanently remove {member.name} from the workspace and revoke
            all their access. This action cannot be undone.
          </DialogDescription>
        </DialogHeader>

        {/* Pre-offboarding checklist */}
        <div className="space-y-3">
          <div className="rounded border bg-muted/30 p-3">
            <p className="mb-2 text-sm font-medium">What will happen:</p>
            <ul className="space-y-2">
              {CHECKLIST_ITEMS.map((item) => {
                const Icon = item.icon
                return (
                  <li key={item.label} className="flex items-start gap-2">
                    <Icon className="mt-0.5 h-4 w-4 shrink-0 text-destructive" />
                    <div>
                      <p className="text-sm font-medium">{item.label}</p>
                      <p className="text-muted-foreground text-xs">{item.description}</p>
                    </div>
                  </li>
                )
              })}
            </ul>
          </div>

          {member.teams_count !== undefined && member.teams_count > 0 && (
            <div className="flex items-center gap-2 text-sm">
              <UsersRound className="h-4 w-4 text-muted-foreground" />
              <span>Member of {member.teams_count} team{member.teams_count !== 1 ? 's' : ''}</span>
            </div>
          )}

          {/* Reason */}
          <div className="space-y-2">
            <Label htmlFor="offboard-reason">Reason (optional)</Label>
            <Textarea
              id="offboard-reason"
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              placeholder="e.g. Leaving the company, contract ended..."
              rows={2}
            />
          </div>

          {/* Confirmation checkbox */}
          <label className="flex items-start gap-2 cursor-pointer">
            <input
              type="checkbox"
              checked={confirmed}
              onChange={(e) => setConfirmed(e.target.checked)}
              className="mt-0.5 h-4 w-4 rounded border-input"
            />
            <span className="text-sm">
              I understand this action is irreversible and will revoke all access
              for {member.name}.
            </span>
          </label>
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={handleCancel}>
            Cancel
          </Button>
          <Button
            variant="destructive"
            onClick={handleOffboard}
            disabled={!confirmed || offboardMutation.isPending}
          >
            {offboardMutation.isPending ? (
              <>
                <Loader2 className="mr-1 h-4 w-4 animate-spin" />
                Offboarding...
              </>
            ) : (
              <>
                <AlertTriangle className="mr-1 h-4 w-4" />
                Offboard Member
              </>
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
