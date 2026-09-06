import { useState } from 'react'
import { toast } from 'sonner'
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
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useCreateAccessRequest } from '@/features/access-requests/hooks/use-access-requests'
import type {
  AccessRequestResourceType,
  RequestPermission,
  RequestDuration,
} from '@/types/access-request'

const PERMISSIONS: { value: RequestPermission; label: string }[] = [
  { value: 'view', label: 'View' },
  { value: 'download', label: 'Download' },
  { value: 'edit', label: 'Edit' },
  { value: 'share', label: 'Share' },
  { value: 'manage', label: 'Manage' },
]

const DURATIONS: { value: RequestDuration; label: string }[] = [
  { value: null, label: 'No expiry' },
  { value: '15m', label: '15 minutes' },
  { value: '30m', label: '30 minutes' },
  { value: '1h', label: '1 hour' },
  { value: '24h', label: '24 hours' },
  { value: '7d', label: '7 days' },
  { value: '30d', label: '30 days' },
  { value: 'permanent', label: 'Permanent' },
]

export function RequestAccessDialog({
  resourceType,
  resourceId,
  resourceName,
  open,
  onOpenChange,
}: {
  resourceType: AccessRequestResourceType
  resourceId: number
  resourceName: string
  open: boolean
  onOpenChange: (open: boolean) => void
}) {
  const [permission, setPermission] = useState<RequestPermission>('view')
  const [duration, setDuration] = useState<RequestDuration>(null)
  const [reason, setReason] = useState('')
  const createRequest = useCreateAccessRequest()

  const handleSubmit = () => {
    if (!reason.trim()) {
      toast.error('Please provide a reason for your request.')
      return
    }
    createRequest.mutate(
      {
        resource_type: resourceType,
        resource_id: resourceId,
        requested_permission: permission,
        requested_duration: duration,
        reason: reason.trim(),
      },
      {
        onSuccess: () => {
          toast.success('Access request submitted.')
          setPermission('view')
          setDuration(null)
          setReason('')
          onOpenChange(false)
        },
        onError: (err) => {
          if (err.code === 'DUPLICATE_PENDING_REQUEST') {
            toast.error('You already have a pending request for this resource.')
          } else {
            toast.error(err.message || 'Failed to submit request.')
          }
        },
      },
    )
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Request Access</DialogTitle>
          <DialogDescription>
            Request access to <span className="font-medium">{resourceName}</span>
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-2">
              <Label>Permission</Label>
              <Select value={permission} onValueChange={(v) => setPermission(v as RequestPermission)}>
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
              <Label>Duration</Label>
              <Select
                value={duration ?? 'none'}
                onValueChange={(v) => setDuration(v === 'none' ? null : (v as RequestDuration))}
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
            <Label htmlFor="reason">Reason *</Label>
            <Textarea
              id="reason"
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              placeholder="Explain why you need access to this resource..."
              rows={3}
            />
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancel
          </Button>
          <Button onClick={handleSubmit} disabled={createRequest.isPending}>
            {createRequest.isPending ? 'Submitting...' : 'Submit Request'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
