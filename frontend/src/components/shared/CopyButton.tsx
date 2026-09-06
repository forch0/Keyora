import { useState, useRef, useCallback } from 'react'
import { Check, Copy } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'

interface CopyButtonProps {
  value: string
  className?: string
  label?: string
}

const CLIPBOARD_CLEAR_DELAY = 30_000 // 30 seconds

/**
 * Button that copies text to clipboard, shows "Copied!" for 2 seconds,
 * and auto-clears the clipboard after 30 seconds for security.
 */
export function CopyButton({ value, className, label = 'Copy' }: CopyButtonProps) {
  const [copied, setCopied] = useState(false)
  const clearTimer = useRef<ReturnType<typeof setTimeout> | null>(null)
  const copiedTimer = useRef<ReturnType<typeof setTimeout> | null>(null)

  const handleCopy = useCallback(async () => {
    try {
      await navigator.clipboard.writeText(value)
      setCopied(true)

      // Clear "Copied!" state after 2 seconds
      if (copiedTimer.current) clearTimeout(copiedTimer.current)
      copiedTimer.current = setTimeout(() => setCopied(false), 2000)

      // Auto-clear clipboard after 30 seconds
      if (clearTimer.current) clearTimeout(clearTimer.current)
      clearTimer.current = setTimeout(async () => {
        // Only clear if the clipboard still contains our value
        try {
          const current = await navigator.clipboard.readText()
          if (current === value) {
            await navigator.clipboard.writeText('')
          }
        } catch {
          // readText may fail in some browsers — best effort
        }
      }, CLIPBOARD_CLEAR_DELAY)
    } catch {
      // Clipboard API may not be available (e.g., non-HTTPS)
    }
  }, [value])

  return (
    <Button
      variant="ghost"
      size="sm"
      onClick={handleCopy}
      className={cn('h-8 gap-1.5 px-2', className)}
      aria-label={copied ? 'Copied' : label}
    >
      {copied ? (
        <>
          <Check className="h-4 w-4 text-green-600" />
          <span className="text-green-600">Copied!</span>
        </>
      ) : (
        <>
          <Copy className="h-4 w-4" />
          <span>{label}</span>
        </>
      )}
    </Button>
  )
}
