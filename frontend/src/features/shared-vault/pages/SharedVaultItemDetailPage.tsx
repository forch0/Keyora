import { useState } from 'react'
import { useParams, useNavigate, Link } from 'react-router-dom'
import {
  ArrowLeft,
  Pencil,
  Trash2,
  Globe,
  User as UserIcon,
  FileText,
  Lock,
} from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { Separator } from '@/components/ui/separator'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { CopyButton } from '@/components/shared/CopyButton'
import { PasswordField } from '@/components/shared/PasswordField'
import { ItemTypeIcon, itemTypeLabel } from '@/components/shared/ItemTypeIcon'
import { EmptyState } from '@/components/shared/EmptyState'
import { AccessManagementPanel } from '@/features/access/components/AccessManagementPanel'
import { SecureLinksPanel } from '@/features/secure-links/components/SecureLinksPanel'
import {
  useOrgVaultItem,
  useDeleteOrgVaultItem,
} from '@/features/shared-vault/hooks/use-shared-vault'

export function SharedVaultItemDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const itemId = parseInt(id ?? '0', 10)

  const { data: item, isLoading, isError } = useOrgVaultItem(itemId)
  const deleteItem = useDeleteOrgVaultItem()
  const [showDeleteDialog, setShowDeleteDialog] = useState(false)

  const handleDelete = () => {
    deleteItem.mutate(itemId, {
      onSuccess: () => {
        toast.success('Item deleted.')
        navigate('/shared', { replace: true })
      },
      onError: () => toast.error('Failed to delete item. You may not have permission.'),
    })
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
        description="This item may have been deleted, or you don't have access."
        action={
          <Button variant="outline" onClick={() => navigate('/shared')}>
            Back to shared vault
          </Button>
        }
      />
    )
  }

  return (
    <div className="space-y-4">
      <Link to="/shared">
        <Button variant="ghost" size="sm">
          <ArrowLeft className="mr-2 h-4 w-4" />
          Back to shared vault
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
              {item.team_id ? (
                <Badge variant="outline">Team item</Badge>
              ) : (
                <Badge variant="outline">Org item</Badge>
              )}
            </div>
          </div>
        </div>

        <div className="flex items-center gap-2">
          <Link to={`/shared/items/${itemId}/edit`}>
            <Button variant="outline" size="sm">
              <Pencil className="mr-2 h-4 w-4" />
              Edit
            </Button>
          </Link>
          <Button
            variant="destructive"
            size="sm"
            onClick={() => setShowDeleteDialog(true)}
          >
            <Trash2 className="mr-2 h-4 w-4" />
            Delete
          </Button>
        </div>
      </div>

      {/* Fields */}
      <Card>
        <CardHeader>
          <CardTitle>Details</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
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

          {item.password && (
            <div className="space-y-1">
              <label className="flex items-center gap-1.5 text-muted-foreground text-sm">
                <Lock className="h-3.5 w-3.5" />
                Password
              </label>
              <PasswordField value={item.password} />
            </div>
          )}

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
        </CardContent>
      </Card>

      {/* Access management */}
      <AccessManagementPanel resource="vault/items" id={item.id} />

      {/* Secure links */}
      <SecureLinksPanel resource="vault/items" id={item.id} />

      {/* Delete dialog */}
      <Dialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Delete shared item?</DialogTitle>
            <DialogDescription>
              Are you sure you want to delete &ldquo;{item.name}&rdquo;? This action cannot be
              undone.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setShowDeleteDialog(false)}>
              Cancel
            </Button>
            <Button
              variant="destructive"
              onClick={handleDelete}
              disabled={deleteItem.isPending}
            >
              {deleteItem.isPending ? 'Deleting...' : 'Delete'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  )
}
