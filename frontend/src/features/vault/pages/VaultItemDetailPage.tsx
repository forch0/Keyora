import { useParams, useNavigate, Link } from 'react-router-dom'
import {
  ArrowLeft,
  Star,
  Pencil,
  Trash2,
  Archive,
  Globe,
  User as UserIcon,
  FileText,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { Separator } from '@/components/ui/separator'
import { CopyButton } from '@/components/shared/CopyButton'
import { PasswordField } from '@/components/shared/PasswordField'
import { ItemTypeIcon, itemTypeLabel } from '@/components/shared/ItemTypeIcon'
import { EmptyState } from '@/components/shared/EmptyState'
import { useVaultItem, useToggleFavorite } from '@/features/vault/hooks/use-vault-items'
import { Lock } from 'lucide-react'

export function VaultItemDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const itemId = parseInt(id ?? '0', 10)

  const { data: item, isLoading, isError } = useVaultItem(itemId)
  const toggleFavorite = useToggleFavorite()

  const handleToggleFavorite = () => {
    if (!item) return
    toggleFavorite.mutate({ id: item.id, favorite: !item.favorite })
  }

  if (isLoading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-32" />
        <Skeleton className="h-64 w-full" />
      </div>
    )
  }

  if (isError || !item) {
    return (
      <EmptyState
        icon={Lock}
        title="Item not found"
        description="This vault item may have been deleted."
        action={
          <Button variant="outline" onClick={() => navigate('/vault')}>
            Back to vault
          </Button>
        }
      />
    )
  }

  return (
    <div className="space-y-4">
      {/* Back link */}
      <Link to="/vault">
        <Button variant="ghost" size="sm">
          <ArrowLeft className="mr-2 h-4 w-4" />
          Back to vault
        </Button>
      </Link>

      {/* Header */}
      <div className="flex items-start justify-between gap-4">
        <div className="flex items-start gap-3">
          <div className="mt-0.5 rounded-md bg-muted p-2">
            <ItemTypeIcon type={item.type} className="h-6 w-6" />
          </div>
          <div>
            <h1 className="text-2xl font-bold">{item.name}</h1>
            <div className="mt-1 flex items-center gap-2">
              <Badge variant="secondary">{itemTypeLabel(item.type)}</Badge>
              {item.archived_at && <Badge variant="outline">Archived</Badge>}
              {item.favorite && (
                <Star className="h-4 w-4 fill-yellow-400 text-yellow-400" />
              )}
            </div>
          </div>
        </div>

        <div className="flex items-center gap-2">
          <Button
            variant="ghost"
            size="sm"
            onClick={handleToggleFavorite}
            disabled={toggleFavorite.isPending}
          >
            <Star
              className={
                item.favorite
                  ? 'h-4 w-4 fill-yellow-400 text-yellow-400'
                  : 'h-4 w-4'
              }
            />
            {item.favorite ? 'Unfavorite' : 'Favorite'}
          </Button>
          <Button variant="outline" size="sm" disabled title="Module F05">
            <Pencil className="mr-2 h-4 w-4" />
            Edit
          </Button>
          <Button variant="outline" size="sm" disabled title="Module F05">
            <Archive className="mr-2 h-4 w-4" />
            Archive
          </Button>
          <Button variant="destructive" size="sm" disabled title="Module F05">
            <Trash2 className="mr-2 h-4 w-4" />
            Delete
          </Button>
        </div>
      </div>

      {/* Tags */}
      {item.tags && item.tags.length > 0 && (
        <div className="flex flex-wrap gap-1.5">
          {item.tags.map((tag) => (
            <Badge
              key={tag.id}
              variant="outline"
              style={tag.color ? { borderColor: tag.color } : undefined}
            >
              {tag.name}
            </Badge>
          ))}
        </div>
      )}

      {/* Fields */}
      <Card>
        <CardHeader>
          <CardTitle>Details</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Username */}
          {item.username && (
            <div className="space-y-1">
              <label className="flex items-center gap-1.5 text-muted-foreground text-sm">
                <UserIcon className="h-3.5 w-3.5" />
                Username
              </label>
              <div className="flex items-center gap-2">
                <code className="flex-1 rounded bg-muted px-3 py-2 text-sm">
                  {item.username}
                </code>
                <CopyButton value={item.username} />
              </div>
            </div>
          )}

          {/* Password */}
          {item.password && (
            <div className="space-y-1">
              <label className="flex items-center gap-1.5 text-muted-foreground text-sm">
                <Lock className="h-3.5 w-3.5" />
                Password
              </label>
              <PasswordField value={item.password} />
            </div>
          )}

          {/* URL */}
          {item.url && (
            <div className="space-y-1">
              <label className="flex items-center gap-1.5 text-muted-foreground text-sm">
                <Globe className="h-3.5 w-3.5" />
                URL
              </label>
              <div className="flex items-center gap-2">
                <a
                  href={item.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="flex-1 truncate text-primary text-sm hover:underline"
                >
                  {item.url}
                </a>
                <CopyButton value={item.url} />
              </div>
            </div>
          )}

          {/* Custom fields */}
          {item.custom_fields && item.custom_fields.length > 0 && (
            <>
              <Separator />
              <div className="space-y-3">
                <h4 className="font-medium text-sm">Custom Fields</h4>
                {item.custom_fields.map((field, idx) => (
                  <div key={idx} className="space-y-1">
                    <label className="text-muted-foreground text-sm">{field.key}</label>
                    <div className="flex items-center gap-2">
                      <code className="flex-1 rounded bg-muted px-3 py-2 text-sm">
                        {field.value}
                      </code>
                      <CopyButton value={field.value} />
                    </div>
                  </div>
                ))}
              </div>
            </>
          )}

          {/* Notes */}
          {item.notes && (
            <>
              <Separator />
              <div className="space-y-1">
                <label className="flex items-center gap-1.5 text-muted-foreground text-sm">
                  <FileText className="h-3.5 w-3.5" />
                  Notes
                </label>
                <p className="whitespace-pre-wrap rounded bg-muted p-3 text-sm">
                  {item.notes}
                </p>
              </div>
            </>
          )}
        </CardContent>
      </Card>

      {/* Metadata */}
      <Card>
        <CardContent className="flex flex-wrap gap-4 py-4 text-muted-foreground text-sm">
          <div>
            <span className="font-medium">Created:</span>{' '}
            {item.created_at ? new Date(item.created_at).toLocaleDateString() : '—'}
          </div>
          <div>
            <span className="font-medium">Updated:</span>{' '}
            {item.updated_at ? new Date(item.updated_at).toLocaleDateString() : '—'}
          </div>
          <div>
            <span className="font-medium">Last accessed:</span>{' '}
            {item.last_accessed_at
              ? new Date(item.last_accessed_at).toLocaleDateString()
              : 'Never'}
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
