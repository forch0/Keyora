import { toast } from 'sonner'
import type { ApiError } from '@/types/api-error'

// Track rate-limited state to avoid spamming toasts
let rateLimitToastId: string | number | null = null

/**
 * Global error handler for API errors.
 *
 * Called by the API client for non-recoverable errors (403, 404, 429, 500,
 * network errors). 401 and 423 are handled separately by the session-expired
 * handler and re-auth modal respectively.
 */
export function handleApiError(error: ApiError): void {
  const { status, code, message } = error

  // 401 — handled by SessionExpiredHandler
  if (status === 401) return

  // 423 — handled by ReauthModal
  if (status === 423) return

  // 422 — validation errors are handled by forms, not globally
  if (status === 422) return

  // 429 — rate limited (deduplicated toast)
  if (status === 429) {
    if (rateLimitToastId === null) {
      rateLimitToastId = toast.warning(message, { duration: 8000, onDismiss: () => { rateLimitToastId = null } })
    }
    return
  }

  // 403 — forbidden
  if (status === 403) {
    toast.error(message || 'You do not have permission to do this.')
    return
  }

  // 404 — not found
  if (status === 404) {
    toast.error(message || 'Resource not found.')
    return
  }

  // Network error
  if (status === 0 || code === 'NETWORK_ERROR') {
    toast.error('Connection error. Check your network.', { duration: 8000 })
    return
  }

  // 500 and other server errors
  if (status >= 500) {
    toast.error(message || 'Something went wrong. Please try again.', { duration: 8000 })
    return
  }

  // Fallback for other errors
  toast.error(message || 'An error occurred.')
}
