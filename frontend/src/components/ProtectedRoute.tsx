import { Navigate, useLocation } from 'react-router-dom'
import { Loader2 } from 'lucide-react'
import { useAuthStore } from '@/stores/auth-store'
import { useCurrentUser } from '@/features/auth/hooks/use-auth'

/**
 * Route guard that redirects unauthenticated users to /login.
 * Preserves the intended URL so we can redirect back after login.
 * Also restores the session on page refresh if a token is stored.
 */
export function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { status, token, user } = useAuthStore()
  const location = useLocation()
  const { isLoading } = useCurrentUser()

  // Have a token but no user yet — fetching session
  if (token && !user && isLoading) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      </div>
    )
  }

  // No token or session restore failed
  if (!token || status !== 'authenticated') {
    return <Navigate to="/login" state={{ from: location.pathname }} replace />
  }

  return <>{children}</>
}
