import { useState } from 'react'
import { X, Plus, Check } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import { toast } from 'sonner'
import type { VaultTag } from '@/types/vault-organization'
import { useTags, useCreateTag } from '@/features/vault/hooks/use-vault-organization'

interface TagSelectorProps {
  selectedTagIds: number[]
  onChange: (ids: number[]) => void
}

export function TagSelector({ selectedTagIds, onChange }: TagSelectorProps) {
  const { data: tags, isLoading } = useTags()
  const createTag = useCreateTag()
  const [open, setOpen] = useState(false)
  const [newTagName, setNewTagName] = useState('')
  const [search, setSearch] = useState('')

  const selectedTags = tags?.filter((t) => selectedTagIds.includes(t.id)) ?? []
  const availableTags = tags?.filter(
    (t) => !selectedTagIds.includes(t.id) && t.name.toLowerCase().includes(search.toLowerCase()),
  )

  const handleToggle = (tagId: number) => {
    if (selectedTagIds.includes(tagId)) {
      onChange(selectedTagIds.filter((id) => id !== tagId))
    } else {
      onChange([...selectedTagIds, tagId])
    }
  }

  const handleRemove = (tagId: number) => {
    onChange(selectedTagIds.filter((id) => id !== tagId))
  }

  const handleCreateTag = () => {
    if (!newTagName.trim()) return
    createTag.mutate(
      { name: newTagName.trim() },
      {
        onSuccess: (res) => {
          onChange([...selectedTagIds, res.data.id])
          setNewTagName('')
          toast.success('Tag created.')
        },
        onError: () => toast.error('Failed to create tag.'),
      },
    )
  }

  return (
    <div className="space-y-2">
      {/* Selected tags */}
      {selectedTags.length > 0 && (
        <div className="flex flex-wrap gap-1.5">
          {selectedTags.map((tag) => (
            <Badge
              key={tag.id}
              variant="secondary"
              className="gap-1"
              style={tag.color ? { backgroundColor: tag.color } : undefined}
            >
              {tag.name}
              <button
                onClick={() => handleRemove(tag.id)}
                className="ml-0.5 rounded-full hover:bg-background/50"
              >
                <X className="h-3 w-3" />
              </button>
            </Badge>
          ))}
        </div>
      )}

      {/* Tag picker popover */}
      <Popover open={open} onOpenChange={setOpen}>
        <PopoverTrigger asChild>
          <Button variant="outline" size="sm" disabled={isLoading}>
            <Plus className="mr-2 h-4 w-4" />
            Add tag
          </Button>
        </PopoverTrigger>
        <PopoverContent className="w-64" align="start">
          <div className="space-y-3">
            {/* Search */}
            <Input
              placeholder="Search tags..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="h-8"
            />

            {/* Available tags */}
            <div className="max-h-40 space-y-1 overflow-y-auto">
              {availableTags && availableTags.length > 0 ? (
                availableTags.map((tag: VaultTag) => (
                  <button
                    key={tag.id}
                    onClick={() => handleToggle(tag.id)}
                    className="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-accent"
                  >
                    <span
                      className="h-3 w-3 rounded-full"
                      style={{ backgroundColor: tag.color ?? 'var(--muted)' }}
                    />
                    <span className="flex-1 text-left">{tag.name}</span>
                    {selectedTagIds.includes(tag.id) && <Check className="h-3.5 w-3.5" />}
                  </button>
                ))
              ) : (
                <p className="px-2 py-1.5 text-muted-foreground text-sm">
                  {search ? 'No matching tags' : 'No tags yet'}
                </p>
              )}
            </div>

            {/* Create new tag */}
            <div className="flex gap-2 border-t pt-3">
              <Input
                placeholder="New tag name"
                value={newTagName}
                onChange={(e) => setNewTagName(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === 'Enter') {
                    e.preventDefault()
                    handleCreateTag()
                  }
                }}
                className="h-8"
              />
              <Button
                size="sm"
                onClick={handleCreateTag}
                disabled={!newTagName.trim() || createTag.isPending}
              >
                <Plus className="h-4 w-4" />
              </Button>
            </div>
          </div>
        </PopoverContent>
      </Popover>
    </div>
  )
}
