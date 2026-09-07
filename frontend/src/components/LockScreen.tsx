import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Lock, Loader2, LogOut } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useAuthStore } from '@/stores/auth-store'
import { useLockStore } from '@/stores/lock-store'
import { useReauthenticate } from '@/features/auth/hooks/use-reauth'
import { useLogout } from '@/features/auth/hooks/use-auth'

export function LockScreen() {
  const navigate = useNavigate()
  const { user } = useAuthStore()
  const { unlock } = useLockStore()
  const reauthMutation = useReauthenticate()
  const logoutMutation = useLogout()

  const [password, setPassword] = useState('')
  const [error, setError] = useState('')

  const handleUnlock = () => {
    if (!password) {
      setError('Password is required.')
      return
    }
    reauthMutation.mutate(password, {
      onSuccess: (res) => {
        if (res.data?.reauthenticated) {
          unlock()
          setPassword('')
          setError('')
          toast.success('Welcome back.')
        } else {
          setError('Re-authentication failed.')
        }
      },
      onError: (err) => {
        setError(err.message ?? 'Incorrect password.')
      },
    })
  }

  const handleLogout = () => {
    logoutMutation.mutate(undefined, {
      onSuccess: () => {
        unlock()
        navigate('/login', { replace: true })
      },
    })
  }

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' && !reauthMutation.isPending) {
      handleUnlock()
    }
  }

  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-background p-6">
      <div className="w-full max-w-sm space-y-6">
        <div className="text-center">
          <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-muted">
            <Lock className="h-7 w-7 text-muted-foreground" />
          </div>
          <h1 className="text-xl font-semibold">App Locked</h1>
          <p className="mt-1 text-muted-foreground text-sm">
            Your session has been locked due to inactivity.
          </p>
          {user && (
            <p className="mt-2 text-muted-foreground text-xs">
              Signed in as {user.name} ({user.email})
            </p>
          )}
        </div>

        <div className="space-y-3">
          <div className="space-y-2">
            <Label htmlFor="lock-password">Password</Label>
            <div className="relative">
              <Lock className="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground" />
              <Input
                id="lock-password"
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

          {error && <p className="text-destructive text-sm">{error}</p>}

          <Button
            className="w-full"
            onClick={handleUnlock}
            disabled={reauthMutation.isPending || !password}
          >
            {reauthMutation.isPending ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Unlocking...
              </>
            ) : (
              <>
                <Lock className="mr-2 h-4 w-4" />
                Unlock
              </>
            )}
          </Button>

          <Button
            variant="ghost"
            className="w-full"
            onClick={handleLogout}
            disabled={logoutMutation.isPending}
          >
            <LogOut className="mr-2 h-4 w-4" />
            Logout instead
          </Button>
        </div>
      </div>
    </div>
  )
}
