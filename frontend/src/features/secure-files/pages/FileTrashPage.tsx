import { useState } from 'react'
import { Link } from 'react-router-dom'
import {
  File as FileIcon,
  Trash2,
  RotateCcw,
  ArrowLeft,
  Loader2,
} from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { EmptyState } from '@/components/shared/EmptyState'
import {
  useFileTrash,
  useRestoreFromTrash,
  useForceDeleteFile,
  useEmptyTrash,
} from '@/features/secure-files/hooks/use-secure-files'
import type { SecureFile } from '@/types/secure-file'

function formatBytes(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

export function FileTrashPage() {
  const { data: trashData, isLoading } = useFileTrash({ per_page: 50 })
  const restoreMutation = useRestoreFromTrash()
  const forceDeleteMutation = useForceDeleteFile()
  const emptyTrashMutation = useEmptyTrash()

  const [confirmingEmpty, setConfirmingEmpty] = useState(false)

  const files = trashData?.data ?? []

  const handleRestore = (file: SecureFile) => {
    restoreMutation.mutate(file.id, {
      onSuccess: () => toast.success(`"${file.name}" restored.`),
      onError: () => toast.error('Failed to restore file.'),
    })
  }

  const handleForceDelete = (file: SecureFile) => {
    if (!confirm(`Permanently delete "${file.name}"? This cannot be undone.`)) return
    forceDeleteMutation.mutate(file.id, {
      onSuccess: () => toast.success('File permanently deleted.'),
      onError: () => toast.error('Failed to delete file.'),
    })
  }

  const handleEmptyTrash = () => {
    emptyTrashMutation.mutate(undefined, {
      onSuccess: () => {
        toast.success('Trash emptied.')
        setConfirmingEmpty(false)
      },
      onError: () => toast.error('Failed to empty trash.'),
    })
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="sm" asChild>
            <Link to="/files">
              <ArrowLeft className="mr-1 h-4 w-4" />
              Files
            </Link>
          </Button>
          <h1 className="text-2xl font-bold">Trash</h1>
        </div>
        {files.length > 0 && (
          <Button
            variant="destructive"
            size="sm"
            onClick={() => setConfirmingEmpty(true)}
            disabled={emptyTrashMutation.isPending}
          >
            <Trash2 className="mr-1 h-4 w-4" />
            Empty Trash
          </Button>
        )}
      </div>

      {confirmingEmpty && (
        <Card className="border-destructive">
          <CardContent className="flex items-center justify-between py-4">
            <p className="text-sm">
              Permanently delete all {files.length} files in trash? This cannot be undone.
            </p>
            <div className="flex gap-2">
              <Button variant="outline" size="sm" onClick={() => setConfirmingEmpty(false)}>
                Cancel
              </Button>
              <Button variant="destructive" size="sm" onClick={handleEmptyTrash}>
                Delete All
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {isLoading ? (
        <div className="space-y-2">
          {Array.from({ length: 3 }).map((_, i) => (
            <Skeleton key={i} className="h-16" />
          ))}
        </div>
      ) : files.length === 0 ? (
        <EmptyState
          icon={Trash2}
          title="Trash is empty"
          description="Deleted files will appear here for recovery."
        />
      ) : (
        <div className="space-y-2">
          {files.map((file) => (
            <div
              key={file.id}
              className="flex items-center gap-3 rounded-lg border p-3"
            >
              <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded bg-muted">
                <FileIcon className="h-5 w-5 text-muted-foreground" />
              </div>
              <div className="min-w-0 flex-1">
                <p className="truncate font-medium">{file.name}</p>
                <p className="text-muted-foreground text-xs">
                  {formatBytes(file.size_bytes)} · {file.mime_type}
                </p>
              </div>
              <div className="flex gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => handleRestore(file)}
                  disabled={restoreMutation.isPending}
                >
                  <RotateCcw className="mr-1 h-3.5 w-3.5" />
                  Restore
                </Button>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => handleForceDelete(file)}
                  disabled={forceDeleteMutation.isPending}
                  className="text-destructive"
                >
                  {forceDeleteMutation.isPending ? (
                    <Loader2 className="h-3.5 w-3.5 animate-spin" />
                  ) : (
                    <Trash2 className="h-3.5 w-3.5" />
                  )}
                </Button>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
