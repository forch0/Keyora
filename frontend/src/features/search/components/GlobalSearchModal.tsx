import { useState, useEffect, useRef, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import {
  Search,
  File as FileIcon,
  StickyNote,
  KeyRound,
  User,
  Users,
  Loader2,
  Clock,
} from 'lucide-react'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Skeleton } from '@/components/ui/skeleton'
import { useGlobalSearch } from '@/features/search/hooks/use-search'
import type { SearchResults } from '@/types/search'

const RECENT_SEARCHES_KEY = 'keyora_recent_searches'
const MAX_RECENT = 5

function loadRecentSearches(): string[] {
  try {
    const raw = localStorage.getItem(RECENT_SEARCHES_KEY)
    return raw ? JSON.parse(raw) : []
  } catch {
    return []
  }
}

function saveRecentSearch(query: string) {
  try {
    const recent = loadRecentSearches().filter((q) => q !== query)
    recent.unshift(query)
    localStorage.setItem(RECENT_SEARCHES_KEY, JSON.stringify(recent.slice(0, MAX_RECENT)))
  } catch {
    // ignore
  }
}

export function GlobalSearchModal({
  open,
  onOpenChange,
}: {
  open: boolean
  onOpenChange: (open: boolean) => void
}) {
  const [query, setQuery] = useState('')
  const [debouncedQuery, setDebouncedQuery] = useState('')
  const [recentSearches, setRecentSearches] = useState<string[]>([])
  const inputRef = useRef<HTMLInputElement>(null)
  const navigate = useNavigate()

  const { data: results, isLoading } = useGlobalSearch(debouncedQuery)

  // Debounce
  useEffect(() => {
    const timer = setTimeout(() => setDebouncedQuery(query), 300)
    return () => clearTimeout(timer)
  }, [query])

  // Load recent searches on open
  useEffect(() => {
    if (open) {
      setRecentSearches(loadRecentSearches())
      setQuery('')
      setDebouncedQuery('')
      setTimeout(() => inputRef.current?.focus(), 50)
    }
  }, [open])

  const close = useCallback(() => onOpenChange(false), [onOpenChange])

  const navigateTo = (path: string) => {
    if (debouncedQuery) saveRecentSearch(debouncedQuery)
    close()
    navigate(path)
  }

  const handleRecentSearch = (q: string) => {
    setQuery(q)
    setDebouncedQuery(q)
  }

  const hasResults = results && (
    (results.vault_items?.length ?? 0) > 0 ||
    (results.files?.length ?? 0) > 0 ||
    (results.notes?.length ?? 0) > 0
  )

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent
        className="sm:max-w-2xl"
        onOpenAutoFocus={(e) => e.preventDefault()}
      >
        <DialogHeader>
          <DialogTitle className="sr-only">Global Search</DialogTitle>
        </DialogHeader>

        {/* Search input */}
        <div className="relative">
          <Search className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
          <Input
            ref={inputRef}
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Search vault items, files, notes, people..."
            className="pl-9"
            autoFocus
          />
          {isLoading && (
            <Loader2 className="absolute top-3 right-3 h-4 w-4 animate-spin text-muted-foreground" />
          )}
        </div>

        {/* Results / recent searches */}
        <div className="max-h-96 overflow-y-auto">
          {!debouncedQuery && recentSearches.length > 0 && (
            <div className="space-y-1">
              <p className="px-1 py-1 text-muted-foreground text-xs font-medium">
                Recent searches
              </p>
              {recentSearches.map((q) => (
                <button
                  key={q}
                  className="flex w-full items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-accent"
                  onClick={() => handleRecentSearch(q)}
                >
                  <Clock className="h-3.5 w-3.5 text-muted-foreground" />
                  {q}
                </button>
              ))}
            </div>
          )}

          {!debouncedQuery && recentSearches.length === 0 && (
            <div className="py-8 text-center">
              <Search className="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
              <p className="text-muted-foreground text-sm">
                Start typing to search across all resources
              </p>
            </div>
          )}

          {debouncedQuery && isLoading && (
            <div className="space-y-2 p-2">
              {Array.from({ length: 3 }).map((_, i) => (
                <Skeleton key={i} className="h-10" />
              ))}
            </div>
          )}

          {debouncedQuery && !isLoading && !hasResults && (
            <div className="py-8 text-center">
              <p className="text-muted-foreground text-sm">
                No results found for "{debouncedQuery}"
              </p>
            </div>
          )}

          {debouncedQuery && !isLoading && hasResults && results && (
            <SearchResultsList results={results} onNavigate={navigateTo} />
          )}
        </div>
      </DialogContent>
    </Dialog>
  )
}

function SearchResultsList({
  results,
  onNavigate,
}: {
  results: SearchResults
  onNavigate: (path: string) => void
}) {
  return (
    <div className="space-y-4 p-1">
      {results.vault_items?.length > 0 && (
        <ResultSection
          title="Vault Items"
          icon={KeyRound}
          items={results.vault_items.map((item) => ({
            id: item.id,
            label: item.name,
            sublabel: item.type,
            onClick: () => onNavigate(`/shared/items/${item.id}`),
          }))}
        />
      )}

      {results.files?.length > 0 && (
        <ResultSection
          title="Files"
          icon={FileIcon}
          items={results.files.map((file) => ({
            id: file.id,
            label: file.name,
            sublabel: String(file.mime_type ?? ''),
            onClick: () => onNavigate(`/files/${file.id}`),
          }))}
        />
      )}

      {results.notes?.length > 0 && (
        <ResultSection
          title="Notes"
          icon={StickyNote}
          items={results.notes.map((note) => ({
            id: note.id,
            label: note.title,
            sublabel: '',
            onClick: () => onNavigate(`/notes/${note.id}`),
          }))}
        />
      )}

      {Array.isArray(results.people) && results.people.length > 0 && (
        <ResultSection
          title="People"
          icon={User}
          items={results.people
            .filter((p): p is { id: number; name: string; email: string } =>
              typeof p === 'object' && p !== null && 'id' in p
            )
            .map((person) => ({
              id: person.id,
              label: person.name,
              sublabel: person.email,
              onClick: () => onNavigate(`/admin`), // no user detail page yet
            }))}
        />
      )}

      {Array.isArray(results.teams) && results.teams.length > 0 && (
        <ResultSection
          title="Teams"
          icon={Users}
          items={results.teams
            .filter((t): t is { id: number; name: string } =>
              typeof t === 'object' && t !== null && 'id' in t
            )
            .map((team) => ({
              id: team.id,
              label: team.name,
              sublabel: '',
              onClick: () => onNavigate(`/admin/teams/${team.id}/members`),
            }))}
        />
      )}
    </div>
  )
}

function ResultSection({
  title,
  icon: Icon,
  items,
}: {
  title: string
  icon: React.ComponentType<{ className?: string }>
  items: {
    id: number
    label: string
    sublabel: string
    onClick: () => void
  }[]
}) {
  if (items.length === 0) return null
  return (
    <div className="space-y-1">
      <p className="flex items-center gap-1.5 px-1 py-1 text-muted-foreground text-xs font-medium">
        <Icon className="h-3.5 w-3.5" />
        {title}
      </p>
      {items.map((item) => (
        <button
          key={item.id}
          className="flex w-full items-center gap-2 rounded px-2 py-2 text-sm hover:bg-accent"
          onClick={item.onClick}
        >
          <span className="min-w-0 flex-1 truncate">{item.label}</span>
          {item.sublabel && (
            <span className="shrink-0 text-muted-foreground text-xs">{item.sublabel}</span>
          )}
        </button>
      ))}
    </div>
  )
}
