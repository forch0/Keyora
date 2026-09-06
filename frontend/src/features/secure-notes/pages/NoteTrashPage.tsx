import { Link } from 'react-router-dom'
import {
  StickyNote,
  Trash2,
  RotateCcw,
  ArrowLeft,
} from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { EmptyState } from '@/components/shared/EmptyState'
import {
  useNoteTrash,
  useRestoreNoteFromTrash,
  useForceDeleteNote,
  useEmptyNoteTrash,
} from '@/features/secure-notes/hooks/use-secure-notes'
import { useState } from 'react'
import type { SecureNote } from '@/types/secure-note'

export function NoteTrashPage() {
  const { data: trashData, isLoading } = useNoteTrash({ per_page: 50 })
  const restoreMutation = useRestoreNoteFromTrash()
  const forceDeleteMutation = useForceDeleteNote()
  const emptyTrashMutation = useEmptyNoteTrash()

  const [confirmingEmpty, setConfirmingEmpty] = useState(false)

  const notes = trashData?.data ?? []

  const handleRestore = (note: SecureNote) => {
    restoreMutation.mutate(note.id, {
      onSuccess: () => toast.success(`"${note.title}" restored.`),
      onError: () => toast.error('Failed to restore note.'),
    })
  }

  const handleForceDelete = (note: SecureNote) => {
    if (!confirm(`Permanently delete "${note.title}"? This cannot be undone.`)) return
    forceDeleteMutation.mutate(note.id, {
      onSuccess: () => toast.success('Note permanently deleted.'),
      onError: () => toast.error('Failed to delete note.'),
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
            <Link to="/notes">
              <ArrowLeft className="mr-1 h-4 w-4" />
              Notes
            </Link>
          </Button>
          <h1 className="text-2xl font-bold">Trash</h1>
        </div>
        {notes.length > 0 && (
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
              Permanently delete all {notes.length} notes in trash? This cannot be undone.
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
      ) : notes.length === 0 ? (
        <EmptyState
          icon={Trash2}
          title="Trash is empty"
          description="Deleted notes will appear here for recovery."
        />
      ) : (
        <div className="space-y-2">
          {notes.map((note) => (
            <div
              key={note.id}
              className="flex items-center gap-3 rounded-lg border p-3"
            >
              <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded bg-muted">
                <StickyNote className="h-5 w-5 text-muted-foreground" />
              </div>
              <div className="min-w-0 flex-1">
                <p className="truncate font-medium">{note.title}</p>
                <p className="text-muted-foreground text-xs">
                  by {note.created_by}
                  {note.updated_at && ` · ${new Date(note.updated_at).toLocaleDateString()}`}
                </p>
              </div>
              <div className="flex gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => handleRestore(note)}
                  disabled={restoreMutation.isPending}
                >
                  <RotateCcw className="mr-1 h-3.5 w-3.5" />
                  Restore
                </Button>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => handleForceDelete(note)}
                  disabled={forceDeleteMutation.isPending}
                  className="text-destructive"
                >
                  <Trash2 className="h-3.5 w-3.5" />
                </Button>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
