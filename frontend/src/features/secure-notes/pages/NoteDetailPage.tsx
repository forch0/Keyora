import { useState, useEffect } from 'react'
import { useParams, Link, useNavigate } from 'react-router-dom'
import {
  ArrowLeft,
  Pin,
  PinOff,
  Trash2,
  Save,
  StickyNote,
  X,
} from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Skeleton } from '@/components/ui/skeleton'
import { CopyButton } from '@/components/shared/CopyButton'
import { AccessManagementPanel } from '@/features/access/components/AccessManagementPanel'
import { SecureLinksPanel } from '@/features/secure-links/components/SecureLinksPanel'
import {
  useNote,
  useCreateNote,
  useUpdateNote,
  useDeleteNote,
  useTogglePin,
} from '@/features/secure-notes/hooks/use-secure-notes'

export function NoteDetailPage() {
  const { id } = useParams<{ id: string }>()
  const noteId = id ? parseInt(id, 10) : 0
  const isNew = noteId === 0
  const navigate = useNavigate()

  const { data: note, isLoading } = useNote(noteId)
  const createMutation = useCreateNote()
  const updateMutation = useUpdateNote(noteId)
  const deleteMutation = useDeleteNote()
  const pinMutation = useTogglePin()

  const [title, setTitle] = useState('')
  const [content, setContent] = useState('')
  const [contentFormat, setContentFormat] = useState<'markdown' | 'html'>('markdown')
  const [isEditing, setIsEditing] = useState(isNew)

  // Sync form when note loads
  useEffect(() => {
    if (note) {
      setTitle(note.title)
      setContent(note.content)
      setContentFormat((note.content_format as 'markdown' | 'html') ?? 'markdown')
    }
  }, [note])

  const handleSave = () => {
    if (!title.trim()) {
      toast.error('Title is required.')
      return
    }

    const data = {
      title: title.trim(),
      content,
      content_format: contentFormat,
    }

    if (isNew) {
      createMutation.mutate(data, {
        onSuccess: (res) => {
          toast.success('Note created.')
          navigate(`/notes/${res.data.id}`)
        },
        onError: () => toast.error('Failed to create note.'),
      })
    } else {
      updateMutation.mutate(data, {
        onSuccess: () => {
          toast.success('Note saved.')
          setIsEditing(false)
        },
        onError: () => toast.error('Failed to save note.'),
      })
    }
  }

  const handleDelete = () => {
    if (!confirm('Delete this note? It will be moved to trash.')) return
    deleteMutation.mutate(noteId, {
      onSuccess: () => {
        toast.success('Note moved to trash.')
        navigate('/notes')
      },
      onError: () => toast.error('Failed to delete note.'),
    })
  }

  const handleTogglePin = () => {
    pinMutation.mutate(noteId, {
      onError: () => toast.error('Failed to toggle pin.'),
    })
  }

  const handleCancel = () => {
    if (note) {
      setTitle(note.title)
      setContent(note.content)
      setContentFormat((note.content_format as 'markdown' | 'html') ?? 'markdown')
      setIsEditing(false)
    } else {
      navigate('/notes')
    }
  }

  if (!isNew && isLoading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-32" />
        <Skeleton className="h-12 w-full" />
        <Skeleton className="h-64 w-full" />
      </div>
    )
  }

  if (!isNew && !note) {
    return (
      <div className="py-12 text-center">
        <p className="text-muted-foreground">Note not found.</p>
        <Button variant="link" asChild>
          <Link to="/notes">Back to Notes</Link>
        </Button>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" asChild>
          <Link to="/notes">
            <ArrowLeft className="mr-1 h-4 w-4" />
            Notes
          </Link>
        </Button>
      </div>

      {/* Header */}
      <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div className="flex items-center gap-3">
          <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-muted">
            <StickyNote className="h-6 w-6 text-muted-foreground" />
          </div>
          <div>
            {note?.is_pinned && (
              <Badge variant="default" className="mb-1 text-xs">
                <Pin className="mr-1 h-3 w-3" />
                Pinned
              </Badge>
            )}
            <p className="text-muted-foreground text-sm">
              {note ? `by ${note.created_by}` : 'New note'}
            </p>
          </div>
        </div>
        {!isNew && (
          <div className="flex gap-2">
            <Button
              variant="outline"
              size="sm"
              onClick={handleTogglePin}
              disabled={pinMutation.isPending}
            >
              {note?.is_pinned ? (
                <>
                  <PinOff className="mr-1 h-4 w-4" />
                  Unpin
                </>
              ) : (
                <>
                  <Pin className="mr-1 h-4 w-4" />
                  Pin
                </>
              )}
            </Button>
            {!isEditing ? (
              <Button variant="outline" size="sm" onClick={() => setIsEditing(true)}>
                Edit
              </Button>
            ) : (
              <Button variant="outline" size="sm" onClick={handleCancel}>
                <X className="mr-1 h-4 w-4" />
                Cancel
              </Button>
            )}
            <Button size="sm" onClick={handleSave} disabled={updateMutation.isPending || createMutation.isPending}>
              <Save className="mr-1 h-4 w-4" />
              {updateMutation.isPending || createMutation.isPending ? 'Saving...' : 'Save'}
            </Button>
            <Button variant="destructive" size="sm" onClick={handleDelete}>
              <Trash2 className="mr-1 h-4 w-4" />
              Delete
            </Button>
          </div>
        )}
        {isNew && (
          <div className="flex gap-2">
            <Button variant="outline" size="sm" onClick={handleCancel}>
              Cancel
            </Button>
            <Button size="sm" onClick={handleSave} disabled={createMutation.isPending}>
              <Save className="mr-1 h-4 w-4" />
              {createMutation.isPending ? 'Creating...' : 'Create'}
            </Button>
          </div>
        )}
      </div>

      {/* Note content */}
      <Card>
        <CardContent className="space-y-4 p-6">
          {isEditing ? (
            <>
              <div className="space-y-2">
                <Label htmlFor="note-title">Title</Label>
                <Input
                  id="note-title"
                  value={title}
                  onChange={(e) => {
                    setTitle(e.target.value)
                  }}
                  placeholder="Note title"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="note-content">Content</Label>
                <Textarea
                  id="note-content"
                  value={content}
                  onChange={(e) => {
                    setContent(e.target.value)
                  }}
                  placeholder="Write your note in markdown..."
                  rows={16}
                  className="font-mono text-sm"
                />
              </div>
              <div className="flex items-center gap-2">
                <Label className="text-sm">Format:</Label>
                <div className="flex gap-1">
                  <Button
                    type="button"
                    variant={contentFormat === 'markdown' ? 'default' : 'outline'}
                    size="sm"
                    onClick={() => {
                      setContentFormat('markdown')
                    }}
                  >
                    Markdown
                  </Button>
                  <Button
                    type="button"
                    variant={contentFormat === 'html' ? 'default' : 'outline'}
                    size="sm"
                    onClick={() => {
                      setContentFormat('html')
                    }}
                  >
                    HTML
                  </Button>
                </div>
              </div>
            </>
          ) : (
            <>
              <h1 className="text-2xl font-bold">{note?.title}</h1>
              <div className="flex items-center gap-2">
                <CopyButton value={note?.content ?? ''} />
                <span className="text-muted-foreground text-xs">
                  {note?.content_format ?? 'markdown'}
                </span>
              </div>
              <div className="prose prose-sm dark:prose-invert max-w-none">
                <pre className="whitespace-pre-wrap font-sans text-sm">
                  {note?.content}
                </pre>
              </div>
              {note?.tags && note.tags.length > 0 && (
                <div className="flex flex-wrap gap-1">
                  {note.tags.map((tag) => (
                    <Badge key={tag.id} variant="outline" className="text-xs">
                      {tag.name}
                    </Badge>
                  ))}
                </div>
              )}
            </>
          )}
        </CardContent>
      </Card>

      {/* Access management + secure links (only for existing notes) */}
      {!isNew && note && (
        <>
          <AccessManagementPanel resource="notes" id={noteId} />
          <SecureLinksPanel resource="notes" id={noteId} />
        </>
      )}
    </div>
  )
}
