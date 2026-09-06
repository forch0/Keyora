import { useEffect } from 'react'
import { useTenants } from '@/features/tenant/hooks/use-tenants'
import { useAuthStore } from '@/stores/auth-store'

/**
 * Auto-selects the first tenant when tenants are loaded and none is selected.
 * Clears the selected tenant if it's no longer in the user's tenant list.
 * No-ops when the user has no tenants (onboarding prompt handles that).
 */
export function useTenantAutoSelect() {
  const { data: tenants, isLoading } = useTenants()
  const { selectedTenantId, setTenant } = useAuthStore()

  useEffect(() => {
    if (isLoading || !tenants) return

    // No tenants — nothing to select
    if (tenants.length === 0) {
      if (selectedTenantId !== null) setTenant(null)
      return
    }

    // No tenant selected — auto-select the first one
    if (selectedTenantId === null) {
      setTenant(tenants[0].id)
      return
    }

    // Selected tenant no longer accessible — clear it
    const stillExists = tenants.some((t) => t.id === selectedTenantId)
    if (!stillExists) {
      setTenant(tenants[0].id)
    }
  }, [tenants, isLoading, selectedTenantId, setTenant])
}
