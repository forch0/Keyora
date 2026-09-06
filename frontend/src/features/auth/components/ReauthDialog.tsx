import { useState, useEffect, useCallback } from 'react'
import { Lock, Loader2 } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { api, REAUTH_REQUIRED_EVENT } from '@/api/client'

type PendingRequest = () => void

let pendingRetry: PendingRequest | null = null

export function setReauthRetry(fn: PendingRequest) {
  pendingRetry = fn
}

export function ReauthDialog() {
  const [open, setOpen] = useState(false)
  const [password, setPassword] = useState('')
  const [isSubmitting, setIsSubmitting] = useState(false)

  const handleReauthEvent = useCallback(() => {
    setOpen(true)
  }, [])

  useEffect(() => {
    window.addEventListener(REAUTH_REQUIRED_EVENT, handleReauthEvent)
    return () => window.removeEventListener(REAUTH_REQUIRED_EVENT, handleReauthEvent)
  }, [handleReauthEvent])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!password) return
    setIsSubmitting(true)

    try {
      const res = await api.post<{ data: { reauthenticated: boolean } }>(
        '/api/v1/auth/reauthenticate',
        { password },
      )
      if (res.data.reauthenticated) {
        toast.success('Re-authenticated successfully.')
        setOpen(false)
        setPassword('')
        pendingRetry?.()
        pendingRetry = null
      }
    } catch {
      toast.error('Invalid password.')
    } finally {
      setIsSubmitting(false)
    }
  }

  const handleCancel = () => {
    setOpen(false)
    setPassword('')
    pendingRetry = null
  }

  return (
    <Dialog open={open} onOpenChange={(o) => !o && handleCancel()}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Lock className="h-5 w-5" />
            Re-authentication Required
          </DialogTitle>
          <DialogDescription>
            This action requires re-authentication for security. Please enter your password to
            continue.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="reauth-password">Password</Label>
            <Input
              id="reauth-password"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              autoFocus
              required
            />
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={handleCancel}>
              Cancel
            </Button>
            <Button type="submit" disabled={isSubmitting || !password}>
              {isSubmitting ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Verifying...
                </>
              ) : (
                'Re-authenticate'
              )}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}
