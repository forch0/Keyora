import { useSearchParams, Link } from 'react-router-dom'
import { ArrowLeft, KeyRound, History, Lock } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { EmptyState } from '@/components/shared/EmptyState'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useAccessRequestHistory } from '@/features/access-requests/hooks/use-access-requests'

const STATUS_COLORS: Record<string, 'secondary' | 'default' | 'destructive' | 'outline'> = {
  pending: 'default',
  approved: 'secondary',
  rejected: 'destructive',
  cancelled: 'outline',
  expired: 'outline',
}

const STATUS_FILTERS = [
  { value: 'all', label: 'All' },
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

export function RequestHistoryPage() {
  const [searchParams, setSearchParams] = useSearchParams()
  const statusFilter = searchParams.get('status') ?? 'all'
  const page = parseInt(searchParams.get('page') ?? '1', 10) || 1

  const params: Record<string, unknown> = {
    page,
    ...(statusFilter !== 'all' && { status: statusFilter }),
  }

  const { data, isLoading, isError } = useAccessRequestHistory(params)
  const requests = data?.data
  const meta = data?.meta

  const updateParam = (key: string, value: string) => {
    const next = new URLSearchParams(searchParams)
    if (value === 'all' || value === '') next.delete(key)
    else next.set(key, value)
    if (key !== 'page') next.delete('page')
    setSearchParams(next)
  }

  return (
    <div className="space-y-4">
      <Link to="/access-requests">
        <Button variant="ghost" size="sm">
          <ArrowLeft className="mr-2 h-4 w-4" />
          Back to access requests
        </Button>
      </Link>

      <div>
        <h1 className="flex items-center gap-2 text-2xl font-bold">
          <History className="h-6 w-6" />
          Request History
        </h1>
        <p className="text-muted-foreground text-sm">All past access requests</p>
      </div>

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

      {isLoading ? (
        <div className="space-y-3">
          {Array.from({ length: 3 }).map((_, i) => (
            <Skeleton key={i} className="h-20" />
          ))}
        </div>
      ) : isError ? (
        <EmptyState
          icon={Lock}
          title="Error loading history"
          description="Failed to load request history."
        />
      ) : !requests || requests.length === 0 ? (
        <EmptyState
          icon={History}
          title="No history"
          description="Past access requests will appear here."
        />
      ) : (
        <div className="space-y-3">
          {requests.map((req) => {
            const resourceName = String(req.resource.name ?? `#${req.resource.id}`)
            return (
              <Card key={req.id}>
                <CardContent className="flex items-center gap-4 p-4">
                  <div className="rounded-md bg-muted p-2">
                    <KeyRound className="h-5 w-5" />
                  </div>
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                      <p className="truncate font-medium">{resourceName}</p>
                      <Badge variant="outline" className="text-xs">
                        {resourceTypeLabel(req.resource.type)}
                      </Badge>
                      <Badge variant={STATUS_COLORS[req.status] ?? 'outline'} className="text-xs">
                        {req.status}
                      </Badge>
                    </div>
                    <div className="mt-1 flex flex-wrap items-center gap-3 text-muted-foreground text-xs">
                      <span>By: {req.requester.name}</span>
                      <span>Permission: {req.requested_permission}</span>
                      {req.granted_permission && (
                        <span>Granted: {req.granted_permission}</span>
                      )}
                      {req.reviewed_by && <span>Reviewed by: {req.reviewed_by}</span>}
                      <span>
                        {req.created_at && new Date(req.created_at).toLocaleDateString()}
                      </span>
                    </div>
                    {req.review_note && (
                      <p className="mt-1 truncate text-xs italic text-muted-foreground">
                        Note: {req.review_note}
                      </p>
                    )}
                  </div>
                </CardContent>
              </Card>
            )
          })}

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
    </div>
  )
}
