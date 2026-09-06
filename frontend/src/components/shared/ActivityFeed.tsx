import { Activity } from 'lucide-react'

interface ActivityFeedProps {
  items: unknown[] | undefined
  title?: string
}

interface ActivityEntry {
  id?: number
  description?: string
  event?: string
  message?: string
  created_at?: string
  created_at_human?: string
  user?: { name?: string }
  type?: string
}

function getEntryText(entry: ActivityEntry): string {
  return entry.description || entry.event || entry.message || 'Activity event'
}

function getEntryTime(entry: ActivityEntry): string {
  return entry.created_at_human || (entry.created_at ? new Date(entry.created_at).toLocaleString() : '')
}

export function ActivityFeed({ items, title = 'Recent Activity' }: ActivityFeedProps) {
  if (!items || items.length === 0) {
    return (
      <div className="rounded-lg border p-6 text-center">
        <Activity className="mx-auto h-8 w-8 text-muted-foreground" />
        <p className="mt-2 text-muted-foreground text-sm">No recent activity</p>
      </div>
    )
  }

  return (
    <div className="rounded-lg border">
      <h3 className="border-b px-4 py-2 font-medium text-sm">{title}</h3>
      <div className="divide-y">
        {items.slice(0, 10).map((item, idx) => {
          const entry = item as ActivityEntry
          return (
            <div key={entry.id ?? idx} className="flex items-start gap-3 px-4 py-2.5">
              <div className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-primary/60" />
              <div className="min-w-0 flex-1">
                <p className="text-sm">{getEntryText(entry)}</p>
                <p className="text-muted-foreground text-xs">{getEntryTime(entry)}</p>
              </div>
            </div>
          )
        })}
      </div>
    </div>
  )
}
