import { useState } from 'react'
import { RefreshCw, Eye, EyeOff } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { CopyButton } from '@/components/shared/CopyButton'
import { StrengthMeter } from '@/components/shared/StrengthMeter'
import { useGeneratePassword, useCheckStrength } from '@/features/tools/hooks/use-password-tools'
import type { GeneratePasswordOptions } from '@/features/tools/hooks/use-password-tools'
import { cn } from '@/lib/utils'

interface PasswordGeneratorProps {
  onUsePassword?: (password: string) => void
  compact?: boolean
}

export function PasswordGenerator({ onUsePassword, compact }: PasswordGeneratorProps) {
  const [length, setLength] = useState(20)
  const [uppercase, setUppercase] = useState(true)
  const [lowercase, setLowercase] = useState(true)
  const [numbers, setNumbers] = useState(true)
  const [symbols, setSymbols] = useState(true)
  const [excludeSimilar, setExcludeSimilar] = useState(false)
  const [excludeAmbiguous, setExcludeAmbiguous] = useState(false)
  const [showPassword, setShowPassword] = useState(true)
  const [generated, setGenerated] = useState('')

  const generate = useGeneratePassword()
  const checkStrength = useCheckStrength()

  const handleGenerate = () => {
    const options: GeneratePasswordOptions = {
      length,
      uppercase,
      lowercase,
      numbers,
      symbols,
      exclude_similar: excludeSimilar,
      exclude_ambiguous: excludeAmbiguous,
    }
    generate.mutate(options, {
      onSuccess: (res) => {
        setGenerated(res.data.password)
        // Check strength of generated password
        checkStrength.mutate({ password: res.data.password })
      },
    })
  }

  const handleUse = () => {
    if (onUsePassword && generated) {
      onUsePassword(generated)
    }
  }

  return (
    <Card className={compact ? 'border-0 shadow-none' : ''}>
      {!compact && (
        <CardHeader>
          <CardTitle>Password Generator</CardTitle>
        </CardHeader>
      )}
      <CardContent className="space-y-4">
        {/* Output */}
        <div className="space-y-2">
          <div className="flex items-center gap-2">
            <Input
              readOnly
              value={generated}
              type={showPassword ? 'text' : 'password'}
              placeholder="Click generate..."
              className="font-mono"
            />
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setShowPassword(!showPassword)}
              className="shrink-0"
            >
              {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
            </Button>
            <CopyButton value={generated} label="" className="shrink-0" />
          </div>

          {/* Strength meter */}
          {checkStrength.data && (
            <StrengthMeter
              strength={checkStrength.data.data.strength}
              entropy={checkStrength.data.data.entropy}
            />
          )}

          <div className="flex gap-2">
            <Button onClick={handleGenerate} disabled={generate.isPending} className="flex-1">
              <RefreshCw className={cn('mr-2 h-4 w-4', generate.isPending && 'animate-spin')} />
              Generate
            </Button>
            {onUsePassword && (
              <Button
                variant="secondary"
                onClick={handleUse}
                disabled={!generated}
              >
                Use this
              </Button>
            )}
          </div>
        </div>

        {/* Options */}
        <div className="space-y-3">
          {/* Length slider */}
          <div className="space-y-1">
            <div className="flex items-center justify-between">
              <Label htmlFor="length">Length</Label>
              <span className="font-mono text-sm font-medium">{length}</span>
            </div>
            <input
              id="length"
              type="range"
              min={8}
              max={64}
              value={length}
              onChange={(e) => setLength(Number(e.target.value))}
              className="w-full accent-primary"
            />
          </div>

          {/* Character type toggles */}
          <div className="grid grid-cols-2 gap-2">
            <Toggle label="Uppercase (A-Z)" checked={uppercase} onChange={setUppercase} />
            <Toggle label="Lowercase (a-z)" checked={lowercase} onChange={setLowercase} />
            <Toggle label="Numbers (0-9)" checked={numbers} onChange={setNumbers} />
            <Toggle label="Symbols (!@#$)" checked={symbols} onChange={setSymbols} />
          </div>

          {/* Exclusion options */}
          <div className="space-y-1.5">
            <Toggle
              label="Exclude similar (il1Lo0O)"
              checked={excludeSimilar}
              onChange={setExcludeSimilar}
            />
            <Toggle
              label="Exclude ambiguous ({ } [ ] / \ )"
              checked={excludeAmbiguous}
              onChange={setExcludeAmbiguous}
            />
          </div>
        </div>
      </CardContent>
    </Card>
  )
}

function Toggle({
  label,
  checked,
  onChange,
}: {
  label: string
  checked: boolean
  onChange: (v: boolean) => void
}) {
  return (
    <label className="flex cursor-pointer items-center gap-2 text-sm">
      <input
        type="checkbox"
        checked={checked}
        onChange={(e) => onChange(e.target.checked)}
        className="h-4 w-4 rounded border-input accent-primary"
      />
      {label}
    </label>
  )
}
