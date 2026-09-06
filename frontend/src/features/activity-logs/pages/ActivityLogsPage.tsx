import { useSearchParams } from 'react-router-dom'
import {
  Activity,
  ChevronLeft,
  ChevronRight,
  User,
  Building2,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { EmptyState } from '@/components/shared/EmptyState'
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '@/components/ui/tabs'
import {
  usePersonalActivityLogs,
  useCompanyActivityLogs,
} from '@/features/activity-logs/hooks/use-activity-logs'
import { useTenantMembers } from '@/features/tenant/hooks/use-tenant-members'
import type { ActivityLog, PaginatedActivityLogs } from '@/types/activity-log'

const PER_PAGE = 25

function actionLabel(action: string): string {
  return action
    .split('_')
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ')
}

function subjectLabel(type: string | null | undefined): string {
  if (!type) return ''
  if (type.includes('VaultItem')) return 'Vault Item'
  if (type.includes('SecureFile')) return 'File'
  if (type.includes('SecureNote')) return 'Note'
  if (type.includes('SecureLink')) return 'Secure Link'
  if (type.includes('AccessGrant')) return 'Access Grant'
  if (type.includes('AccessRequest')) return 'Access Request'
  if (type.includes('Team')) return 'Team'
  if (type.includes('User')) return 'User'
  return type.split('\\').pop() ?? type
}

export function ActivityLogsPage() {
  const [searchParams, setSearchParams] = useSearchParams()
  const tab = searchParams.get('tab') ?? 'personal'
  const page = parseInt(searchParams.get('page') ?? '1', 10) || 1

  const { data: members } = useTenantMembers()
  const isAdmin = !!members?.some((m) => m.role === 'admin' || m.role === 'owner')

  const setTab = (newTab: string) => setSearchParams({ tab: newTab })
  const setPage = (newPage: number) => setSearchParams({ tab, page: String(newPage) })

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">Activity Logs</h1>
        <p className="text-muted-foreground text-sm">
          Track actions across your account and organization
        </p>
      </div>

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList>
          <TabsTrigger value="personal" className="flex items-center gap-1.5">
            <User className="h-3.5 w-3.5" />
            Personal
          </TabsTrigger>
          {isAdmin && (
            <TabsTrigger value="company" className="flex items-center gap-1.5">
              <Building2 className="h-3.5 w-3.5" />
              Company
            </TabsTrigger>
          )}
        </TabsList>

        <TabsContent value="personal">
          <PersonalActivityList page={page} onPageChange={setPage} />
        </TabsContent>

        {isAdmin && (
          <TabsContent value="company">
            <CompanyActivityList page={page} onPageChange={setPage} />
          </TabsContent>
        )}
      </Tabs>
    </div>
  )
}

function PersonalActivityList({ page, onPageChange }: { page: number; onPageChange: (p: number) => void }) {
  const { data, isLoading } = usePersonalActivityLogs({ page, per_page: PER_PAGE })
  return <ActivityLogListView data={data} isLoading={isLoading} page={page} onPageChange={onPageChange} />
}

function CompanyActivityList({ page, onPageChange }: { page: number; onPageChange: (p: number) => void }) {
  const { data, isLoading } = useCompanyActivityLogs({ page, per_page: PER_PAGE })
  return <ActivityLogListView data={data} isLoading={isLoading} page={page} onPageChange={onPageChange} />
}

function ActivityLogListView({
  data,
  isLoading,
  page,
  onPageChange,
}: {
  data: PaginatedActivityLogs | undefined
  isLoading: boolean
  page: number
  onPageChange: (p: number) => void
}) {
  const logs = data?.data ?? []
  const totalPages = data?.last_page ?? 1

  if (isLoading) {
    return (
      <div className="space-y-2">
        {Array.from({ length: 5 }).map((_, i) => (
          <Skeleton key={i} className="h-16" />
        ))}
      </div>
    )
  }

  if (logs.length === 0) {
    return (
      <EmptyState
        icon={Activity}
        title="No activity yet"
        description="Actions you take will be logged here."
      />
    )
  }

  return (
    <div className="space-y-3">
      <Card>
        <CardContent className="p-0">
          <div className="divide-y">
            {logs.map((log: ActivityLog) => (
              <div key={log.id} className="flex items-start gap-3 px-4 py-3">
                <div className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-primary/60" />
                <div className="min-w-0 flex-1">
                  <div className="flex items-center gap-2">
                    <span className="font-medium text-sm">{actionLabel(log.action)}</span>
                    {log.subject?.type && (
                      <Badge variant="outline" className="text-xs">
                        {subjectLabel(log.subject.type)}
                      </Badge>
                    )}
                  </div>
                  <div className="mt-0.5 flex flex-wrap items-center gap-x-2 text-muted-foreground text-xs">
                    {log.user?.name && <span>by {log.user.name}</span>}
                    {log.ip_address && (
                      <>
                        <span>·</span>
                        <span>{log.ip_address}</span>
                      </>
                    )}
                  </div>
                </div>
                <span className="shrink-0 text-muted-foreground text-xs">
                  {new Date(log.created_at).toLocaleString()}
                </span>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {totalPages > 1 && (
        <div className="flex items-center justify-center gap-2">
          <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => onPageChange(page - 1)}>
            <ChevronLeft className="h-4 w-4" />
            Prev
          </Button>
          <span className="text-sm">Page {page} of {totalPages}</span>
          <Button variant="outline" size="sm" disabled={page >= totalPages} onClick={() => onPageChange(page + 1)}>
            Next
            <ChevronRight className="h-4 w-4" />
          </Button>
        </div>
      )}
    </div>
  )
}
