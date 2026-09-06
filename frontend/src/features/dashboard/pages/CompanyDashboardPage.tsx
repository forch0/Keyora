import {
  Users,
  FolderLock,
  FileLock,
  Shield,
  KeyRound,
  AlertTriangle,
  Activity as ActivityIcon,
  Clock,
} from 'lucide-react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { StatCard } from '@/components/shared/StatCard'
import { ActivityFeed } from '@/components/shared/ActivityFeed'
import { useCompanyDashboard, useUsageDashboard } from '@/features/dashboard/hooks/use-dashboard'

export function CompanyDashboardPage() {
  const { data: company, isLoading } = useCompanyDashboard()
  const { data: usage } = useUsageDashboard()

  if (isLoading) {
    return (
      <div className="space-y-6">
        <Skeleton className="h-8 w-48" />
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {Array.from({ length: 4 }).map((_, i) => (
            <Skeleton key={i} className="h-24" />
          ))}
        </div>
        <Skeleton className="h-64 w-full" />
      </div>
    )
  }

  if (!company) return null

  const overview = company.overview

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">Company Dashboard</h1>
        <p className="text-muted-foreground text-sm">Organization-wide metrics and activity.</p>
      </div>

      {/* Overview stats */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard icon={Users} label="Total Members" value={overview.total_members} />
        <StatCard
          icon={Users}
          label="Active Members"
          value={overview.active_members}
          description={`${overview.suspended_members} suspended`}
        />
        <StatCard icon={FolderLock} label="Vault Items" value={overview.total_vault_items} />
        <StatCard
          icon={FileLock}
          label="Files"
          value={overview.total_files}
          description={`${overview.total_notes} notes`}
        />
      </div>

      {/* Access + security */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard
          icon={KeyRound}
          label="Pending Requests"
          value={company.access_requests.pending}
        />
        <StatCard
          icon={Clock}
          label="Active Temp Access"
          value={company.temporary_access.active}
          description={`${company.temporary_access.expiring_24h} expiring 24h`}
        />
        <StatCard
          icon={AlertTriangle}
          label="Recent Alerts"
          value={company.security_activity.recent_alerts}
        />
        <StatCard
          icon={Shield}
          label="Failed Logins (24h)"
          value={company.security_activity.failed_logins_24h}
        />
      </div>

      {/* Usage metrics */}
      {usage && (
        <Card>
          <CardHeader>
            <CardTitle>Usage</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-3">
            <UsageBar
              label="Members"
              used={usage.usage.members}
              limit={Number(usage.limits.max_members)}
            />
            <UsageBar
              label="Storage (MB)"
              used={usage.usage.storage_used_mb}
              limit={Number(usage.limits.max_storage_mb)}
            />
            <UsageBar
              label="Vault Items"
              used={usage.usage.vault_items}
              limit={Number(usage.limits.max_vault_items)}
            />
          </CardContent>
        </Card>
      )}

      {/* Recent activity */}
      <div>
        <h2 className="mb-3 flex items-center gap-2 text-lg font-semibold">
          <ActivityIcon className="h-5 w-5" />
          Recent Activity
        </h2>
        <ActivityFeed items={company.recent_activity} title="Company Activity" />
      </div>
    </div>
  )
}

function UsageBar({ label, used, limit }: { label: string; used: number; limit: number }) {
  const pct = limit > 0 ? Math.min(100, (used / limit) * 100) : 0
  const isHigh = pct >= 80

  return (
    <div className="space-y-1">
      <div className="flex items-center justify-between text-sm">
        <span className="text-muted-foreground">{label}</span>
        <span className="font-medium">
          {used} / {limit}
        </span>
      </div>
      <div className="h-2 overflow-hidden rounded-full bg-muted">
        <div
          className={`h-full rounded-full transition-all ${isHigh ? 'bg-orange-500' : 'bg-primary'}`}
          style={{ width: `${pct}%` }}
        />
      </div>
    </div>
  )
}
