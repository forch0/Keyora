import { useMemo } from 'react'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useFolders } from '@/features/vault/hooks/use-vault-organization'
import type { VaultFolder } from '@/types/vault-organization'

interface FolderSelectorProps {
  value: number | null
  onChange: (id: number | null) => void
}

/** Flatten the folder tree into a list with indentation. */
function flattenFolders(folders: VaultFolder[], depth = 0): { folder: VaultFolder; depth: number }[] {
  const result: { folder: VaultFolder; depth: number }[] = []
  for (const f of folders) {
    result.push({ folder: f, depth })
    if (f.children && f.children.length > 0) {
      result.push(...flattenFolders(f.children, depth + 1))
    }
  }
  return result
}

export function FolderSelector({ value, onChange }: FolderSelectorProps) {
  const { data: folders, isLoading } = useFolders()

  const flatFolders = useMemo(() => flattenFolders(folders ?? []), [folders])

  return (
    <Select
      value={value != null ? String(value) : 'none'}
      onValueChange={(v) => onChange(v === 'none' ? null : Number(v))}
    >
      <SelectTrigger disabled={isLoading}>
        <SelectValue placeholder="None (root)" />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value="none">None (root)</SelectItem>
        {flatFolders.map(({ folder, depth }) => (
          <SelectItem key={folder.id} value={String(folder.id)}>
            {'— '.repeat(depth) + folder.name}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  )
}
