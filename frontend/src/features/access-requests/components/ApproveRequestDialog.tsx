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
import { useApproveRequest, useRejectRequest } from '@/features/access-requests/hooks/use-access-requests'
import type { AccessRequest, RequestPermission, RequestDuration } from '@/types/access-request'

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

export function ApproveRequestDialog({
  request,
  open,
  onOpenChange,
}: {
  request: AccessRequest | null
  open: boolean
  onOpenChange: (open: boolean) => void
}) {
  const [permission, setPermission] = useState<RequestPermission>(
    (request?.requested_permission as RequestPermission) ?? 'view',
  )
  const [duration, setDuration] = useState<RequestDuration>(null)
  const [reviewNote, setReviewNote] = useState('')
  const approve = useApproveRequest()
  const reject = useRejectRequest()

  const handleApprove = () => {
    if (!request) return
    approve.mutate(
      {
        id: request.id,
        data: {
          granted_permission: permission,
          granted_duration: duration,
          review_note: reviewNote.trim() || null,
        },
      },
      {
        onSuccess: () => {
          toast.success('Request approved.')
          onOpenChange(false)
        },
        onError: () => toast.error('Failed to approve request.'),
      },
    )
  }

  const handleReject = () => {
    if (!request) return
    reject.mutate(
      {
        id: request.id,
        data: { review_note: reviewNote.trim() || null },
      },
      {
        onSuccess: () => {
          toast.success('Request rejected.')
          onOpenChange(false)
        },
        onError: () => toast.error('Failed to reject request.'),
      },
    )
  }

  if (!request) return null
  const resourceName = String(request.resource.name ?? `#${request.resource.id}`)

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Review Access Request</DialogTitle>
          <DialogDescription>
            <span className="font-medium">{request.requester.name}</span> requested{' '}
            <span className="font-medium">{request.requested_permission}</span> access to{' '}
            <span className="font-medium">{resourceName}</span>
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          {request.reason && (
            <div className="rounded bg-muted p-3 text-sm">
              <p className="text-muted-foreground text-xs">Reason</p>
              <p className="mt-1">{request.reason}</p>
            </div>
          )}

          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-2">
              <Label>Grant permission</Label>
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
            <Label htmlFor="review-note">Review note (optional)</Label>
            <Textarea
              id="review-note"
              value={reviewNote}
              onChange={(e) => setReviewNote(e.target.value)}
              placeholder="Add a note for the requester..."
              rows={2}
            />
          </div>
        </div>

        <DialogFooter className="gap-2">
          <Button
            variant="destructive"
            onClick={handleReject}
            disabled={reject.isPending}
          >
            {reject.isPending ? 'Rejecting...' : 'Reject'}
          </Button>
          <Button onClick={handleApprove} disabled={approve.isPending}>
            {approve.isPending ? 'Approving...' : 'Approve'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
