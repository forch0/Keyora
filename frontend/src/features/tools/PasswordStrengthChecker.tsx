import { useState, useEffect } from 'react'
import { Eye, EyeOff, AlertTriangle, CheckCircle2 } from 'lucide-react'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { StrengthMeter } from '@/components/shared/StrengthMeter'
import { useCheckStrength } from '@/features/tools/hooks/use-password-tools'
import { useDebounce } from '@/hooks/useDebounce'
import type { PasswordStrength } from '@/features/tools/hooks/use-password-tools'

const STRENGTH_SUGGESTION_ICONS: Record<PasswordStrength, React.ComponentType<{ className?: string }>> = {
  very_weak: AlertTriangle,
  weak: AlertTriangle,
  fair: AlertTriangle,
  strong: CheckCircle2,
  very_strong: CheckCircle2,
}

export function PasswordStrengthChecker() {
  const [password, setPassword] = useState('')
  const [showPassword, setShowPassword] = useState(false)
  const debouncedPassword = useDebounce(password, 400)

  const checkStrength = useCheckStrength()

  // Trigger strength check when debounced password changes
  useEffect(() => {
    if (debouncedPassword.length > 0) {
      checkStrength.mutate({ password: debouncedPassword })
    }
  }, [debouncedPassword])

  const result = checkStrength.data?.data
  const StrengthIcon = result ? STRENGTH_SUGGESTION_ICONS[result.strength] : null

  return (
    <Card>
      <CardHeader>
        <CardTitle>Password Strength Checker</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        {/* Password input */}
        <div className="space-y-2">
          <Label htmlFor="check-password">Enter a password to check</Label>
          <div className="flex items-center gap-2">
            <Input
              id="check-password"
              type={showPassword ? 'text' : 'password'}
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="Type or paste a password..."
              className="font-mono"
            />
            <button
              onClick={() => setShowPassword(!showPassword)}
              className="shrink-0 rounded-md p-2 hover:bg-accent"
            >
              {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
            </button>
          </div>
        </div>

        {/* Results */}
        {result && password.length > 0 && (
          <div className="space-y-4">
            {/* Strength meter */}
            <StrengthMeter strength={result.strength} entropy={result.entropy} />

            {/* Criteria checklist */}
            <div className="space-y-1.5">
              <h4 className="font-medium text-sm">Criteria</h4>
              <div className="grid grid-cols-2 gap-1.5 text-sm">
                <Criterion label="Length (12+)" met={result.criteria.length} />
                <Criterion label="Uppercase" met={result.criteria.uppercase} />
                <Criterion label="Lowercase" met={result.criteria.lowercase} />
                <Criterion label="Numbers" met={result.criteria.numbers} />
                <Criterion label="Symbols" met={result.criteria.symbols} />
                <Criterion label="No common patterns" met={result.criteria.no_common_patterns} />
              </div>
            </div>

            {/* Suggestions */}
            {result.suggestions.length > 0 && (
              <div className="space-y-2">
                <h4 className="flex items-center gap-1.5 font-medium text-sm">
                  {StrengthIcon && <StrengthIcon className="h-4 w-4" />}
                  Suggestions
                </h4>
                <ul className="space-y-1">
                  {result.suggestions.map((suggestion, idx) => (
                    <li
                      key={idx}
                      className="flex items-start gap-2 text-muted-foreground text-sm"
                    >
                      <span className="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-muted-foreground" />
                      {suggestion}
                    </li>
                  ))}
                </ul>
              </div>
            )}
          </div>
        )}

        {password.length === 0 && (
          <p className="text-muted-foreground text-sm">
            Enter a password above to see its strength analysis.
          </p>
        )}
      </CardContent>
    </Card>
  )
}

function Criterion({ label, met }: { label: string; met: boolean }) {
  return (
    <div className="flex items-center gap-1.5">
      {met ? (
        <CheckCircle2 className="h-3.5 w-3.5 text-green-600" />
      ) : (
        <span className="h-3.5 w-3.5 rounded-full border border-muted-foreground/30" />
      )}
      <span className={met ? '' : 'text-muted-foreground'}>{label}</span>
    </div>
  )
}
