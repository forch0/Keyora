import { Activity, ChevronLeft, ChevronRight } from 'lucide-react'
import { useState } from 'react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { Button } from '@/components/ui/button'
import { EmptyState } from '@/components/shared/EmptyState'
import { useResourceActivityLogs } from '@/features/activity-logs/hooks/use-activity-logs'
import type { ActivityLogResourceType } from '@/types/activity-log'

const PER_PAGE = 10

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
  if (type.includes('Team')) return 'Team'
  if (type.includes('User')) return 'User'
  return type.split('\\').pop() ?? type
}

export function ResourceActivityPanel({
  resource,
  id,
}: {
  resource: ActivityLogResourceType
  id: number
}) {
  const [page, setPage] = useState(1)
  const { data, isLoading } = useResourceActivityLogs(resource, id, {
    page,
    per_page: PER_PAGE,
  })

  const logs = data?.data ?? []
  const totalPages = data?.last_page ?? 1

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Activity className="h-5 w-5" />
          Activity History
        </CardTitle>
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <div className="space-y-2">
            {Array.from({ length: 4 }).map((_, i) => (
              <Skeleton key={i} className="h-12" />
            ))}
          </div>
        ) : logs.length === 0 ? (
          <EmptyState
            icon={Activity}
            title="No activity yet"
            description="Actions on this resource will appear here."
          />
        ) : (
          <>
            <div className="space-y-1">
              {logs.map((log) => (
                <div
                  key={log.id}
                  className="flex items-start gap-3 rounded border p-2.5 text-sm"
                >
                  <div className="mt-1 h-2 w-2 shrink-0 rounded-full bg-primary/60" />
                  <div className="min-w-0 flex-1">
                    <p className="font-medium">{actionLabel(log.action)}</p>
                    <div className="mt-0.5 flex flex-wrap items-center gap-x-2 text-muted-foreground text-xs">
                      {log.user?.name && <span>by {log.user.name}</span>}
                      {log.subject?.type && (
                        <>
                          <span>·</span>
                          <span>{subjectLabel(log.subject.type)}</span>
                        </>
                      )}
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

            {totalPages > 1 && (
              <div className="mt-4 flex items-center justify-center gap-2">
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
      </CardContent>
    </Card>
  )
}
