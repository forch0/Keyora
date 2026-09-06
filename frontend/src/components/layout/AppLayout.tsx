import { Outlet } from 'react-router-dom'
import { cn } from '@/lib/utils'
import { useUIStore } from '@/stores/ui-store'
import { Sidebar } from './Sidebar'
import { Navbar } from './Navbar'
import { useTenantAutoSelect } from '@/features/tenant/hooks/use-tenant-auto-select'

export function AppLayout() {
  const { sidebarCollapsed } = useUIStore()
  useTenantAutoSelect()

  return (
    <div className="min-h-screen bg-background">
      <Sidebar />

      {/* Main content — offset by sidebar width on desktop */}
      <div
        className={cn(
          'flex min-h-screen flex-col transition-all duration-200',
          sidebarCollapsed ? 'lg:pl-16' : 'lg:pl-60',
        )}
      >
        <Navbar />
        <main className="flex-1 p-4 lg:p-6">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
