import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { EmptyState } from '@/components/shared/EmptyState'
import { useSecureLinkActivity } from '@/features/secure-links/hooks/use-secure-links'
import { Activity } from 'lucide-react'

export function LinkActivityDialog({
  linkId,
  open,
  onOpenChange,
}: {
  linkId: number | null
  open: boolean
  onOpenChange: (open: boolean) => void
}) {
  const { data: activity, isLoading } = useSecureLinkActivity(linkId ?? 0)

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Link Activity</DialogTitle>
          <DialogDescription>Who has accessed this secure link</DialogDescription>
        </DialogHeader>

        {isLoading ? (
          <div className="space-y-2">
            {Array.from({ length: 3 }).map((_, i) => (
              <Skeleton key={i} className="h-16" />
            ))}
          </div>
        ) : !activity || activity.length === 0 ? (
          <EmptyState
            icon={Activity}
            title="No activity yet"
            description="This link has not been accessed."
          />
        ) : (
          <div className="max-h-96 space-y-2 overflow-y-auto">
            {activity.map((access) => (
              <div
                key={access.id}
                className="flex items-center gap-3 rounded border p-3 text-sm"
              >
                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-muted">
                  {access.email?.charAt(0).toUpperCase() ?? '?'}
                </div>
                <div className="min-w-0 flex-1">
                  <p className="truncate font-medium">
                    {access.email ?? 'Anonymous'}
                  </p>
                  <p className="truncate text-muted-foreground text-xs">
                    {access.ip_address} · {access.user_agent}
                  </p>
                </div>
                <span className="shrink-0 text-muted-foreground text-xs">
                  {access.accessed_at
                    ? new Date(access.accessed_at).toLocaleString()
                    : '—'}
                </span>
              </div>
            ))}
          </div>
        )}

        <div className="flex justify-end">
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Close
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  )
}
