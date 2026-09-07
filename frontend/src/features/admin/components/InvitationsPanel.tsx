import { Mail, X, Clock, CheckCircle } from 'lucide-react'
import { toast } from 'sonner'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import {
  useInvitations,
  useCancelInvitation,
} from '@/features/admin/hooks/use-admin'

export function InvitationsPanel() {
  const { data: invitations, isLoading } = useInvitations()
  const cancelMutation = useCancelInvitation()

  const handleCancel = (id: number, email: string) => {
    if (!confirm(`Cancel invitation to ${email}?`)) return
    cancelMutation.mutate(id, {
      onSuccess: () => toast.success('Invitation cancelled.'),
      onError: () => toast.error('Failed to cancel invitation.'),
    })
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Mail className="h-5 w-5" />
          Pending Invitations
        </CardTitle>
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <div className="space-y-2">
            {Array.from({ length: 2 }).map((_, i) => (
              <Skeleton key={i} className="h-16" />
            ))}
          </div>
        ) : !invitations || invitations.length === 0 ? (
          <p className="py-4 text-center text-muted-foreground text-sm">
            No pending invitations
          </p>
        ) : (
          <div className="space-y-2">
            {invitations.map((inv) => {
              const isExpired = new Date(inv.expires_at).getTime() < Date.now()
              const isAccepted = inv.accepted_at !== null

              return (
                <div
                  key={inv.id}
                  className="flex items-center gap-3 rounded border p-3"
                >
                  <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded bg-muted">
                    <Mail className="h-4 w-4 text-muted-foreground" />
                  </div>
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-medium">{inv.email}</p>
                    <div className="mt-0.5 flex items-center gap-2 text-muted-foreground text-xs">
                      <span>by {inv.invited_by?.name ?? 'Unknown'}</span>
                      <span>·</span>
                      <Badge variant="outline" className="text-xs capitalize">
                        {inv.role}
                      </Badge>
                      {isAccepted && (
                        <Badge variant="default" className="text-xs">
                          <CheckCircle className="mr-0.5 h-3 w-3" />
                          Accepted
                        </Badge>
                      )}
                      {isExpired && !isAccepted && (
                        <Badge variant="destructive" className="text-xs">
                          Expired
                        </Badge>
                      )}
                    </div>
                    <p className="mt-0.5 flex items-center gap-1 text-muted-foreground text-xs">
                      <Clock className="h-3 w-3" />
                      Expires {new Date(inv.expires_at).toLocaleDateString()}
                    </p>
                  </div>
                  {!isAccepted && (
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => handleCancel(inv.id, inv.email)}
                      disabled={cancelMutation.isPending}
                      className="text-destructive"
                    >
                      <X className="h-4 w-4" />
                    </Button>
                  )}
                </div>
              )
            })}
          </div>
        )}
      </CardContent>
    </Card>
  )
}
