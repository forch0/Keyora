import { useState } from 'react'
import { Eye, EyeOff } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { CopyButton } from './CopyButton'

interface PasswordFieldProps {
  value: string | null
  className?: string
}

/**
 * Masked password field with show/hide toggle and copy-to-clipboard button.
 */
export function PasswordField({ value, className }: PasswordFieldProps) {
  const [visible, setVisible] = useState(false)

  if (!value) {
    return <span className="text-muted-foreground text-sm">—</span>
  }

  return (
    <div className="flex items-center gap-2">
      <Input
        type={visible ? 'text' : 'password'}
        value={value}
        readOnly
        className={className}
      />
      <Button
        variant="ghost"
        size="sm"
        onClick={() => setVisible(!visible)}
        className="h-8 shrink-0 px-2"
        aria-label={visible ? 'Hide password' : 'Show password'}
      >
        {visible ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
      </Button>
      <CopyButton value={value} label="" className="h-8 shrink-0 px-2" />
    </div>
  )
}
