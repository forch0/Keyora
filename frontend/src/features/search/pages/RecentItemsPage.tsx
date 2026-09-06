import { Link } from 'react-router-dom'
import {
  Clock,
  File as FileIcon,
  StickyNote,
  KeyRound,
  Plus,
} from 'lucide-react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { EmptyState } from '@/components/shared/EmptyState'
import {
  useRecentItems,
  useRecentCreated,
} from '@/features/search/hooks/use-search'

function formatDate(date: string | null): string {
  if (!date) return '—'
  return new Date(date).toLocaleString()
}

export function RecentItemsPage() {
  const { data: recent, isLoading: recentLoading } = useRecentItems()
  const { data: created, isLoading: createdLoading } = useRecentCreated()

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">Recent Activity</h1>
        <p className="text-muted-foreground text-sm">
          Recently accessed and recently created items
        </p>
      </div>

      {/* Recently accessed */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Clock className="h-5 w-5" />
            Recently Accessed
          </CardTitle>
        </CardHeader>
        <CardContent>
          {recentLoading ? (
            <div className="space-y-2">
              {Array.from({ length: 3 }).map((_, i) => (
                <Skeleton key={i} className="h-12" />
              ))}
            </div>
          ) : !recent ||
            (recent.vault_items.length === 0 &&
              recent.files.length === 0 &&
              recent.notes.length === 0) ? (
            <EmptyState
              icon={Clock}
              title="No recent items"
              description="Items you access will appear here."
            />
          ) : (
            <div className="space-y-4">
              {recent.vault_items.length > 0 && (
                <RecentSection
                  title="Vault Items"
                  icon={KeyRound}
                  items={recent.vault_items.map((item) => ({
                    id: item.id,
                    label: item.name,
                    sublabel: formatDate(item.created_at),
                    href: `/shared/items/${item.id}`,
                  }))}
                />
              )}
              {recent.files.length > 0 && (
                <RecentSection
                  title="Files"
                  icon={FileIcon}
                  items={recent.files.map((file) => ({
                    id: file.id,
                    label: file.name,
                    sublabel: formatDate(file.created_at),
                    href: `/files/${file.id}`,
                  }))}
                />
              )}
              {recent.notes.length > 0 && (
                <RecentSection
                  title="Notes"
                  icon={StickyNote}
                  items={recent.notes.map((note) => ({
                    id: note.id,
                    label: note.title,
                    sublabel: formatDate(note.created_at),
                    href: `/notes/${note.id}`,
                  }))}
                />
              )}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Recently created */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Plus className="h-5 w-5" />
            Recently Created
          </CardTitle>
        </CardHeader>
        <CardContent>
          {createdLoading ? (
            <div className="space-y-2">
              {Array.from({ length: 3 }).map((_, i) => (
                <Skeleton key={i} className="h-12" />
              ))}
            </div>
          ) : !created ||
            (created.vault_items.length === 0 &&
              created.files.length === 0 &&
              created.notes.length === 0) ? (
            <EmptyState
              icon={Plus}
              title="No recent items"
              description="Items you create will appear here."
            />
          ) : (
            <div className="space-y-4">
              {created.vault_items.length > 0 && (
                <RecentSection
                  title="Vault Items"
                  icon={KeyRound}
                  items={created.vault_items.map((item) => ({
                    id: item.id,
                    label: item.name,
                    sublabel: formatDate(item.created_at),
                    href: `/shared/items/${item.id}`,
                  }))}
                />
              )}
              {created.files.length > 0 && (
                <RecentSection
                  title="Files"
                  icon={FileIcon}
                  items={created.files.map((file) => ({
                    id: file.id,
                    label: file.name,
                    sublabel: formatDate(file.created_at),
                    href: `/files/${file.id}`,
                  }))}
                />
              )}
              {created.notes.length > 0 && (
                <RecentSection
                  title="Notes"
                  icon={StickyNote}
                  items={created.notes.map((note) => ({
                    id: note.id,
                    label: note.title,
                    sublabel: formatDate(note.created_at),
                    href: `/notes/${note.id}`,
                  }))}
                />
              )}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}

function RecentSection({
  title,
  icon: Icon,
  items,
}: {
  title: string
  icon: React.ComponentType<{ className?: string }>
  items: {
    id: number
    label: string
    sublabel: string
    href: string
  }[]
}) {
  return (
    <div className="space-y-1">
      <p className="flex items-center gap-1.5 text-muted-foreground text-xs font-medium">
        <Icon className="h-3.5 w-3.5" />
        {title}
      </p>
      {items.map((item) => (
        <Link
          key={item.id}
          to={item.href}
          className="flex items-center gap-2 rounded border p-2 text-sm hover:bg-accent"
        >
          <Icon className="h-4 w-4 shrink-0 text-muted-foreground" />
          <span className="min-w-0 flex-1 truncate">{item.label}</span>
          <span className="shrink-0 text-muted-foreground text-xs">{item.sublabel}</span>
        </Link>
      ))}
    </div>
  )
}
