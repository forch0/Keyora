import { Construction } from 'lucide-react'

interface PlaceholderPageProps {
  title: string
  module: string
}

/**
 * Placeholder page for routes whose module hasn't been built yet.
 * Replaced by real pages as each module is implemented.
 */
export function PlaceholderPage({ title, module }: PlaceholderPageProps) {
  return (
    <div className="flex flex-col items-center justify-center py-20">
      <Construction className="h-12 w-12 text-muted-foreground" />
      <h2 className="mt-4 text-xl font-semibold">{title}</h2>
      <p className="mt-1 text-muted-foreground text-sm">Coming soon — {module}</p>
    </div>
  )
}

/** 404 page for unknown routes. */
export function NotFoundPage() {
  return (
    <div className="flex flex-col items-center justify-center py-20">
      <h2 className="text-xl font-semibold">Page not found</h2>
      <p className="mt-1 text-muted-foreground text-sm">
        The page you're looking for doesn't exist.
      </p>
    </div>
  )
}
