import { useState, useMemo } from 'react'
import { useSearchParams } from 'react-router-dom'
import { Search, Star, Archive, Plus, ChevronLeft, ChevronRight, Lock } from 'lucide-react'
import { Link } from 'react-router-dom'
import { cn } from '@/lib/utils'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import { VaultItemCard } from '@/components/shared/VaultItemCard'
import { EmptyState } from '@/components/shared/EmptyState'
import { useVaultItems, useSearchVaultItems } from '@/features/vault/hooks/use-vault-items'
import { useTags } from '@/features/vault/hooks/use-vault-organization'
import { FolderTree } from '@/features/vault/components/FolderTree'
import { useDebounce } from '@/hooks/useDebounce'
import type { VaultItemListParams } from '@/types/vault'

const TYPE_FILTERS = [
  { value: 'all', label: 'All' },
  { value: 'password', label: 'Passwords' },
  { value: 'api_key', label: 'API Keys' },
  { value: 'server_credential', label: 'Server Credentials' },
  { value: 'database_credential', label: 'Database Credentials' },
]

const SORT_OPTIONS = [
  { value: 'name', label: 'Name' },
  { value: 'created_at', label: 'Created' },
  { value: 'last_accessed_at', label: 'Last Accessed' },
]

const PER_PAGE = 20

