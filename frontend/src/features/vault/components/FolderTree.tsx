import { useState } from 'react'
import { ChevronRight, ChevronDown, Folder as FolderIcon, FolderPlus, Pencil, Trash2, FileBox } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { toast } from 'sonner'
import type { VaultFolder } from '@/types/vault-organization'
import {
  useFolders,
  useCreateFolder,
  useUpdateFolder,
  useDeleteFolder,
} from '@/features/vault/hooks/use-vault-organization'

// ─── Folder tree node ───────────────────────────────────────────────────────

interface TreeNodeProps {
  folder: VaultFolder
  depth: number
  selectedFolderId: number | null
  onSelect: (id: number | null) => void
  onEdit: (folder: VaultFolder) => void
  onDelete: (folder: VaultFolder) => void
  onAddChild: (parentId: number) => void
}

function TreeNode({
  folder,
  depth,
  selectedFolderId,
  onSelect,
  onEdit,
  onDelete,
  onAddChild,
}: TreeNodeProps) {
  const [expanded, setExpanded] = useState(true)
  const hasChildren = folder.children && folder.children.length > 0
  const isSelected = selectedFolderId === folder.id

  return (
    <div>
      <div
        className={cn(
          'group flex items-center gap-1 rounded-md px-2 py-1.5 text-sm hover:bg-accent',
          isSelected && 'bg-accent',
        )}
        style={{ paddingLeft: `${depth * 12 + 8}px` }}
      >
        {hasChildren ? (
          <button
            onClick={() => setExpanded(!expanded)}
            className="shrink-0"
          >
            {expanded ? (
              <ChevronDown className="h-3.5 w-3.5" />
            ) : (
              <ChevronRight className="h-3.5 w-3.5" />
            )}
          </button>
        ) : (
          <span className="w-3.5 shrink-0" />
        )}

        <button
          onClick={() => onSelect(folder.id)}
          className="flex flex-1 items-center gap-1.5 truncate text-left"
        >
          <FolderIcon className="h-4 w-4 shrink-0 text-muted-foreground" />
          <span className="truncate">{folder.name}</span>
          {folder.items_count !== undefined && folder.items_count > 0 && (
            <span className="text-muted-foreground text-xs">({folder.items_count})</span>
          )}
        </button>

        <div className="hidden items-center gap-0.5 group-hover:flex">
          <button
            onClick={() => onAddChild(folder.id)}
            className="rounded p-1 hover:bg-background"
            title="Add subfolder"
          >
            <FolderPlus className="h-3.5 w-3.5" />
          </button>
          <button
            onClick={() => onEdit(folder)}
            className="rounded p-1 hover:bg-background"
            title="Rename"
          >
            <Pencil className="h-3.5 w-3.5" />
          </button>
          <button
            onClick={() => onDelete(folder)}
            className="rounded p-1 text-destructive hover:bg-background"
            title="Delete"
          >
            <Trash2 className="h-3.5 w-3.5" />
          </button>
        </div>
      </div>

      {expanded && hasChildren && (
        <div>
          {folder.children!.map((child) => (
            <TreeNode
              key={child.id}
              folder={child}
              depth={depth + 1}
              selectedFolderId={selectedFolderId}
              onSelect={onSelect}
              onEdit={onEdit}
              onDelete={onDelete}
              onAddChild={onAddChild}
            />
          ))}
        </div>
      )}
    </div>
  )
}

// ─── Folder dialog (create / rename) ────────────────────────────────────────

interface FolderDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  mode: 'create' | 'rename'
  folder?: VaultFolder | null
  parentId?: number | null
  folders: VaultFolder[]
}

