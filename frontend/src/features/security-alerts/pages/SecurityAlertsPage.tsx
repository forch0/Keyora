import { useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import {
  Bell,
  AlertTriangle,
  Info,
  ShieldAlert,
  Check,
  X,
  CheckCheck,
  ChevronLeft,
  ChevronRight,
} from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { EmptyState } from '@/components/shared/EmptyState'
import {
  useSecurityAlerts,
  useMarkAlertRead,
  useDismissAlert,
  useMarkAllAlertsRead,
} from '@/features/security-alerts/hooks/use-security-alerts'
import type { SecurityAlert } from '@/types/security-alert'

const PER_PAGE = 20

function severityIcon(severity: string) {
  if (severity === 'critical') return ShieldAlert
  if (severity === 'warning') return AlertTriangle
  return Info
}

function severityBadge(severity: string) {
  if (severity === 'critical') return <Badge variant="destructive">Critical</Badge>
  if (severity === 'warning') return <Badge variant="default" className="bg-yellow-600">Warning</Badge>
  return <Badge variant="secondary">Info</Badge>
}

const SEVERITY_FILTERS = ['all', 'info', 'warning', 'critical'] as const

export function SecurityAlertsPage() {
  const [searchParams, setSearchParams] = useSearchParams()
  const page = parseInt(searchParams.get('page') ?? '1', 10) || 1
  const severity = searchParams.get('severity') ?? 'all'

  const [confirmingDismiss, setConfirmingDismiss] = useState<number | null>(null)

  const params: { page: number; per_page: number; severity?: string } = {
    page,
    per_page: PER_PAGE,
  }
  if (severity !== 'all') params.severity = severity

  const { data, isLoading } = useSecurityAlerts(params)
  const markRead = useMarkAlertRead()
  const dismiss = useDismissAlert()
  const markAllRead = useMarkAllAlertsRead()

  const alerts = data?.data ?? []
  const totalPages = data?.last_page ?? 1

  const setSeverity = (s: string) => setSearchParams(s === 'all' ? {} : { severity: s })
  const setPage = (p: number) => {
    const next: Record<string, string> = { page: String(p) }
    if (severity !== 'all') next.severity = severity
    setSearchParams(next)
  }

  const handleMarkRead = (alert: SecurityAlert) => {
    markRead.mutate(alert.id, {
      onError: () => toast.error('Failed to mark alert as read.'),
    })
  }

  const handleDismiss = (alert: SecurityAlert) => {
    dismiss.mutate(alert.id, {
      onSuccess: () => {
        toast.success('Alert dismissed.')
        setConfirmingDismiss(null)
      },
      onError: () => toast.error('Failed to dismiss alert.'),
    })
  }

  const handleMarkAllRead = () => {
    markAllRead.mutate(undefined, {
      onSuccess: () => toast.success('All alerts marked as read.'),
      onError: () => toast.error('Failed to mark alerts as read.'),
    })
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Security Alerts</h1>
          <p className="text-muted-foreground text-sm">
            Security events and notifications
          </p>
        </div>
        <Button variant="outline" size="sm" onClick={handleMarkAllRead} disabled={markAllRead.isPending}>
          <CheckCheck className="mr-1 h-4 w-4" />
          Mark all read
        </Button>
      </div>

      {/* Severity filter */}
      <div className="flex gap-2">
        {SEVERITY_FILTERS.map((s) => (
          <Button
            key={s}
            variant={severity === s ? 'default' : 'outline'}
            size="sm"
            onClick={() => setSeverity(s)}
            className="capitalize"
          >
            {s}
          </Button>
        ))}
      </div>

      {isLoading ? (
        <div className="space-y-2">
          {Array.from({ length: 5 }).map((_, i) => (
            <Skeleton key={i} className="h-20" />
          ))}
        </div>
      ) : alerts.length === 0 ? (
        <EmptyState
          icon={Bell}
          title="No alerts"
          description="Security alerts will appear here when triggered."
        />
      ) : (
        <>
          <div className="space-y-2">
            {alerts.map((alert) => {
              const Icon = severityIcon(alert.severity)
              const isUnread = alert.read_at === null
              const isDismissed = alert.dismissed_at !== null

              return (
                <Card
                  key={alert.id}
                  className={isUnread ? 'border-primary/40' : isDismissed ? 'opacity-60' : ''}
                >
                  <CardContent className="flex items-start gap-3 p-4">
                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded bg-muted">
                      <Icon className="h-4 w-4" />
                    </div>
                    <div className="min-w-0 flex-1">
                      <div className="flex items-center gap-2">
                        <h3 className={`text-sm ${isUnread ? 'font-semibold' : 'font-medium'}`}>
                          {alert.title}
                        </h3>
                        {severityBadge(alert.severity)}
                        {isUnread && (
                          <span className="h-2 w-2 rounded-full bg-primary" />
                        )}
                      </div>
                      <p className="mt-0.5 text-muted-foreground text-sm">{alert.message}</p>
                      <p className="mt-1 text-muted-foreground text-xs">
                        {alert.created_at ? new Date(alert.created_at).toLocaleString() : ''}
                      </p>
                    </div>
                    <div className="flex shrink-0 gap-1">
                      {isUnread && (
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => handleMarkRead(alert)}
                          disabled={markRead.isPending}
                          title="Mark as read"
                        >
                          <Check className="h-4 w-4" />
                        </Button>
                      )}
                      {confirmingDismiss === alert.id ? (
                        <>
                          <Button
                            variant="destructive"
                            size="sm"
                            onClick={() => handleDismiss(alert)}
                            disabled={dismiss.isPending}
                          >
                            Confirm
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setConfirmingDismiss(null)}
                          >
                            <X className="h-4 w-4" />
                          </Button>
                        </>
                      ) : (
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => setConfirmingDismiss(alert.id)}
                          title="Dismiss"
                        >
                          <X className="h-4 w-4" />
                        </Button>
                      )}
                    </div>
                  </CardContent>
                </Card>
              )
            })}
          </div>

          {totalPages > 1 && (
            <div className="flex items-center justify-center gap-2">
              <Button
                variant="outline"
                size="sm"
                disabled={page <= 1}
                onClick={() => setPage(page - 1)}
              >
                <ChevronLeft className="h-4 w-4" />
                Prev
              </Button>
              <span className="text-sm">
                Page {page} of {totalPages}
              </span>
              <Button
                variant="outline"
                size="sm"
                disabled={page >= totalPages}
                onClick={() => setPage(page + 1)}
              >
                Next
                <ChevronRight className="h-4 w-4" />
              </Button>
            </div>
          )}
        </>
      )}
    </div>
  )
}
