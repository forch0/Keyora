import { Card, CardContent } from '@/components/ui/card'

interface StatCardProps {
  icon: React.ComponentType<{ className?: string }>
  label: string
  value: string | number
  description?: string
  className?: string
}

export function StatCard({ icon: Icon, label, value, description, className }: StatCardProps) {
  return (
    <Card className={className}>
      <CardContent className="flex items-center gap-4 p-4">
        <div className="rounded-lg bg-muted p-2.5">
          <Icon className="h-5 w-5 text-muted-foreground" />
        </div>
        <div className="min-w-0">
          <p className="text-muted-foreground text-sm">{label}</p>
          <p className="text-2xl font-bold">{value}</p>
          {description && (
            <p className="truncate text-muted-foreground text-xs">{description}</p>
          )}
        </div>
      </CardContent>
    </Card>
  )
}
