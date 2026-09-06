import { useEffect, useRef } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import { useAuthStore } from '@/stores/auth-store'

/**
 * Invalidates all cached queries when the selected tenant changes.
 *
 * Tenant-scoped queries (company dashboard, usage, teams, etc.) don't
 * include the tenant ID in their query keys, so TanStack Query would
 * serve stale data from the previous tenant. This hook clears the
 * entire query cache on tenant switch so the next render refetches
 * with the new X-Tenant-ID header.
 */
export function useTenantQueryInvalidation() {
  const queryClient = useQueryClient()
  const { selectedTenantId } = useAuthStore()
  const prevTenantId = useRef(selectedTenantId)

  useEffect(() => {
    if (prevTenantId.current !== selectedTenantId) {
      // Invalidate all queries except tenant-agnostic ones (auth, personal vault, tools)
      queryClient.invalidateQueries()
      prevTenantId.current = selectedTenantId
    }
  }, [selectedTenantId, queryClient])
}
