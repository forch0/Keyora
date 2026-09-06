import { useEffect } from 'react'
import { useNavigate, useLocation } from 'react-router-dom'
import { toast } from 'sonner'
import { useAuthStore } from '@/stores/auth-store'
import { SESSION_EXPIRED_EVENT } from '@/api/client'

/**
 * Listens for the global session-expired event (dispatched by the API client
 * on 401 responses) and redirects to /login with a toast.
 */
export function SessionExpiredHandler() {
  const navigate = useNavigate()
  const location = useLocation()
  const { clear } = useAuthStore()

  useEffect(() => {
    const handler = () => {
      clear()
      // Don't redirect if we're already on a public page
      const publicPaths = ['/login', '/register', '/forgot-password', '/reset-password']
      if (!publicPaths.some((p) => location.pathname.startsWith(p))) {
        toast.error('Your session has expired. Please sign in again.')
        navigate('/login', { state: { from: location.pathname }, replace: true })
      }
    }

    window.addEventListener(SESSION_EXPIRED_EVENT, handler)
    return () => window.removeEventListener(SESSION_EXPIRED_EVENT, handler)
  }, [clear, navigate, location.pathname])

  return null
}
