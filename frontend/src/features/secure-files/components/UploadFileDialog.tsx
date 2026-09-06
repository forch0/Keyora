import { useState, useRef } from 'react'
import { Upload, File as FileIcon, Loader2 } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { useUploadFile, useBulkUploadFiles } from '@/features/secure-files/hooks/use-secure-files'

function formatBytes(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

export function UploadFileDialog({
  open,
  onOpenChange,
  folderId,
  teamId,
}: {
  open: boolean
  onOpenChange: (open: boolean) => void
  folderId?: number | null
  teamId?: number | null
}) {
  const [files, setFiles] = useState<File[]>([])
  const [description, setDescription] = useState('')
  const [dragOver, setDragOver] = useState(false)
  const inputRef = useRef<HTMLInputElement>(null)

  const uploadMutation = useUploadFile()
  const bulkUploadMutation = useBulkUploadFiles()

  const isUploading = uploadMutation.isPending || bulkUploadMutation.isPending

  const handleSelect = (selected: FileList | null) => {
    if (!selected) return
    setFiles(Array.from(selected))
  }

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault()
    setDragOver(false)
    handleSelect(e.dataTransfer.files)
  }

  const handleSubmit = () => {
    if (files.length === 0) {
      toast.error('Select at least one file.')
      return
    }

    const commonOpts = {
      folder_id: folderId ?? null,
      team_id: teamId ?? null,
      description: description || null,
    }

    const onSuccess = () => {
      toast.success(files.length > 1 ? `${files.length} files uploaded.` : 'File uploaded.')
      setFiles([])
      setDescription('')
      onOpenChange(false)
    }

    const onError = () => toast.error('Upload failed.')

    if (files.length === 1) {
      uploadMutation.mutate({ file: files[0], ...commonOpts }, { onSuccess, onError })
    } else {
      bulkUploadMutation.mutate({ files, ...commonOpts }, { onSuccess, onError })
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Upload Files</DialogTitle>
          <DialogDescription>
            Upload files to the secure file vault. Max 10 MB per file.
          </DialogDescription>
        </DialogHeader>

        <div
          className={`rounded-lg border-2 border-dashed p-6 text-center transition-colors ${
            dragOver ? 'border-primary bg-primary/5' : 'border-muted'
          }`}
          onDragOver={(e) => {
            e.preventDefault()
            setDragOver(true)
          }}
          onDragLeave={() => setDragOver(false)}
          onDrop={handleDrop}
        >
          <Upload className="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
          <p className="text-muted-foreground text-sm">
            Drag and drop files here, or
          </p>
          <Button
            variant="outline"
            size="sm"
            className="mt-2"
            onClick={() => inputRef.current?.click()}
          >
            Browse Files
          </Button>
          <input
            ref={inputRef}
            type="file"
            multiple
            className="hidden"
            onChange={(e) => handleSelect(e.target.files)}
          />
        </div>

        {files.length > 0 && (
          <div className="space-y-1">
            {files.map((file, i) => (
              <div key={i} className="flex items-center gap-2 rounded border p-2 text-sm">
                <FileIcon className="h-4 w-4 shrink-0 text-muted-foreground" />
                <span className="min-w-0 flex-1 truncate">{file.name}</span>
                <span className="shrink-0 text-muted-foreground text-xs">
                  {formatBytes(file.size)}
                </span>
              </div>
            ))}
          </div>
        )}

        <div className="space-y-2">
          <Label htmlFor="file-description">Description (optional)</Label>
          <Textarea
            id="file-description"
            value={description}
            onChange={(e) => setDescription(e.target.value)}
            placeholder="Add a note about these files..."
            rows={2}
          />
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancel
          </Button>
          <Button onClick={handleSubmit} disabled={isUploading || files.length === 0}>
            {isUploading ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Uploading...
              </>
            ) : (
              `Upload ${files.length > 0 ? `(${files.length})` : ''}`
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
