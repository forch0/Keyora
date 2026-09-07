import { useState, useEffect, useSyncExternalStore } from 'react'
import { Shield, Loader2, Lock } from 'lucide-react'
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
import { reauthManager } from '@/api/reauth-manager'
import { useReauthenticate } from '@/features/auth/hooks/use-reauth'

export function ReauthModal() {
  const state = useSyncExternalStore(
    (cb) => reauthManager.subscribe(cb),
    () => reauthManager.getState(),
  )
  const open = state === 'pending'

  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const reauthMutation = useReauthenticate()

  // Reset state when modal opens
  useEffect(() => {
    if (open) {
      setPassword('')
      setError('')
    }
  }, [open])

  const handleSubmit = () => {
    if (!password) {
      setError('Password is required.')
      return
    }
    reauthMutation.mutate(password, {
      onSuccess: (res) => {
        if (res.data?.reauthenticated) {
          reauthManager.resolveReauth()
          toast.success('Re-authenticated successfully.')
        } else {
          setError('Re-authentication failed.')
        }
      },
      onError: (err) => {
        setError(err.message ?? 'Incorrect password.')
      },
    })
  }

  const handleCancel = () => {
    reauthManager.cancelReauth()
  }

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' && !reauthMutation.isPending) {
      handleSubmit()
    }
  }

  return (
    <Dialog open={open} onOpenChange={(o) => { if (!o) handleCancel() }}>
      <DialogContent className="sm:max-w-sm" onOpenAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Shield className="h-5 w-5" />
            Re-authentication Required
          </DialogTitle>
          <DialogDescription>
            This action requires you to confirm your identity. Please enter your
            password to continue.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-3">
          <div className="space-y-2">
            <Label htmlFor="reauth-password">Password</Label>
            <div className="relative">
              <Lock className="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground" />
              <Input
                id="reauth-password"
                type="password"
                value={password}
                onChange={(e) => {
                  setPassword(e.target.value)
                  setError('')
                }}
                onKeyDown={handleKeyDown}
                placeholder="Enter your password"
                className="pl-8"
                autoFocus
                disabled={reauthMutation.isPending}
              />
            </div>
          </div>

          {error && (
            <p className="text-destructive text-sm">{error}</p>
          )}
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={handleCancel} disabled={reauthMutation.isPending}>
            Cancel
          </Button>
          <Button onClick={handleSubmit} disabled={reauthMutation.isPending || !password}>
            {reauthMutation.isPending ? (
              <>
                <Loader2 className="mr-1 h-4 w-4 animate-spin" />
                Verifying...
              </>
            ) : (
              <>
                <Shield className="mr-1 h-4 w-4" />
                Re-authenticate
              </>
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
