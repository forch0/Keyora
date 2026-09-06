import { useState } from 'react'
import { useSearchParams, Link } from 'react-router-dom'
import {
  KeyRound,
  Check,
  Trash2,
  Clock,
  History,
  Lock,
} from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { EmptyState } from '@/components/shared/EmptyState'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  useAccessRequests,
  useCancelRequest,
} from '@/features/access-requests/hooks/use-access-requests'
import { ApproveRequestDialog } from '@/features/access-requests/components/ApproveRequestDialog'
import type { AccessRequest, RequestDirection } from '@/types/access-request'

const STATUS_COLORS: Record<string, 'secondary' | 'default' | 'destructive' | 'outline'> = {
  pending: 'default',
  approved: 'secondary',
  rejected: 'destructive',
  cancelled: 'outline',
  expired: 'outline',
}

const STATUS_FILTERS: { value: string; label: string }[] = [
  { value: 'all', label: 'All' },
  { value: 'pending', label: 'Pending' },
  { value: 'approved', label: 'Approved' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'cancelled', label: 'Cancelled' },
  { value: 'expired', label: 'Expired' },
]

function resourceTypeLabel(type: string): string {
  if (type.includes('VaultItem')) return 'Vault Item'
  if (type.includes('SecureFile')) return 'File'
  if (type.includes('SecureNote')) return 'Note'
  return type
}

