import { useAuthStore } from '@/stores/auth-store'
import { getCsrfToken } from './csrf'
import { reauthManager } from './reauth-manager'
import { handleApiError } from './error-handler'
import type { ApiError } from '@/types/api-error'

const BASE_URL = import.meta.env.VITE_API_URL ?? ''

/** Event emitted when a 423 Locked response is received (re-auth required). */
export const REAUTH_REQUIRED_EVENT = 'keyora:reauth-required'

/** Event emitted when a 401 Unauthorized response is received (session expired). */
export const SESSION_EXPIRED_EVENT = 'keyora:session-expired'

/** Endpoints that are not tenant-scoped and should not send X-Tenant-ID. */
const TENANT_AGNOSTIC_PATTERNS = [
  /^\/api\/v1\/auth\//,
  // Personal vault — but NOT share-links or access (those are tenant-scoped)
  /^\/api\/v1\/vault\/items\/\d+\/?$/,
  /^\/api\/v1\/vault\/items\/\d+\/edit$/,
  /^\/api\/v1\/vault\/folders/,
  /^\/api\/v1\/vault\/tags/,
  /^\/api\/v1\/vault\/trash/,
  /^\/api\/v1\/vault\/bulk/,
  /^\/api\/v1\/vault\/recent/,
  /^\/api\/v1\/dashboard\/personal/,
  /^\/api\/v1\/security-alerts/,
  /^\/api\/v1\/devices/,
  /^\/api\/v1\/activity-logs$/,
  /^\/api\/v1\/tools\//,
  /^\/api\/v1\/access-requests/,
  /^\/api\/v1\/secure-links\//,
  /^\/api\/v1\/s\//,
  /^\/api\/v1\/health/,
]

function isTenantAgnostic(path: string): boolean {
  return TENANT_AGNOSTIC_PATTERNS.some((pattern) => pattern.test(path))
}

/** Extract and normalize the error from a fetch Response. */
async function parseError(response: Response): Promise<ApiError> {
  const status = response.status

  // Rate limit — try to read Retry-After header
  if (status === 429) {
    const retryAfter = response.headers.get('Retry-After')
    return {
      code: 'RATE_LIMITED',
      message: retryAfter
        ? `Too many requests. Try again in ${retryAfter} seconds.`
        : 'Too many requests. Please try again later.',
      status,
    }
  }

  // Try to parse JSON error envelope
  try {
    const body = await response.json()
    if (body?.error?.code) {
      return {
        code: body.error.code,
        message: body.error.message ?? 'An error occurred.',
        status,
        errors: body.error.errors,
      }
    }
    // Some error responses might have a different shape
    if (body?.message) {
      return {
        code: body.code ?? 'ERROR',
        message: body.message,
        status,
        errors: body.errors,
      }
    }
  } catch {
    // Response body is not JSON
  }

  // Fallback messages for common status codes
  const fallbacks: Record<number, string> = {
    400: 'Bad request.',
    401: 'You are not authenticated.',
    403: 'You do not have permission to do this.',
    404: 'Resource not found.',
    409: 'Conflict — the resource already exists.',
    423: 'Re-authentication required for this action.',
    500: 'Something went wrong. Please try again.',
    502: 'Server is unavailable.',
    503: 'Service temporarily unavailable.',
  }

  return {
    code: 'ERROR',
    message: fallbacks[status] ?? `Request failed with status ${status}.`,
    status,
  }
}

/** Core request function. */
async function request<T>(
  path: string,
  options: RequestInit = {},
): Promise<T> {
  const { method = 'GET' } = options

  const headers: Record<string, string> = {
    Accept: 'application/json',
    ...((options.headers as Record<string, string>) ?? {}),
  }

  // Content-Type for requests with a body (unless already set, e.g. FormData)
  if (options.body && !headers['Content-Type'] && !(options.body instanceof FormData)) {
    headers['Content-Type'] = 'application/json'
  }

  // CSRF token for non-GET requests
  if (method !== 'GET') {
    const csrfToken = getCsrfToken()
    if (csrfToken) {
      headers['X-CSRF-TOKEN'] = csrfToken
    }
  }

  // Bearer token for authenticated requests (Sanctum token-based auth)
  const { token } = useAuthStore.getState()
  if (token) {
    headers['Authorization'] = `Bearer ${token}`
  }

  // Tenant header for tenant-scoped endpoints
  const { selectedTenantId } = useAuthStore.getState()
  if (selectedTenantId !== null && !isTenantAgnostic(path)) {
    headers['X-Tenant-ID'] = String(selectedTenantId)
  }

  let response: Response
  try {
    response = await fetch(`${BASE_URL}${path}`, {
      ...options,
      method,
      headers,
      credentials: 'include',
    })
  } catch {
    throw {
      code: 'NETWORK_ERROR',
      message: 'Connection error. Check your network.',
      status: 0,
    } satisfies ApiError
  }

  // Handle error status codes
  if (!response.ok) {
    // 401 — session expired
    if (response.status === 401) {
      window.dispatchEvent(new CustomEvent(SESSION_EXPIRED_EVENT))
    }

    // 423 — re-authentication required; wait for re-auth then retry once
    if (response.status === 423) {
      window.dispatchEvent(new CustomEvent(REAUTH_REQUIRED_EVENT))
      try {
        await reauthManager.waitForReauth()
        // Re-auth succeeded — retry the original request once
        return request<T>(path, options)
      } catch {
        // User cancelled re-auth
        const cancelErr = {
          code: 'REAUTH_CANCELLED',
          message: 'Re-authentication cancelled.',
          status: 423,
        } satisfies ApiError
        throw cancelErr
      }
    }

    const error = await parseError(response)

    // Show global toast for non-validation errors (422 is handled by forms)
    if (response.status !== 422) {
      handleApiError(error)
    }

    throw error
  }

  // 204 No Content
  if (response.status === 204) {
    return undefined as T
  }

  // Parse JSON response
  try {
    return (await response.json()) as T
  } catch {
    return undefined as T
  }
}

/** Public API client. */
export const api = {
  get<T>(path: string, params?: Record<string, unknown>): Promise<T> {
    const query = params
      ? '?' + new URLSearchParams(
          Object.entries(params)
            .filter(([, v]) => v !== undefined && v !== null)
            .map(([k, v]) => [k, String(v)]),
        ).toString()
      : ''
    return request<T>(`${path}${query}`)
  },

  post<T>(path: string, body?: unknown): Promise<T> {
    return request<T>(path, {
      method: 'POST',
      body: body !== undefined ? JSON.stringify(body) : undefined,
    })
  },

  postForm<T>(path: string, formData: FormData): Promise<T> {
    return request<T>(path, {
      method: 'POST',
      body: formData,
    })
  },

  put<T>(path: string, body?: unknown): Promise<T> {
    return request<T>(path, {
      method: 'PUT',
      body: body !== undefined ? JSON.stringify(body) : undefined,
    })
  },

  delete<T = void>(path: string): Promise<T> {
    return request<T>(path, { method: 'DELETE' })
  },
}
