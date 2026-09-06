import { useMemo } from 'react'
import { useParams, Link } from 'react-router-dom'
import { Plus, ChevronLeft, ChevronRight, Lock, Users, ArrowLeft } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import { ItemTypeIcon, itemTypeLabel } from '@/components/shared/ItemTypeIcon'
import { EmptyState } from '@/components/shared/EmptyState'
import { useTeamVaultItems, useTeams } from '@/features/shared-vault/hooks/use-shared-vault'

const PER_PAGE = 20

export function TeamVaultListPage() {
  const { teamId } = useParams<{ teamId: string }>()
  const tid = parseInt(teamId ?? '0', 10)

  const { data: teams } = useTeams()
  const currentTeam = teams?.find((t) => t.id === tid)

  const params = useMemo(() => ({ page: 1, per_page: PER_PAGE, sort: 'name' }), [])
  const listQuery = useTeamVaultItems(tid, params)

  const items = listQuery.data?.data
  const meta = listQuery.data?.meta

  return (
    <div className="space-y-4">
      <Link to="/shared">
        <Button variant="ghost" size="sm">
          <ArrowLeft className="mr-2 h-4 w-4" />
          Back to shared vault
        </Button>
      </Link>

      <div className="flex items-center justify-between">
        <div>
          <h1 className="flex items-center gap-2 text-2xl font-bold">
            <Users className="h-6 w-6" />
            {currentTeam?.name ?? 'Team'} Vault
          </h1>
          {currentTeam?.description && (
            <p className="text-muted-foreground text-sm">{currentTeam.description}</p>
          )}
        </div>
        <Button asChild>
          <Link to={`/shared/teams/${tid}/new`}>
            <Plus className="mr-2 h-4 w-4" />
            Add Team Item
          </Link>
        </Button>
      </div>

      {/* Team selector */}
      {teams && teams.length > 1 && (
        <div className="flex flex-wrap gap-2">
          <Link to="/shared">
            <Badge variant="outline" className="cursor-pointer">Org-wide</Badge>
          </Link>
          {teams.map((team) => (
            <Link key={team.id} to={`/shared/teams/${team.id}`}>
              <Badge
                variant={team.id === tid ? 'secondary' : 'outline'}
                className="cursor-pointer"
              >
                {team.name}
              </Badge>
            </Link>
          ))}
        </div>
      )}

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
          description="You don't have permission to view this team's vault items."
        />
      ) : !items || items.length === 0 ? (
        <EmptyState
          icon={Lock}
          title="No team items"
          description={`No vault items in ${currentTeam?.name ?? 'this team'} yet.`}
          action={
            <Button asChild>
              <Link to={`/shared/teams/${tid}/new`}>
                <Plus className="mr-2 h-4 w-4" />
                Add Team Item
              </Link>
            </Button>
          }
        />
      ) : (
        <>
          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            {items.map((item) => (
              <Link
                key={item.id}
                to={`/shared/teams/${tid}/items/${item.id}`}
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
                    <Badge variant="secondary" className="mt-1 text-xs">
                      {itemTypeLabel(item.type)}
                    </Badge>
                  </div>
                </div>
              </Link>
            ))}
          </div>

          {meta && meta.last_page > 1 && (
            <div className="flex items-center justify-between">
              <p className="text-muted-foreground text-sm">
                Showing {meta.from ?? 0}–{meta.to ?? 0} of {meta.total}
              </p>
              <div className="flex items-center gap-2">
                <Button variant="outline" size="sm" disabled={meta.current_page <= 1}>
                  <ChevronLeft className="h-4 w-4" />
                  Prev
                </Button>
                <Badge variant="outline">
                  {meta.current_page} / {meta.last_page}
                </Badge>
                <Button variant="outline" size="sm" disabled={meta.current_page >= meta.last_page}>
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
