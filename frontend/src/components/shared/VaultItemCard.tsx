import { useNavigate } from 'react-router-dom'
import { Star } from 'lucide-react'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { ItemTypeIcon, itemTypeLabel } from '@/components/shared/ItemTypeIcon'
import type { VaultItem } from '@/types/vault'

interface VaultItemCardProps {
  item: VaultItem
}

export function VaultItemCard({ item }: VaultItemCardProps) {
  const navigate = useNavigate()

  return (
    <Card
      className="cursor-pointer p-4 transition-colors hover:bg-accent"
      onClick={() => navigate(`/vault/items/${item.id}`)}
    >
      <div className="flex items-start gap-3">
        <div className="mt-0.5 shrink-0">
          <ItemTypeIcon type={item.type} />
        </div>

        <div className="min-w-0 flex-1">
          <div className="flex items-center gap-2">
            <h3 className="truncate font-medium">{item.name}</h3>
            {item.favorite && (
              <Star className="h-4 w-4 shrink-0 fill-yellow-400 text-yellow-400" />
            )}
          </div>

          {item.username && (
            <p className="mt-0.5 truncate text-muted-foreground text-sm">{item.username}</p>
          )}

          <div className="mt-2 flex flex-wrap items-center gap-1.5">
            <Badge variant="secondary" className="text-xs">
              {itemTypeLabel(item.type)}
            </Badge>
            {item.tags?.map((tag) => (
              <Badge
                key={tag.id}
                variant="outline"
                className="text-xs"
                style={tag.color ? { borderColor: tag.color } : undefined}
              >
                {tag.name}
              </Badge>
            ))}
          </div>
        </div>
      </div>
    </Card>
  )
}
