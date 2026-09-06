import { useState, useEffect } from 'react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  useCreateNoteFolder,
  useUpdateNoteFolder,
} from '@/features/secure-notes/hooks/use-secure-notes'
import type { NoteFolder } from '@/types/secure-note'

export function NoteFolderDialog({
  open,
  onOpenChange,
  folder,
  parentId,
}: {
  open: boolean
  onOpenChange: (open: boolean) => void
  folder?: NoteFolder | null
  parentId?: number | null
}) {
  const [name, setName] = useState('')
  const createMutation = useCreateNoteFolder()
  const updateMutation = useUpdateNoteFolder()

  useEffect(() => {
    setName(folder?.name ?? '')
  }, [folder, open])

  const isEditing = !!folder
  const isPending = createMutation.isPending || updateMutation.isPending

  const handleSubmit = () => {
    if (!name.trim()) {
      toast.error('Folder name is required.')
      return
    }

    if (isEditing && folder) {
      updateMutation.mutate(
        { id: folder.id, data: { name: name.trim() } },
        {
          onSuccess: () => {
            toast.success('Folder updated.')
            setName('')
            onOpenChange(false)
          },
          onError: () => toast.error('Failed to update folder.'),
        },
      )
    } else {
      createMutation.mutate(
        { name: name.trim(), parent_id: parentId ?? null },
        {
          onSuccess: () => {
            toast.success('Folder created.')
            setName('')
            onOpenChange(false)
          },
          onError: () => toast.error('Failed to create folder.'),
        },
      )
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-sm">
        <DialogHeader>
          <DialogTitle>{isEditing ? 'Edit Folder' : 'New Folder'}</DialogTitle>
        </DialogHeader>
        <div className="space-y-2">
          <Label htmlFor="note-folder-name">Folder Name</Label>
          <Input
            id="note-folder-name"
            value={name}
            onChange={(e) => setName(e.target.value)}
            placeholder="Folder name"
            autoFocus
            onKeyDown={(e) => e.key === 'Enter' && handleSubmit()}
          />
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancel
          </Button>
          <Button onClick={handleSubmit} disabled={isPending}>
            {isPending ? 'Saving...' : isEditing ? 'Save' : 'Create'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
