import { useState, useMemo, useEffect } from 'react'
import { useSearchParams, Link } from 'react-router-dom'
import {
  StickyNote,
  Plus,
  Pin,
  Search,
  Folder as FolderIcon,
  Trash2,
  ChevronLeft,
  ChevronRight,
  MoreVertical,
  FolderPlus,
} from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { EmptyState } from '@/components/shared/EmptyState'
import { NoteFolderDialog } from '@/features/secure-notes/components/NoteFolderDialog'
import {
  useNotes,
  useSearchNotes,
  useNoteFolders,
  useDeleteNote,
  useTogglePin,
  useDeleteNoteFolder,
} from '@/features/secure-notes/hooks/use-secure-notes'
import type { SecureNote, NoteFolder } from '@/types/secure-note'

const PER_PAGE = 20

function preview(content: string, max = 120): string {
  const stripped = content.replace(/[#*`>\-\[\]]/g, '').trim()
  return stripped.length > max ? stripped.slice(0, max) + '...' : stripped
}

export function NoteListPage() {
  const [searchParams, setSearchParams] = useSearchParams()
  const page = parseInt(searchParams.get('page') ?? '1', 10) || 1
  const folderFilter = searchParams.get('folder')

  const [searchQuery, setSearchQuery] = useState('')
  const [debouncedSearch, setDebouncedSearch] = useState('')
  const [showFolderDialog, setShowFolderDialog] = useState(false)
  const [editingFolder, setEditingFolder] = useState<NoteFolder | null>(null)

  // Debounce search
  useEffect(() => {
    const timer = setTimeout(() => setDebouncedSearch(searchQuery), 300)
    return () => clearTimeout(timer)
  }, [searchQuery])

  const isSearching = debouncedSearch.length > 0

  const listParams = useMemo(
    () => ({
      page,
      per_page: PER_PAGE,
      ...(folderFilter && { folder_id: parseInt(folderFilter, 10) }),
    }),
    [page, folderFilter],
  )

  const { data: notesData, isLoading } = useNotes(listParams)
  const { data: searchResults } = useSearchNotes(debouncedSearch)
  const { data: folders } = useNoteFolders()
  const deleteMutation = useDeleteNote()
  const pinMutation = useTogglePin()
  const deleteFolderMutation = useDeleteNoteFolder()

  const allNotes = isSearching ? (searchResults ?? []) : (notesData?.data ?? [])
  const pinnedNotes = allNotes.filter((n) => n.is_pinned)
  const regularNotes = allNotes.filter((n) => !n.is_pinned)
  const totalPages = notesData?.last_page ?? 1

  const handleDelete = (note: SecureNote) => {
    if (!confirm(`Delete "${note.title}"? It will be moved to trash.`)) return
    deleteMutation.mutate(note.id, {
      onSuccess: () => toast.success('Note moved to trash.'),
      onError: () => toast.error('Failed to delete note.'),
    })
  }

  const handleTogglePin = (note: SecureNote) => {
    pinMutation.mutate(note.id, {
      onError: () => toast.error('Failed to toggle pin.'),
    })
  }

  const handleDeleteFolder = (folder: NoteFolder) => {
    if (!confirm(`Delete folder "${folder.name}"? Notes will be moved to root.`)) return
    deleteFolderMutation.mutate(folder.id, {
      onSuccess: () => {
        toast.success('Folder deleted.')
        if (folderFilter === String(folder.id)) setSearchParams({})
      },
      onError: () => toast.error('Failed to delete folder.'),
    })
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Secure Notes</h1>
          <p className="text-muted-foreground text-sm">
            Encrypted notes with access control
          </p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" onClick={() => setShowFolderDialog(true)}>
            <FolderPlus className="mr-2 h-4 w-4" />
            New Folder
          </Button>
          <Button asChild>
            <Link to="/notes/new">
              <Plus className="mr-2 h-4 w-4" />
              New Note
            </Link>
          </Button>
        </div>
      </div>

      {/* Search */}
      <div className="relative max-w-md">
        <Search className="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground" />
        <Input
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          placeholder="Search notes by title..."
          className="pl-8"
        />
      </div>

      <div className="flex gap-6">
        {/* Folder sidebar */}
        <div className="w-48 shrink-0 space-y-1">
          <button
            className={`flex w-full items-center gap-2 rounded px-2 py-1.5 text-sm transition-colors ${
              !folderFilter ? 'bg-muted font-medium' : 'hover:bg-muted/50'
            }`}
            onClick={() => setSearchParams({})}
          >
            <StickyNote className="h-4 w-4" />
            All Notes
          </button>
          {folders?.map((folder) => (
            <div
              key={folder.id}
              className={`group flex items-center gap-2 rounded px-2 py-1.5 text-sm transition-colors ${
                folderFilter === String(folder.id) ? 'bg-muted font-medium' : 'hover:bg-muted/50'
              }`}
            >
              <button
                className="flex min-w-0 flex-1 items-center gap-2"
                onClick={() => setSearchParams({ folder: String(folder.id) })}
              >
                <FolderIcon className="h-4 w-4 shrink-0" />
                <span className="truncate">{folder.name}</span>
                <span className="text-muted-foreground text-xs">({folder.notes_count})</span>
              </button>
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <button className="opacity-0 transition-opacity group-hover:opacity-100">
                    <MoreVertical className="h-3.5 w-3.5" />
                  </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <DropdownMenuItem
                    onClick={() => {
                      setEditingFolder(folder)
                      setShowFolderDialog(true)
                    }}
                  >
                    Edit
                  </DropdownMenuItem>
                  <DropdownMenuItem
                    className="text-destructive"
                    onClick={() => handleDeleteFolder(folder)}
                  >
                    Delete
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          ))}
        </div>

        {/* Notes list */}
        <div className="min-w-0 flex-1">
          {isLoading ? (
            <div className="space-y-2">
              {Array.from({ length: 4 }).map((_, i) => (
                <Skeleton key={i} className="h-20" />
              ))}
            </div>
          ) : allNotes.length === 0 ? (
            <EmptyState
              icon={StickyNote}
              title={isSearching ? 'No notes found' : 'No notes yet'}
              description={isSearching ? 'Try a different search term.' : 'Create your first secure note.'}
              action={
                !isSearching && (
                  <Button asChild>
                    <Link to="/notes/new">
                      <Plus className="mr-2 h-4 w-4" />
                      New Note
                    </Link>
                  </Button>
                )
              }
            />
          ) : (
            <div className="space-y-6">
              {/* Pinned section */}
              {pinnedNotes.length > 0 && (
                <div className="space-y-2">
                  <h2 className="flex items-center gap-1.5 text-muted-foreground text-sm font-medium">
                    <Pin className="h-3.5 w-3.5" />
                    Pinned
                  </h2>
                  <div className="space-y-2">
                    {pinnedNotes.map((note) => (
                      <NoteCard
                        key={note.id}
                        note={note}
                        onTogglePin={() => handleTogglePin(note)}
                        onDelete={() => handleDelete(note)}
                      />
                    ))}
                  </div>
                </div>
              )}

              {/* Regular notes */}
              {regularNotes.length > 0 && (
                <div className="space-y-2">
                  {pinnedNotes.length > 0 && (
                    <h2 className="text-muted-foreground text-sm font-medium">All Notes</h2>
                  )}
                  <div className="space-y-2">
                    {regularNotes.map((note) => (
                      <NoteCard
                        key={note.id}
                        note={note}
                        onTogglePin={() => handleTogglePin(note)}
                        onDelete={() => handleDelete(note)}
                      />
                    ))}
                  </div>
                </div>
              )}

              {/* Pagination */}
              {!isSearching && totalPages > 1 && (
                <div className="flex items-center justify-center gap-2">
                  <Button
                    variant="outline"
                    size="sm"
                    disabled={page <= 1}
                    onClick={() => setSearchParams({ page: String(page - 1) })}
                  >
                    <ChevronLeft className="h-4 w-4" />
                    Prev
                  </Button>
                  <span className="text-sm">
                    Page {page} of {totalPages}
                  </span>
                  <Button
                    variant="outline"
                    size="sm"
                    disabled={page >= totalPages}
                    onClick={() => setSearchParams({ page: String(page + 1) })}
                  >
                    Next
                    <ChevronRight className="h-4 w-4" />
                  </Button>
                </div>
              )}
            </div>
          )}
        </div>
      </div>

      <NoteFolderDialog
        open={showFolderDialog}
        onOpenChange={(open) => {
          setShowFolderDialog(open)
          if (!open) setEditingFolder(null)
        }}
        folder={editingFolder}
      />
    </div>
  )
}

function NoteCard({
  note,
  onTogglePin,
  onDelete,
}: {
  note: SecureNote
  onTogglePin: () => void
  onDelete: () => void
}) {
  return (
    <div className="group flex items-start gap-3 rounded-lg border p-4 transition-shadow hover:shadow-sm">
      <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded bg-muted">
        <StickyNote className="h-5 w-5 text-muted-foreground" />
      </div>
      <div className="min-w-0 flex-1">
        <Link to={`/notes/${note.id}`} className="block">
          <div className="flex items-center gap-2">
            {note.is_pinned && <Pin className="h-3.5 w-3.5 shrink-0 text-primary" />}
            <h3 className="truncate font-medium hover:underline">{note.title}</h3>
          </div>
          <p className="mt-0.5 line-clamp-2 text-muted-foreground text-sm">
            {preview(note.content)}
          </p>
          <div className="mt-1 flex items-center gap-2 text-muted-foreground text-xs">
            <span>by {note.created_by}</span>
            {note.updated_at && (
              <>
                <span>·</span>
                <span>{new Date(note.updated_at).toLocaleDateString()}</span>
              </>
            )}
            {note.tags && note.tags.length > 0 && (
              <div className="flex gap-1">
                {note.tags.slice(0, 3).map((tag) => (
                  <Badge key={tag.id} variant="outline" className="text-xs">
                    {tag.name}
                  </Badge>
                ))}
              </div>
            )}
          </div>
        </Link>
      </div>
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <button className="opacity-0 transition-opacity group-hover:opacity-100">
            <MoreVertical className="h-4 w-4" />
          </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem onClick={onTogglePin}>
            <Pin className="mr-2 h-3.5 w-3.5" />
            {note.is_pinned ? 'Unpin' : 'Pin'}
          </DropdownMenuItem>
          <DropdownMenuItem asChild>
            <Link to={`/notes/${note.id}`}>
              <StickyNote className="mr-2 h-3.5 w-3.5" />
              Open
            </Link>
          </DropdownMenuItem>
          <DropdownMenuItem className="text-destructive" onClick={onDelete}>
            <Trash2 className="mr-2 h-3.5 w-3.5" />
            Delete
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  )
}