function FolderDialog({
  open,
  onOpenChange,
  mode,
  folder,
  parentId,
  folders,
}: FolderDialogProps) {
  const createFolder = useCreateFolder()
  const updateFolder = useUpdateFolder(folder?.id ?? 0)

  // Flatten folders for parent selector (exclude self + descendants in rename mode)
  const flatFolders = folders

  const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    const formData = new FormData(e.currentTarget)
    const name = formData.get('name') as string
    const parentVal = formData.get('parent_id') as string

    if (mode === 'create') {
      createFolder.mutate(
        {
          name,
          parent_id: parentVal && parentVal !== 'none' ? Number(parentVal) : null,
        },
        {
          onSuccess: () => {
            toast.success('Folder created.')
            onOpenChange(false)
          },
          onError: () => toast.error('Failed to create folder.'),
        },
      )
    } else if (folder) {
      updateFolder.mutate(
        {
          name,
          parent_id: parentVal && parentVal !== 'none' ? Number(parentVal) : null,
        },
        {
          onSuccess: () => {
            toast.success('Folder renamed.')
            onOpenChange(false)
          },
          onError: () => toast.error('Failed to rename folder.'),
        },
      )
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{mode === 'create' ? 'New Folder' : 'Rename Folder'}</DialogTitle>
          <DialogDescription>
            {mode === 'create'
              ? 'Create a new folder to organize your vault items.'
              : 'Rename or move this folder.'}
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="folder-name">Name</Label>
            <Input
              id="folder-name"
              name="name"
              defaultValue={folder?.name ?? ''}
              placeholder="Folder name"
              autoFocus
              required
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="folder-parent">Parent folder</Label>
            <Select
              name="parent_id"
              defaultValue={parentId != null ? String(parentId) : 'none'}
            >
              <SelectTrigger id="folder-parent">
                <SelectValue placeholder="None (root)" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="none">None (root)</SelectItem>
                {flatFolders
                  .filter((f) => f.id !== folder?.id)
                  .map((f) => (
                    <SelectItem key={f.id} value={String(f.id)}>
                      {f.name}
                    </SelectItem>
                  ))}
              </SelectContent>
            </Select>
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
              Cancel
            </Button>
            <Button type="submit" disabled={createFolder.isPending || updateFolder.isPending}>
              {mode === 'create' ? 'Create' : 'Save'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}

// ─── Delete folder dialog ───────────────────────────────────────────────────

function DeleteFolderDialog({
  folder,
  open,
  onOpenChange,
}: {
  folder: VaultFolder | null
  open: boolean
  onOpenChange: (open: boolean) => void
}) {
  const deleteFolder = useDeleteFolder()

  const handleDelete = () => {
    if (!folder) return
    deleteFolder.mutate(folder.id, {
      onSuccess: () => {
        toast.success('Folder deleted. Items moved to root.')
        onOpenChange(false)
      },
      onError: () => toast.error('Failed to delete folder.'),
    })
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete folder?</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete &ldquo;{folder?.name}&rdquo;? Items in this folder
            will be moved to root.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancel
          </Button>
          <Button variant="destructive" onClick={handleDelete} disabled={deleteFolder.isPending}>
            {deleteFolder.isPending ? 'Deleting...' : 'Delete'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

// ─── FolderTree ─────────────────────────────────────────────────────────────

interface FolderTreeProps {
  selectedFolderId: number | null
  onSelectFolder: (id: number | null) => void
}

export function FolderTree({ selectedFolderId, onSelectFolder }: FolderTreeProps) {
  const { data: folders, isLoading } = useFolders()
  const [dialogState, setDialogState] = useState<{
    mode: 'create' | 'rename'
    open: boolean
    folder: VaultFolder | null
    parentId: number | null
  }>({ mode: 'create', open: false, folder: null, parentId: null })
  const [deleteFolder, setDeleteFolder] = useState<VaultFolder | null>(null)

  const handleAddRoot = () => {
    setDialogState({ mode: 'create', open: true, folder: null, parentId: null })
  }

  const handleAddChild = (parentId: number) => {
    setDialogState({ mode: 'create', open: true, folder: null, parentId })
  }

  const handleEdit = (folder: VaultFolder) => {
    setDialogState({ mode: 'rename', open: true, folder, parentId: folder.parent_id })
  }

  return (
    <div className="space-y-1">
      <div className="flex items-center justify-between px-2 py-1">
        <h3 className="font-medium text-sm">Folders</h3>
        <button onClick={handleAddRoot} className="text-muted-foreground hover:text-foreground">
          <FolderPlus className="h-4 w-4" />
        </button>
      </div>

      {/* All Items root */}
      <button
        onClick={() => onSelectFolder(null)}
        className={cn(
          'flex w-full items-center gap-1.5 rounded-md px-2 py-1.5 text-sm hover:bg-accent',
          selectedFolderId === null && 'bg-accent',
        )}
      >
        <FileBox className="h-4 w-4 shrink-0 text-muted-foreground" />
        All Items
      </button>

      {isLoading ? (
        <div className="space-y-1 px-2">
          <Skeleton className="h-6 w-full" />
          <Skeleton className="h-6 w-3/4" />
        </div>
      ) : (
        folders?.map((folder) => (
          <TreeNode
            key={folder.id}
            folder={folder}
            depth={0}
            selectedFolderId={selectedFolderId}
            onSelect={onSelectFolder}
            onEdit={handleEdit}
            onDelete={setDeleteFolder}
            onAddChild={handleAddChild}
          />
        ))
      )}

      <FolderDialog
        open={dialogState.open}
        onOpenChange={(open) => setDialogState((s) => ({ ...s, open }))}
        mode={dialogState.mode}
        folder={dialogState.folder}
        parentId={dialogState.parentId}
        folders={folders ?? []}
      />

      <DeleteFolderDialog
        folder={deleteFolder}
        open={!!deleteFolder}
        onOpenChange={(open) => !open && setDeleteFolder(null)}
      />
    </div>
  )
}
