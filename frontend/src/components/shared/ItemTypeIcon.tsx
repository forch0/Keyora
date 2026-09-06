import { Key, Database, Server, Lock } from 'lucide-react'
import { cn } from '@/lib/utils'

interface ItemTypeIconProps {
  type: string
  className?: string
}

const iconMap: Record<string, React.ComponentType<{ className?: string }>> = {
  password: Lock,
  api_key: Key,
  server_credential: Server,
  database_credential: Database,
}

/**
 * Icon based on vault item type.
 * Falls back to a lock icon for unknown types.
 */
export function ItemTypeIcon({ type, className }: ItemTypeIconProps) {
  const Icon = iconMap[type] ?? Lock
  return <Icon className={cn('h-5 w-5', className)} />
}

/**
 * Human-readable label for an item type.
 */
export function itemTypeLabel(type: string): string {
  const labels: Record<string, string> = {
    password: 'Password',
    api_key: 'API Key',
    server_credential: 'Server Credential',
    database_credential: 'Database Credential',
  }
  return labels[type] ?? type
}
