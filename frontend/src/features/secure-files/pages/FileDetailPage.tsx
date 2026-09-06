import { useState, useRef } from 'react'
import { useParams, Link, useNavigate } from 'react-router-dom'
import {
  ArrowLeft,
  File as FileIcon,
  Download,
  Trash2,
  Archive,
  RefreshCw,
  Save,
  Loader2,
} from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Switch } from '@/components/ui/switch'
import { Skeleton } from '@/components/ui/skeleton'
import { CopyButton } from '@/components/shared/CopyButton'
import { AccessManagementPanel } from '@/features/access/components/AccessManagementPanel'
import { SecureLinksPanel } from '@/features/secure-links/components/SecureLinksPanel'
import {
  useFile,
  useDownloadFile,
  useUpdateFile,
  useReplaceFile,
  useDeleteFile,
  useArchiveFile,
  useRestoreFile,
} from '@/features/secure-files/hooks/use-secure-files'

function formatBytes(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

export function FileDetailPage() {
  const { id } = useParams<{ id: string }>()
  const fileId = parseInt(id ?? '0', 10)
  const navigate = useNavigate()

  const { data: file, isLoading } = useFile(fileId)
  const downloadMutation = useDownloadFile()
  const updateMutation = useUpdateFile(fileId)
  const replaceMutation = useReplaceFile(fileId)
  const deleteMutation = useDeleteFile()
  const archiveMutation = useArchiveFile()
  const restoreMutation = useRestoreFile()

  const [name, setName] = useState('')
  const [description, setDescription] = useState('')
  const [downloadEnabled, setDownloadEnabled] = useState(true)
  const [edited, setEdited] = useState(false)
  const fileInputRef = useRef<HTMLInputElement>(null)

  // Sync form when file loads
  const fileLoaded = file !== undefined
  if (fileLoaded && !edited) {
    if (name !== file.name) setName(file.name)
    if (description !== (file.description ?? '')) setDescription(file.description ?? '')
    if (downloadEnabled !== file.download_enabled) setDownloadEnabled(file.download_enabled)
  }

  const handleDownload = () => {
    downloadMutation.mutate(fileId, {
      onSuccess: (blob) => {
        const url = URL.createObjectURL(blob)
        const a = window.document.createElement('a')
        a.href = url
        a.download = file?.name ?? 'download'
        a.click()
        URL.revokeObjectURL(url)
      },
      onError: () => toast.error('Download failed.'),
    })
  }

  const handleSave = () => {
    updateMutation.mutate(
      { name, description: description || null, download_enabled: downloadEnabled },
      {
        onSuccess: () => {
          toast.success('File updated.')
          setEdited(false)
        },
        onError: () => toast.error('Failed to update file.'),
      },
    )
  }

  const handleReplace = (e: React.ChangeEvent<HTMLInputElement>) => {
    const selectedFile = e.target.files?.[0]
    if (!selectedFile) return
    replaceMutation.mutate(selectedFile, {
      onSuccess: () => {
        toast.success('File replaced.')
        if (fileInputRef.current) fileInputRef.current.value = ''
      },
      onError: () => toast.error('Failed to replace file.'),
    })
  }

  const handleDelete = () => {
    if (!confirm('Delete this file? It will be moved to trash.')) return
    deleteMutation.mutate(fileId, {
      onSuccess: () => {
        toast.success('File moved to trash.')
        navigate('/files')
      },
      onError: () => toast.error('Failed to delete file.'),
    })
  }

  const handleArchive = () => {
    archiveMutation.mutate(fileId, {
      onSuccess: () => toast.success('File archived.'),
      onError: () => toast.error('Failed to archive file.'),
    })
  }

  const handleRestore = () => {
    restoreMutation.mutate(fileId, {
      onSuccess: () => toast.success('File restored.'),
      onError: () => toast.error('Failed to restore file.'),
    })
  }

  if (isLoading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-32" />
        <Skeleton className="h-48" />
      </div>
    )
  }

  if (!file) {
    return (
      <div className="py-12 text-center">
        <p className="text-muted-foreground">File not found.</p>
        <Button variant="link" asChild>
          <Link to="/files">Back to Files</Link>
        </Button>
      </div>
    )
  }

  const isArchived = file.archived_at !== null

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" asChild>
          <Link to="/files">
            <ArrowLeft className="mr-1 h-4 w-4" />
            Files
          </Link>
        </Button>
      </div>

      {/* Header */}
      <div className="flex items-start gap-4">
        <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg bg-muted">
          <FileIcon className="h-7 w-7 text-muted-foreground" />
        </div>
        <div className="min-w-0 flex-1">
          <h1 className="truncate text-2xl font-bold">{file.name}</h1>
          <div className="mt-1 flex flex-wrap items-center gap-2 text-muted-foreground text-sm">
            <span>{formatBytes(file.size_bytes)}</span>
            <span>·</span>
            <span>{file.mime_type}</span>
            <span>·</span>
            <span>by {file.uploaded_by}</span>
            {isArchived && (
              <Badge variant="outline" className="text-xs">
                Archived
              </Badge>
            )}
          </div>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" size="sm" onClick={handleDownload} disabled={downloadMutation.isPending}>
            {downloadMutation.isPending ? (
              <Loader2 className="mr-1 h-4 w-4 animate-spin" />
            ) : (
              <Download className="mr-1 h-4 w-4" />
            )}
            Download
          </Button>
          {!isArchived ? (
            <Button variant="outline" size="sm" onClick={handleArchive}>
              <Archive className="mr-1 h-4 w-4" />
              Archive
            </Button>
          ) : (
            <Button variant="outline" size="sm" onClick={handleRestore}>
              <RefreshCw className="mr-1 h-4 w-4" />
              Restore
            </Button>
          )}
          <Button variant="destructive" size="sm" onClick={handleDelete}>
            <Trash2 className="mr-1 h-4 w-4" />
            Delete
          </Button>
        </div>
      </div>

      {/* Metadata */}
      <Card>
        <CardHeader>
          <CardTitle>File Details</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-1">
              <Label className="text-muted-foreground text-xs">Checksum</Label>
              <div className="flex items-center gap-2">
                <code className="min-w-0 flex-1 truncate rounded bg-muted px-2 py-1 text-xs">
                  {file.checksum}
                </code>
                <CopyButton value={file.checksum} />
              </div>
            </div>
            <div className="space-y-1">
              <Label className="text-muted-foreground text-xs">Created</Label>
              <p className="text-sm">
                {file.created_at ? new Date(file.created_at).toLocaleString() : '—'}
              </p>
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="file-name">Name</Label>
            <Input
              id="file-name"
              value={name}
              onChange={(e) => {
                setName(e.target.value)
                setEdited(true)
              }}
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="file-description">Description</Label>
            <Textarea
              id="file-description"
              value={description}
              onChange={(e) => {
                setDescription(e.target.value)
                setEdited(true)
              }}
              rows={3}
            />
          </div>

          <div className="flex items-center gap-2">
            <Switch
              id="download-enabled"
              checked={downloadEnabled}
              onCheckedChange={(v) => {
                setDownloadEnabled(v)
                setEdited(true)
              }}
            />
            <Label htmlFor="download-enabled" className="text-sm">
              Allow downloads
            </Label>
          </div>

          {edited && (
            <div className="flex justify-end gap-2">
              <Button
                variant="outline"
                size="sm"
                onClick={() => {
                  setName(file.name)
                  setDescription(file.description ?? '')
                  setDownloadEnabled(file.download_enabled)
                  setEdited(false)
                }}
              >
                Cancel
              </Button>
              <Button size="sm" onClick={handleSave} disabled={updateMutation.isPending}>
                <Save className="mr-1 h-3.5 w-3.5" />
                {updateMutation.isPending ? 'Saving...' : 'Save'}
              </Button>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Replace file */}
      <Card>
        <CardHeader>
          <CardTitle>Replace File</CardTitle>
        </CardHeader>
        <CardContent>
          <p className="mb-3 text-muted-foreground text-sm">
            Upload a new version of this file. The name and metadata will be preserved.
          </p>
          <input
            ref={fileInputRef}
            type="file"
            onChange={handleReplace}
            className="block w-full text-sm file:mr-4 file:rounded file:border-0 file:bg-primary file:px-4 file:py-2 file:text-primary-foreground hover:file:bg-primary/90"
          />
          {replaceMutation.isPending && (
            <p className="mt-2 flex items-center gap-2 text-sm text-muted-foreground">
              <Loader2 className="h-4 w-4 animate-spin" />
              Replacing file...
            </p>
          )}
        </CardContent>
      </Card>

      {/* Access management */}
      <AccessManagementPanel resource="files" id={fileId} />

      {/* Secure links */}
      <SecureLinksPanel resource="files" id={fileId} />
    </div>
  )
}