export function VaultListPage() {
  const [searchParams, setSearchParams] = useSearchParams()
  const page = parseInt(searchParams.get('page') ?? '1', 10) || 1
  const typeFilter = searchParams.get('type') ?? 'all'
  const showFavorites = searchParams.get('filter') === 'favorites'
  const showArchived = searchParams.get('filter') === 'archived'
  const sortBy = searchParams.get('sort') ?? 'name'
  const folderId = searchParams.get('folder')
  const tagId = searchParams.get('tag')

  const [searchQuery, setSearchQuery] = useState('')
  const debouncedSearch = useDebounce(searchQuery, 300)

  const isSearching = debouncedSearch.length > 0

  // Build query params for the list endpoint
  const listParams: VaultItemListParams = useMemo(
    () => ({
      page,
      per_page: PER_PAGE,
      sort: sortBy,
      ...(typeFilter !== 'all' && { type: typeFilter }),
      ...(showFavorites && { favorite: true }),
      ...(showArchived && { archived: true }),
    }),
    [page, sortBy, typeFilter, showFavorites, showArchived],
  )

  const listQuery = useVaultItems(listParams)
  const searchQuery_ = useSearchVaultItems(debouncedSearch, isSearching)
  const { data: tags } = useTags()

  const updateParam = (key: string, value: string | null) => {
    const next = new URLSearchParams(searchParams)
    if (value === null || value === '' || value === 'all') {
      next.delete(key)
    } else {
      next.set(key, value)
    }
    // Reset to page 1 when filters change
    if (key !== 'page') next.delete('page')
    setSearchParams(next)
  }

  const handlePageChange = (newPage: number) => {
    updateParam('page', String(newPage))
  }

  const items = isSearching ? searchQuery_.data : listQuery.data?.data
  const isLoading = isSearching ? searchQuery_.isLoading : listQuery.isLoading
  const meta = listQuery.data?.meta

  return (
    <div className="flex gap-6">
      {/* Folder tree sidebar */}
      <aside className="hidden w-56 shrink-0 lg:block">
        <FolderTree
          selectedFolderId={folderId ? Number(folderId) : null}
          onSelectFolder={(id) => updateParam('folder', id ? String(id) : null)}
        />

        {/* Tag filter chips */}
        {tags && tags.length > 0 && (
          <div className="mt-4 space-y-1">
            <h3 className="px-2 py-1 font-medium text-sm">Tags</h3>
            <div className="flex flex-wrap gap-1.5 px-2">
              {tags.map((tag) => (
                <button
                  key={tag.id}
                  onClick={() =>
                    updateParam('tag', tagId === String(tag.id) ? null : String(tag.id))
                  }
                  className={cn(
                    'rounded-full border px-2.5 py-0.5 text-xs transition-colors',
                    tagId === String(tag.id)
                      ? 'border-primary bg-primary text-primary-foreground'
                      : 'border-border hover:bg-accent',
                  )}
                  style={tag.color && tagId !== String(tag.id) ? { borderColor: tag.color } : undefined}
                >
                  {tag.name}
                </button>
              ))}
            </div>
          </div>
        )}
      </aside>

      {/* Main content */}
      <div className="flex-1 space-y-4">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-bold">Personal Vault</h1>
          <Button asChild>
            <Link to="/vault/new">
              <Plus className="mr-2 h-4 w-4" />
              Add Item
            </Link>
          </Button>
        </div>

        {/* Search bar */}
        <div className="relative">
          <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search vault items..."
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          className="pl-9"
        />
      </div>

      {/* Filters */}
      {!isSearching && (
        <div className="flex flex-wrap items-center gap-2">
          {/* Type filters */}
          {TYPE_FILTERS.map((tf) => (
            <button
              key={tf.value}
              onClick={() => updateParam('type', tf.value)}
              className={cn(
                'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                typeFilter === tf.value
                  ? 'bg-primary text-primary-foreground'
                  : 'bg-muted text-muted-foreground hover:bg-muted/80',
              )}
            >
              {tf.label}
            </button>
          ))}

          <div className="mx-1 h-4 w-px bg-border" />

          {/* Favorites / Archived */}
          <button
            onClick={() => updateParam('filter', showFavorites ? null : 'favorites')}
            className={cn(
              'flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
              showFavorites
                ? 'bg-primary text-primary-foreground'
                : 'bg-muted text-muted-foreground hover:bg-muted/80',
            )}
          >
            <Star className="h-4 w-4" />
            Favorites
          </button>
          <button
            onClick={() => updateParam('filter', showArchived ? null : 'archived')}
            className={cn(
              'flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
              showArchived
                ? 'bg-primary text-primary-foreground'
                : 'bg-muted text-muted-foreground hover:bg-muted/80',
            )}
          >
            <Archive className="h-4 w-4" />
            Archived
          </button>

          <div className="ml-auto flex items-center gap-2">
            <span className="text-muted-foreground text-sm">Sort by:</span>
            <Select value={sortBy} onValueChange={(v) => updateParam('sort', v)}>
              <SelectTrigger className="w-40">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {SORT_OPTIONS.map((opt) => (
                  <SelectItem key={opt.value} value={opt.value}>
                    {opt.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </div>
      )}

      {/* Items */}
      {isLoading ? (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {Array.from({ length: 6 }).map((_, i) => (
            <Skeleton key={i} className="h-24 w-full" />
          ))}
        </div>
      ) : !items || items.length === 0 ? (
        <EmptyState
          icon={Lock}
          title="No vault items"
          description="Items you add to your personal vault will appear here."
        />
      ) : (
        <>
          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            {items.map((item) => (
              <VaultItemCard key={item.id} item={item} />
            ))}
          </div>

          {/* Pagination */}
          {!isSearching && meta && meta.last_page > 1 && (
            <div className="flex items-center justify-between">
              <p className="text-muted-foreground text-sm">
                Showing {meta.from ?? 0}–{meta.to ?? 0} of {meta.total}
              </p>
              <div className="flex items-center gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  disabled={!meta || page <= 1}
                  onClick={() => handlePageChange(page - 1)}
                >
                  <ChevronLeft className="h-4 w-4" />
                  Prev
                </Button>
                <Badge variant="outline">
                  {page} / {meta.last_page}
                </Badge>
                <Button
                  variant="outline"
                  size="sm"
                  disabled={!meta || page >= meta.last_page}
                  onClick={() => handlePageChange(page + 1)}
                >
                  Next
                  <ChevronRight className="h-4 w-4" />
                </Button>
              </div>
            </div>
          )}
        </>
      )}
      </div>
    </div>
  )
}
