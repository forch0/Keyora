import { useState, useMemo } from 'react'
import { useSearchParams, Link } from 'react-router-dom'
import {
  File as FileIcon,
  Upload,
  Folder as FolderIcon,
  Trash2,
  ChevronLeft,
  ChevronRight,
  Download,
  Archive,
  MoreVertical,
  FolderPlus,
} from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { EmptyState } from '@/components/shared/EmptyState'
import { UploadFileDialog } from '@/features/secure-files/components/UploadFileDialog'
import { FileFolderDialog } from '@/features/secure-files/components/FileFolderDialog'
import {
  useFiles,
  useFileFolders,
  useDownloadFile,
  useArchiveFile,
  useDeleteFile,
  useDeleteFileFolder,
} from '@/features/secure-files/hooks/use-secure-files'
import type { SecureFile, FileFolder } from '@/types/secure-file'

const PER_PAGE = 20

function formatBytes(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

export function FileListPage() {
  const [searchParams, setSearchParams] = useSearchParams()
  const page = parseInt(searchParams.get('page') ?? '1', 10) || 1
  const folderFilter = searchParams.get('folder')

  const [showUpload, setShowUpload] = useState(false)
  const [showFolderDialog, setShowFolderDialog] = useState(false)
  const [editingFolder, setEditingFolder] = useState<FileFolder | null>(null)

  const listParams = useMemo(
    () => ({
      page,
      per_page: PER_PAGE,
      ...(folderFilter && { folder_id: parseInt(folderFilter, 10) }),
    }),
    [page, folderFilter],
  )

  const { data: filesData, isLoading } = useFiles(listParams)
  const { data: folders } = useFileFolders()
  const downloadMutation = useDownloadFile()
  const archiveMutation = useArchiveFile()
  const deleteMutation = useDeleteFile()
  const deleteFolderMutation = useDeleteFileFolder()

  const handleDownload = (file: SecureFile) => {
    downloadMutation.mutate(file.id, {
      onSuccess: (blob) => {
        const url = URL.createObjectURL(blob)
        const a = window.document.createElement('a')
        a.href = url
        a.download = file.name
        a.click()
        URL.revokeObjectURL(url)
      },
      onError: () => toast.error('Download failed.'),
    })
  }

  const handleArchive = (file: SecureFile) => {
    archiveMutation.mutate(file.id, {
      onSuccess: () => toast.success('File archived.'),
      onError: () => toast.error('Failed to archive file.'),
    })
  }

  const handleDelete = (file: SecureFile) => {
    if (!confirm(`Delete "${file.name}"? It will be moved to trash.`)) return
    deleteMutation.mutate(file.id, {
      onSuccess: () => toast.success('File moved to trash.'),
      onError: () => toast.error('Failed to delete file.'),
    })
  }

  const handleDeleteFolder = (folder: FileFolder) => {
    if (!confirm(`Delete folder "${folder.name}"? Files will be moved to root.`)) return
    deleteFolderMutation.mutate(folder.id, {
      onSuccess: () => {
        toast.success('Folder deleted.')
        if (folderFilter === String(folder.id)) {
          setSearchParams({})
        }
      },
      onError: () => toast.error('Failed to delete folder.'),
    })
  }

  const files = filesData?.data ?? []
  const totalPages = filesData?.last_page ?? 1

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Secure Files</h1>
          <p className="text-muted-foreground text-sm">
            Encrypted file storage with access control
          </p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" onClick={() => setShowFolderDialog(true)}>
            <FolderPlus className="mr-2 h-4 w-4" />
            New Folder
          </Button>
          <Button onClick={() => setShowUpload(true)}>
            <Upload className="mr-2 h-4 w-4" />
            Upload
          </Button>
        </div>
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
            <FileIcon className="h-4 w-4" />
            All Files
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
                <span className="text-muted-foreground text-xs">({folder.files_count})</span>
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

        {/* File grid */}
        <div className="min-w-0 flex-1">
          {isLoading ? (
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
              {Array.from({ length: 6 }).map((_, i) => (
                <Skeleton key={i} className="h-24" />
              ))}
            </div>
          ) : files.length === 0 ? (
            <EmptyState
              icon={FileIcon}
              title="No files yet"
              description="Upload files to securely store and share them."
              action={
                <Button onClick={() => setShowUpload(true)}>
                  <Upload className="mr-2 h-4 w-4" />
                  Upload File
                </Button>
              }
            />
          ) : (
            <>
              <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                {files.map((file) => (
                  <FileCard
                    key={file.id}
                    file={file}
                    onDownload={() => handleDownload(file)}
                    onArchive={() => handleArchive(file)}
                    onDelete={() => handleDelete(file)}
                  />
                ))}
              </div>

              {totalPages > 1 && (
                <div className="mt-6 flex items-center justify-center gap-2">
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
            </>
          )}
        </div>
      </div>

      <UploadFileDialog
        open={showUpload}
        onOpenChange={setShowUpload}
        folderId={folderFilter ? parseInt(folderFilter, 10) : null}
      />

      <FileFolderDialog
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

function FileCard({
  file,
  onDownload,
  onArchive,
  onDelete,
}: {
  file: SecureFile
  onDownload: () => void
  onArchive: () => void
  onDelete: () => void
}) {
  return (
    <div className="group rounded-lg border p-4 transition-shadow hover:shadow-sm">
      <div className="flex items-start gap-3">
        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded bg-muted">
          <FileIcon className="h-5 w-5 text-muted-foreground" />
        </div>
        <div className="min-w-0 flex-1">
          <Link
            to={`/files/${file.id}`}
            className="block truncate font-medium hover:underline"
          >
            {file.name}
          </Link>
          <p className="text-muted-foreground text-xs">
            {formatBytes(file.size_bytes)} · {file.uploaded_by}
          </p>
          {file.archived_at && (
            <Badge variant="outline" className="mt-1 text-xs">
              Archived
            </Badge>
          )}
        </div>
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <button className="opacity-0 transition-opacity group-hover:opacity-100">
              <MoreVertical className="h-4 w-4" />
            </button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem onClick={onDownload}>
              <Download className="mr-2 h-3.5 w-3.5" />
              Download
            </DropdownMenuItem>
            <DropdownMenuItem asChild>
              <Link to={`/files/${file.id}`}>
                <FileIcon className="mr-2 h-3.5 w-3.5" />
                Details
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem onClick={onArchive}>
              <Archive className="mr-2 h-3.5 w-3.5" />
              Archive
            </DropdownMenuItem>
            <DropdownMenuItem className="text-destructive" onClick={onDelete}>
              <Trash2 className="mr-2 h-3.5 w-3.5" />
              Delete
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>
  )
}
