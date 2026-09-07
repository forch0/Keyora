import { Loader2 } from 'lucide-react'

/** Full-page centered spinner for page-level loading. */
export function PageLoader({ label = 'Loading...' }: { label?: string }) {
  return (
    <div className="flex flex-col items-center justify-center py-20">
      <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      <p className="mt-3 text-muted-foreground text-sm">{label}</p>
    </div>
  )
}
