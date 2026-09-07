import { cn } from '@/lib/utils'

/**
 * Wraps tabular content with horizontal scroll on small screens.
 * On desktop, the content renders normally.
 *
 * Usage:
 * <ResponsiveTable>
 *   <table>...</table>
 * </ResponsiveTable>
 */
export function ResponsiveTable({
  children,
  className,
}: {
  children: React.ReactNode
  className?: string
}) {
  return (
    <div className={cn('overflow-x-auto', className)}>
      {children}
    </div>
  )
}