export function AccessRequestsPage() {
  const [searchParams, setSearchParams] = useSearchParams()
  const direction = (searchParams.get('direction') ?? 'sent') as RequestDirection
  const statusFilter = searchParams.get('status') ?? 'all'

  const [reviewRequest, setReviewRequest] = useState<AccessRequest | null>(null)
  const cancelRequest = useCancelRequest()

  const params: Record<string, unknown> = {
    direction,
    ...(statusFilter !== 'all' && { status: statusFilter }),
  }

  const { data, isLoading, isError } = useAccessRequests(params)
  const requests = data?.data
  const meta = data?.meta

  const updateParam = (key: string, value: string) => {
    const next = new URLSearchParams(searchParams)
    if (value === 'all' || value === '') next.delete(key)
    else next.set(key, value)
    if (key === 'direction') next.delete('page')
    setSearchParams(next)
  }

  const handleCancel = (id: number) => {
    cancelRequest.mutate(id, {
      onSuccess: () => toast.success('Request cancelled.'),
      onError: () => toast.error('Failed to cancel request.'),
    })
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Access Requests</h1>
          <p className="text-muted-foreground text-sm">Request and approve access to resources</p>
        </div>
        <Button asChild variant="outline">
          <Link to="/access-requests/history">
            <History className="mr-2 h-4 w-4" />
            History
          </Link>
        </Button>
      </div>

      {/* Direction tabs */}
      <Tabs value={direction} onValueChange={(v) => updateParam('direction', v)}>
        <TabsList className="grid w-full max-w-xs grid-cols-2">
          <TabsTrigger value="sent">Sent</TabsTrigger>
          <TabsTrigger value="received">Received</TabsTrigger>
        </TabsList>
      </Tabs>

      {/* Status filter */}
      <div className="flex items-center gap-2">
        <span className="text-muted-foreground text-sm">Status:</span>
        <Select value={statusFilter} onValueChange={(v) => updateParam('status', v)}>
          <SelectTrigger className="w-36">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            {STATUS_FILTERS.map((f) => (
              <SelectItem key={f.value} value={f.value}>
                {f.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      {/* List */}
      {isLoading ? (
        <div className="space-y-3">
          {Array.from({ length: 3 }).map((_, i) => (
            <Skeleton key={i} className="h-24" />
          ))}
        </div>
      ) : isError ? (
        <EmptyState
          icon={Lock}
          title="Error loading requests"
          description="Failed to load access requests. Please try again."
        />
      ) : !requests || requests.length === 0 ? (
        <EmptyState
          icon={KeyRound}
          title={`No ${direction} requests`}
          description={
            direction === 'sent'
              ? "You haven't requested access to any resources yet."
              : "No one has requested access to your resources yet."
          }
        />
      ) : (
        <div className="space-y-3">
          {requests.map((req) => (
            <RequestRow
              key={req.id}
              request={req}
              direction={direction}
              onReview={() => setReviewRequest(req)}
              onCancel={() => handleCancel(req.id)}
              isCancelling={cancelRequest.isPending}
            />
          ))}

          {meta && meta.last_page > 1 && (
            <div className="flex items-center justify-between pt-2">
              <p className="text-muted-foreground text-sm">
                Page {meta.current_page} of {meta.last_page} ({meta.total} total)
              </p>
              <div className="flex gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  disabled={meta.current_page <= 1}
                  onClick={() => updateParam('page', String(meta.current_page - 1))}
                >
                  Prev
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  disabled={meta.current_page >= meta.last_page}
                  onClick={() => updateParam('page', String(meta.current_page + 1))}
                >
                  Next
                </Button>
              </div>
            </div>
          )}
        </div>
      )}

      {/* Approve/Reject dialog */}
      <ApproveRequestDialog
        request={reviewRequest}
        open={!!reviewRequest}
        onOpenChange={(open) => !open && setReviewRequest(null)}
      />
    </div>
  )
}

// ─── Request row ────────────────────────────────────────────────────────────

function RequestRow({
  request,
  direction,
  onReview,
  onCancel,
  isCancelling,
}: {
  request: AccessRequest
  direction: RequestDirection
  onReview: () => void
  onCancel: () => void
  isCancelling: boolean
}) {
  const resourceName = String(request.resource.name ?? `#${request.resource.id}`)
  const isPending = request.status === 'pending'
  const canReview = direction === 'received' && isPending
  const canCancel = direction === 'sent' && isPending

  return (
    <Card>
      <CardContent className="flex items-center gap-4 p-4">
        <div className="rounded-md bg-muted p-2">
          <KeyRound className="h-5 w-5" />
        </div>

        <div className="min-w-0 flex-1">
          <div className="flex items-center gap-2">
            <p className="truncate font-medium">{resourceName}</p>
            <Badge variant="outline" className="text-xs">
              {resourceTypeLabel(request.resource.type)}
            </Badge>
            <Badge variant={STATUS_COLORS[request.status] ?? 'outline'} className="text-xs">
              {request.status}
            </Badge>
          </div>
          <div className="mt-1 flex flex-wrap items-center gap-3 text-muted-foreground text-xs">
            {direction === 'sent' ? (
              <span>To: {request.resource_owner.name}</span>
            ) : (
              <span>From: {request.requester.name}</span>
            )}
            <span className="flex items-center gap-1">
              <KeyRound className="h-3 w-3" />
              {request.requested_permission}
              {request.granted_permission && request.granted_permission !== request.requested_permission && (
                <> → {request.granted_permission}</>
              )}
            </span>
            {request.requested_duration && (
              <span className="flex items-center gap-1">
                <Clock className="h-3 w-3" />
                {request.requested_duration}
              </span>
            )}
            <span>
              {request.created_at && new Date(request.created_at).toLocaleDateString()}
            </span>
          </div>
          {request.reason && (
            <p className="mt-1 truncate text-muted-foreground text-sm">{request.reason}</p>
          )}
          {request.review_note && (
            <p className="mt-1 truncate text-xs italic text-muted-foreground">
              Review: {request.review_note}
            </p>
          )}
        </div>

        <div className="flex gap-2">
          {canReview && (
            <Button size="sm" variant="outline" onClick={onReview}>
              <Check className="mr-1 h-4 w-4" />
              Review
            </Button>
          )}
          {canCancel && (
            <Button
              size="sm"
              variant="ghost"
              onClick={onCancel}
              disabled={isCancelling}
              className="text-destructive"
            >
              <Trash2 className="h-4 w-4" />
            </Button>
          )}
        </div>
      </CardContent>
    </Card>
  )
}
