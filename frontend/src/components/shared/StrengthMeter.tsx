import { cn } from '@/lib/utils'
import type { PasswordStrength } from '@/features/tools/hooks/use-password-tools'

interface StrengthMeterProps {
  strength: PasswordStrength
  entropy?: number
}

const STRENGTH_CONFIG: Record<
  PasswordStrength,
  { label: string; color: string; bars: number }
> = {
  very_weak: { label: 'Very Weak', color: 'bg-red-500', bars: 1 },
  weak: { label: 'Weak', color: 'bg-orange-500', bars: 2 },
  fair: { label: 'Fair', color: 'bg-yellow-500', bars: 3 },
  strong: { label: 'Strong', color: 'bg-green-500', bars: 4 },
  very_strong: { label: 'Very Strong', color: 'bg-emerald-600', bars: 5 },
}

export function StrengthMeter({ strength, entropy }: StrengthMeterProps) {
  const config = STRENGTH_CONFIG[strength]

  return (
    <div className="space-y-1">
      <div className="flex items-center gap-2">
        <div className="flex flex-1 gap-1">
          {Array.from({ length: 5 }).map((_, i) => (
            <div
              key={i}
              className={cn(
                'h-1.5 flex-1 rounded-full transition-colors',
                i < config.bars ? config.color : 'bg-muted',
              )}
            />
          ))}
        </div>
        <span className="text-muted-foreground text-xs font-medium">{config.label}</span>
        {entropy !== undefined && (
          <span className="text-muted-foreground text-xs">{entropy.toFixed(1)} bits</span>
        )}
      </div>
    </div>
  )
}
