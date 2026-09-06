import { Menu, Search, Bell } from 'lucide-react'
import { useUIStore } from '@/stores/ui-store'
import { UserMenu } from './UserMenu'
import { TenantSwitcher } from './TenantSwitcher'

export function Navbar() {
  const { setMobileSidebarOpen } = useUIStore()

  return (
    <header className="sticky top-0 z-30 flex h-14 items-center gap-3 border-b bg-background px-4">
      {/* Mobile hamburger */}
      <button
        className="lg:hidden"
        onClick={() => setMobileSidebarOpen(true)}
        aria-label="Open menu"
      >
        <Menu className="h-5 w-5" />
      </button>

      {/* Tenant switcher */}
      <TenantSwitcher />

      {/* Global search trigger (Module F18) */}
      <button
        className="flex items-center gap-2 rounded-md border bg-muted px-3 py-1.5 text-sm text-muted-foreground hover:bg-muted/80"
        onClick={() => {/* TODO: Module F18 — open search modal */}}
      >
        <Search className="h-4 w-4" />
        <span className="hidden sm:inline">Search...</span>
        <kbd className="hidden rounded border bg-background px-1.5 text-xs sm:inline">⌘K</kbd>
      </button>

      <div className="ml-auto flex items-center gap-2">
        {/* Security alerts bell (Module F20) */}
        <button
          className="relative rounded-md p-2 hover:bg-accent"
          aria-label="Security alerts"
          onClick={() => {/* TODO: Module F20 — open alerts dropdown */}}
        >
          <Bell className="h-5 w-5" />
          {/* TODO: Badge with unread count from Module F20 */}
        </button>

        <UserMenu />
      </div>
    </header>
  )
}
