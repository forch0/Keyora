import { Link } from 'react-router-dom'
import {
  Lock,
  Star,
  Archive,
  Activity as ActivityIcon,
  Plus,
  Wand2,
  AlertTriangle,
  Inbox,
  Send,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { StatCard } from '@/components/shared/StatCard'
import { RecentItemsList } from '@/components/shared/RecentItemsList'
import { usePersonalDashboard } from '@/features/dashboard/hooks/use-dashboard'
import { useRecentVaultItems } from '@/features/vault/hooks/use-vault-items'
import { useAuthStore } from '@/stores/auth-store'
import type { VaultItem } from '@/types/vault'

export function PersonalDashboardPage() {
  const user = useAuthStore((s) => s.user)
  const { data: dashboard, isLoading } = usePersonalDashboard()
  const { data: recentItems } = useRecentVaultItems()

  const totalItems = dashboard ? Number(dashboard.vault_summary.total_items) : 0
  const favoritesCount = dashboard ? Number(dashboard.vault_summary.favorites_count) : 0
  const archivedCount = dashboard ? Number(dashboard.vault_summary.archived_count) : 0

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">
            Welcome back, {user?.name?.split(' ')[0] ?? 'User'}
          </h1>
          <p className="text-muted-foreground text-sm">Here's an overview of your vault.</p>
        </div>
        <div className="flex gap-2">
          <Button asChild variant="outline" size="sm">
            <Link to="/tools">
              <Wand2 className="mr-2 h-4 w-4" />
              Generate Password
            </Link>
          </Button>
          <Button asChild size="sm">
            <Link to="/vault/new">
              <Plus className="mr-2 h-4 w-4" />
              Add Item
            </Link>
          </Button>
        </div>
      </div>

      {/* Expiring access warning */}
      {dashboard && dashboard.expiring_access.count > 0 && (
        <Card className="border-yellow-500/50 bg-yellow-500/5">
          <CardContent className="flex items-center gap-3 p-4">
            <AlertTriangle className="h-5 w-5 shrink-0 text-yellow-600" />
            <p className="text-sm">
              <span className="font-medium">{dashboard.expiring_access.count}</span> shared
            access {dashboard.expiring_access.count === 1 ? 'item is' : 'items are'} expiring
            soon.
            </p>
          </CardContent>
        </Card>
      )}

      {/* Stats */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {isLoading ? (
          Array.from({ length: 4 }).map((_, i) => <Skeleton key={i} className="h-24" />)
        ) : (
          <>
            <StatCard icon={Lock} label="Total Items" value={totalItems} />
            <StatCard icon={Star} label="Favorites" value={favoritesCount} />
            <StatCard icon={Archive} label="Archived" value={archivedCount} />
            <StatCard
              icon={ActivityIcon}
              label="Security Alerts"
              value={dashboard?.security_alerts_unread ?? 0}
            />
          </>
        )}
      </div>

      {/* Pending requests */}
      {dashboard && (dashboard.pending_requests.sent > 0 || dashboard.pending_requests.received > 0) && (
        <div className="grid gap-4 sm:grid-cols-2">
          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="flex items-center gap-2 text-base">
                <Send className="h-4 w-4" />
                Sent Requests
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">{dashboard.pending_requests.sent}</p>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="flex items-center gap-2 text-base">
                <Inbox className="h-4 w-4" />
                Received Requests
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">{dashboard.pending_requests.received}</p>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Recent items */}
      <div>
        <h2 className="mb-3 text-lg font-semibold">Recent Items</h2>
        <RecentItemsList items={recentItems as VaultItem[] | undefined} title="Last Accessed" />
      </div>
    </div>
  )
}
