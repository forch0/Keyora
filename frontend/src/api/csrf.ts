/**
 * Fetch the CSRF cookie from Laravel Sanctum.
 * Must be called before any non-GET request (login, register, etc.).
 *
 * Sets the XSRF-TOKEN cookie, which the API client reads and sends
 * as the X-CSRF-TOKEN header on subsequent requests.
 */
export async function fetchCsrfToken(): Promise<void> {
  await fetch(`${import.meta.env.VITE_API_URL ?? ''}/sanctum/csrf-cookie`, {
    credentials: 'include',
  })
}

/**
 * Read the XSRF-TOKEN cookie value (set by fetchCsrfToken).
 * Returns the decoded token or null if not present.
 */
export function getCsrfToken(): string | null {
  const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/)
  if (!match) return null
  return decodeURIComponent(match[1])
}
