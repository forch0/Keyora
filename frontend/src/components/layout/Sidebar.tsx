import { NavLink } from 'react-router-dom'
import {
  LayoutDashboard,
  Lock,
  Share2,
  FileLock,
  StickyNote,
  KeyRound,
  Activity,
  Shield,
  Settings,
  Wrench,
  ChevronLeft,
  X,
  Users,
} from 'lucide-react'
import { cn } from '@/lib/utils'
import { useUIStore } from '@/stores/ui-store'
import { Button } from '@/components/ui/button'

interface NavItem {
  to: string
  label: string
  icon: React.ComponentType<{ className?: string }>
  end?: boolean
}

const navItems: NavItem[] = [
  { to: '/', label: 'Dashboard', icon: LayoutDashboard, end: true },
  { to: '/vault', label: 'Personal Vault', icon: Lock },
  { to: '/shared', label: 'Shared Vault', icon: Share2 },
  { to: '/files', label: 'Secure Files', icon: FileLock },
  { to: '/notes', label: 'Secure Notes', icon: StickyNote },
  { to: '/access-requests', label: 'Access Requests', icon: KeyRound },
  { to: '/activity', label: 'Activity Logs', icon: Activity },
  { to: '/admin', label: 'Admin', icon: Shield },
  { to: '/admin/teams', label: 'Teams', icon: Users },
  { to: '/tools', label: 'Tools', icon: Wrench },
  { to: '/settings', label: 'Settings', icon: Settings },
]

export function Sidebar() {
  const { sidebarCollapsed, mobileSidebarOpen, toggleSidebar, setMobileSidebarOpen } = useUIStore()

  return (
    <>
      {/* Mobile overlay */}
      {mobileSidebarOpen && (
        <div
          className="fixed inset-0 z-40 bg-black/50 lg:hidden"
          onClick={() => setMobileSidebarOpen(false)}
        />
      )}

      <aside
        className={cn(
          'fixed inset-y-0 left-0 z-50 flex flex-col border-r bg-sidebar transition-all duration-200',
          sidebarCollapsed ? 'w-16' : 'w-60',
          mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        )}
      >
        {/* Logo / app name */}
        <div className="flex h-14 items-center gap-2 border-b px-4">
          <Lock className="h-6 w-6 shrink-0 text-primary" />
          {!sidebarCollapsed && <span className="font-bold text-lg">Zekura</span>}
          <button
            className="ml-auto lg:hidden"
            onClick={() => setMobileSidebarOpen(false)}
          >
            <X className="h-5 w-5" />
          </button>
        </div>

        {/* Navigation */}
        <nav className="flex-1 space-y-1 overflow-y-auto p-2">
          {navItems.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.end}
              onClick={() => setMobileSidebarOpen(false)}
              className={({ isActive }) =>
                cn(
                  'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                  isActive
                    ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                    : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                  sidebarCollapsed && 'justify-center',
                )
              }
              title={sidebarCollapsed ? item.label : undefined}
            >
              <item.icon className="h-5 w-5 shrink-0" />
              {!sidebarCollapsed && <span>{item.label}</span>}
            </NavLink>
          ))}
        </nav>

        {/* Collapse toggle (desktop only) */}
        <div className="hidden border-t p-2 lg:block">
          <Button
            variant="ghost"
            size="sm"
            onClick={toggleSidebar}
            className="w-full justify-center"
          >
            <ChevronLeft
              className={cn('h-4 w-4 transition-transform', sidebarCollapsed && 'rotate-180')}
            />
            {!sidebarCollapsed && <span className="ml-2">Collapse</span>}
          </Button>
        </div>
      </aside>
    </>
  )
}
