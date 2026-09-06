import { useState, useMemo } from 'react'
import { useSearchParams, Link } from 'react-router-dom'
import { Search, Plus, ChevronLeft, ChevronRight, Lock, Users, FolderLock } from 'lucide-react'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import { ItemTypeIcon, itemTypeLabel } from '@/components/shared/ItemTypeIcon'
import { EmptyState } from '@/components/shared/EmptyState'
import { useOrgVaultItems, useTeams } from '@/features/shared-vault/hooks/use-shared-vault'
import type { SharedVaultItem } from '@/types/shared-vault'

const TYPE_FILTERS = [
  { value: 'all', label: 'All' },
  { value: 'password', label: 'Passwords' },
  { value: 'api_key', label: 'API Keys' },
  { value: 'server', label: 'Server Credentials' },
  { value: 'database', label: 'Database Credentials' },
]

const SORT_OPTIONS = [
  { value: 'name', label: 'Name' },
  { value: 'created_at', label: 'Created' },
  { value: 'updated_at', label: 'Updated' },
]

const PER_PAGE = 20

export function SharedVaultListPage() {
  const [searchParams, setSearchParams] = useSearchParams()
  const page = parseInt(searchParams.get('page') ?? '1', 10) || 1
  const typeFilter = searchParams.get('type') ?? 'all'
  const sortBy = searchParams.get('sort') ?? 'name'

  const [searchQuery, setSearchQuery] = useState('')

  const { data: teams } = useTeams()

  const listParams = useMemo(
    () => ({
      page,
      per_page: PER_PAGE,
      sort: sortBy,
      ...(typeFilter !== 'all' && { type: typeFilter }),
    }),
    [page, sortBy, typeFilter],
  )

  const listQuery = useOrgVaultItems(listParams)

  const updateParam = (key: string, value: string | null) => {
    const next = new URLSearchParams(searchParams)
    if (value === null || value === '' || value === 'all') {
      next.delete(key)
    } else {
      next.set(key, value)
    }
    if (key !== 'page') next.delete('page')
    setSearchParams(next)
  }

  const handlePageChange = (newPage: number) => updateParam('page', String(newPage))

  const items = listQuery.data?.data
  const meta = listQuery.data?.meta

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Shared Vault</h1>
          <p className="text-muted-foreground text-sm">Organization and team vault items</p>
        </div>
        <Button asChild>
          <Link to="/shared/new">
            <Plus className="mr-2 h-4 w-4" />
            Add Org Item
          </Link>
        </Button>
      </div>

      {/* Team links */}
      {teams && teams.length > 0 && (
        <div className="flex flex-wrap gap-2">
          <Link to="/shared">
            <Badge variant="secondary" className="cursor-pointer gap-1">
              <FolderLock className="h-3 w-3" />
              Org-wide
            </Badge>
          </Link>
          {teams.map((team) => (
            <Link key={team.id} to={`/shared/teams/${team.id}`}>
              <Badge variant="outline" className="cursor-pointer gap-1">
                <Users className="h-3 w-3" />
                {team.name}
                {team.vault_items_count !== undefined && ` (${team.vault_items_count})`}
              </Badge>
            </Link>
          ))}
        </div>
      )}

      {/* Search + filters */}
      <div className="flex flex-wrap items-center gap-2">
        <div className="relative flex-1">
          <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search shared items..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="pl-9"
          />
        </div>
        <Select value={typeFilter} onValueChange={(v) => updateParam('type', v)}>
          <SelectTrigger className="w-40">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            {TYPE_FILTERS.map((f) => (
              <SelectItem key={f.value} value={f.value}>
                {f.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        <Select value={sortBy} onValueChange={(v) => updateParam('sort', v)}>
          <SelectTrigger className="w-36">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            {SORT_OPTIONS.map((s) => (
              <SelectItem key={s.value} value={s.value}>
                {s.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      {/* Items */}
      {listQuery.isLoading ? (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {Array.from({ length: 6 }).map((_, i) => (
            <Skeleton key={i} className="h-24" />
          ))}
        </div>
      ) : listQuery.isError ? (
        <EmptyState
          icon={Lock}
          title="Access denied"
          description="You don't have permission to view shared vault items, or no workspace is selected."
        />
      ) : !items || items.length === 0 ? (
        <EmptyState
          icon={Lock}
          title="No shared items"
          description="Organization-level vault items will appear here."
          action={
            <Button asChild>
              <Link to="/shared/new">
                <Plus className="mr-2 h-4 w-4" />
                Add Org Item
              </Link>
            </Button>
          }
        />
      ) : (
        <>
          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            {items.map((item) => (
              <SharedVaultItemCard key={item.id} item={item} />
            ))}
          </div>

          {/* Pagination */}
          {meta && meta.last_page > 1 && (
            <div className="flex items-center justify-between">
              <p className="text-muted-foreground text-sm">
                Showing {meta.from ?? 0}–{meta.to ?? 0} of {meta.total}
              </p>
              <div className="flex items-center gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  disabled={page <= 1}
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
                  disabled={page >= meta.last_page}
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
  )
}

// ─── Shared vault item card ─────────────────────────────────────────────────

function SharedVaultItemCard({ item }: { item: SharedVaultItem }) {
  return (
    <Link
      to={`/shared/items/${item.id}`}
      className="block rounded-lg border p-4 transition-colors hover:bg-accent"
    >
      <div className="flex items-start gap-3">
        <div className="rounded-md bg-muted p-2">
          <ItemTypeIcon type={item.type} className="h-5 w-5" />
        </div>
        <div className="min-w-0 flex-1">
          <p className="truncate font-medium">{item.name}</p>
          {item.username && (
            <p className="truncate text-muted-foreground text-sm">{item.username}</p>
          )}
          <div className="mt-1 flex items-center gap-1.5">
            <Badge variant="secondary" className="text-xs">
              {itemTypeLabel(item.type)}
            </Badge>
            {item.team_id ? (
              <Badge variant="outline" className="text-xs">Team</Badge>
            ) : (
              <Badge variant="outline" className="text-xs">Org</Badge>
            )}
          </div>
        </div>
      </div>
    </Link>
  )
}
