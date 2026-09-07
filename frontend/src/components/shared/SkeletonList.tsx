import { Skeleton } from '@/components/ui/skeleton'

/** Skeleton placeholder for list pages. */
export function SkeletonList({ count = 5 }: { count?: number }) {
  return (
    <div className="space-y-2">
      {Array.from({ length: count }).map((_, i) => (
        <Skeleton key={i} className="h-16" />
      ))}
    </div>
  )
}
