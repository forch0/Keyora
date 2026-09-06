import { Navigate, useLocation } from 'react-router-dom'
import { useAuthStore } from '@/stores/auth-store'

/**
 * Route guard that redirects unauthenticated users to /login.
 * Preserves the intended URL so we can redirect back after login.
 */
export function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { status } = useAuthStore()
  const location = useLocation()

  if (status !== 'authenticated') {
    return <Navigate to="/login" state={{ from: location.pathname }} replace />
  }

  return <>{children}</>
}
