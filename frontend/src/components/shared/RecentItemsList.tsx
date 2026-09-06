import { useNavigate } from 'react-router-dom'
import { Lock, ChevronRight } from 'lucide-react'
import { ItemTypeIcon } from '@/components/shared/ItemTypeIcon'
import type { VaultItem } from '@/types/vault'

interface RecentItemsListProps {
  items: VaultItem[] | undefined
  title?: string
}

export function RecentItemsList({ items, title = 'Recent Items' }: RecentItemsListProps) {
  const navigate = useNavigate()

  if (!items || items.length === 0) {
    return (
      <div className="rounded-lg border p-6 text-center">
        <Lock className="mx-auto h-8 w-8 text-muted-foreground" />
        <p className="mt-2 text-muted-foreground text-sm">No recent items</p>
      </div>
    )
  }

  return (
    <div className="rounded-lg border">
      <h3 className="border-b px-4 py-2 font-medium text-sm">{title}</h3>
      <div className="divide-y">
        {items.slice(0, 5).map((item) => (
          <button
            key={item.id}
            onClick={() => navigate(`/vault/items/${item.id}`)}
            className="flex w-full items-center gap-3 px-4 py-2.5 text-left hover:bg-accent"
          >
            <ItemTypeIcon type={item.type} className="h-4 w-4 shrink-0 text-muted-foreground" />
            <div className="min-w-0 flex-1">
              <p className="truncate text-sm font-medium">{item.name}</p>
              {item.username && (
                <p className="truncate text-muted-foreground text-xs">{item.username}</p>
              )}
            </div>
            <ChevronRight className="h-4 w-4 shrink-0 text-muted-foreground" />
          </button>
        ))}
      </div>
    </div>
  )
}
