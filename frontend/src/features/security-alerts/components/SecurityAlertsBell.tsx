import { Link } from 'react-router-dom'
import { Bell, CheckCheck, AlertTriangle, Info, ShieldAlert } from 'lucide-react'
import { toast } from 'sonner'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import {
  useUnreadAlertCount,
  useSecurityAlerts,
  useMarkAllAlertsRead,
} from '@/features/security-alerts/hooks/use-security-alerts'
import type { SecurityAlert } from '@/types/security-alert'

function severityIcon(severity: string) {
  if (severity === 'critical') return ShieldAlert
  if (severity === 'warning') return AlertTriangle
  return Info
}

function severityColor(severity: string): string {
  if (severity === 'critical') return 'text-destructive'
  if (severity === 'warning') return 'text-yellow-600'
  return 'text-blue-500'
}

function timeAgo(date: string | null): string {
  if (!date) return ''
  const diff = Date.now() - new Date(date).getTime()
  const min = Math.floor(diff / 60000)
  if (min < 1) return 'just now'
  if (min < 60) return `${min}m ago`
  const hr = Math.floor(min / 60)
  if (hr < 24) return `${hr}h ago`
  return `${Math.floor(hr / 24)}d ago`
}

export function SecurityAlertsBell() {
  const { data: unreadData } = useUnreadAlertCount()
  const { data: alertsData, isLoading } = useSecurityAlerts({ per_page: 5 })
  const markAllRead = useMarkAllAlertsRead()

  const unreadCount = unreadData?.count ?? 0
  const alerts = alertsData?.data ?? []

  const handleMarkAllRead = () => {
    markAllRead.mutate(undefined, {
      onSuccess: () => toast.success('All alerts marked as read.'),
      onError: () => toast.error('Failed to mark alerts as read.'),
    })
  }

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <button
          className="relative rounded-md p-2 hover:bg-accent"
          aria-label="Security alerts"
        >
          <Bell className="h-5 w-5" />
          {unreadCount > 0 && (
            <span className="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-bold text-destructive-foreground">
              {unreadCount > 99 ? '99+' : unreadCount}
            </span>
          )}
        </button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-80">
        <div className="flex items-center justify-between px-2 py-1.5">
          <DropdownMenuLabel className="p-0 text-sm font-medium">
            Security Alerts
          </DropdownMenuLabel>
          {unreadCount > 0 && (
            <Button
              variant="ghost"
              size="sm"
              className="h-7 text-xs"
              onClick={handleMarkAllRead}
              disabled={markAllRead.isPending}
            >
              <CheckCheck className="mr-1 h-3 w-3" />
              Mark all read
            </Button>
          )}
        </div>
        <DropdownMenuSeparator />

        {isLoading ? (
          <div className="space-y-2 p-2">
            {Array.from({ length: 3 }).map((_, i) => (
              <Skeleton key={i} className="h-12" />
            ))}
          </div>
        ) : alerts.length === 0 ? (
          <div className="py-6 text-center">
            <Bell className="mx-auto mb-1 h-6 w-6 text-muted-foreground" />
            <p className="text-muted-foreground text-sm">No alerts</p>
          </div>
        ) : (
          alerts.map((alert) => <AlertItem key={alert.id} alert={alert} />)
        )}

        <DropdownMenuSeparator />
        <DropdownMenuItem asChild>
          <Link to="/security-alerts" className="w-full justify-center text-sm">
            View all alerts
          </Link>
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  )
}

function AlertItem({ alert }: { alert: SecurityAlert }) {
  const Icon = severityIcon(alert.severity)
  const isUnread = alert.read_at === null

  return (
    <DropdownMenuItem asChild>
      <Link
        to="/security-alerts"
        className={`flex items-start gap-2 py-2 ${isUnread ? 'font-medium' : 'opacity-70'}`}
      >
        <Icon className={`mt-0.5 h-4 w-4 shrink-0 ${severityColor(alert.severity)}`} />
        <div className="min-w-0 flex-1">
          <p className="truncate text-sm">{alert.title}</p>
          <p className="truncate text-muted-foreground text-xs">{alert.message}</p>
          <p className="mt-0.5 text-muted-foreground text-xs">{timeAgo(alert.created_at)}</p>
        </div>
        {isUnread && (
          <span className="mt-1 h-2 w-2 shrink-0 rounded-full bg-primary" />
        )}
      </Link>
    </DropdownMenuItem>
  )
}
