import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Link, useNavigate, useLocation } from 'react-router-dom'
import { toast } from 'sonner'
import { useAuthStore } from '@/stores/auth-store'
import { useLogin, useVerify2fa } from '@/features/auth/hooks/use-auth'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import type { ApiError } from '@/types/api-error'

// ─── Login form ─────────────────────────────────────────────────────────────

const loginSchema = z.object({
  email: z.string().email('Enter a valid email address'),
  password: z.string().min(1, 'Password is required'),
})

type LoginValues = z.infer<typeof loginSchema>

function LoginForm() {
  const navigate = useNavigate()
  const location = useLocation()
  const { setStatus } = useAuthStore()
  const login = useLogin()

  // Preserve intended URL for post-login redirect
  const from = (location.state as { from?: string })?.from ?? '/'

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm<LoginValues>({
    resolver: zodResolver(loginSchema),
  })

  const onSubmit = (values: LoginValues) => {
    setStatus('logging_in')
    login.mutate(values, {
      onSuccess: () => {
        const { status } = useAuthStore.getState()
        if (status === 'authenticated') {
          navigate(from, { replace: true })
        }
        // If status is 'requires_2fa', the 2FA form will show (handled by parent)
      },
      onError: (error: ApiError) => {
        setStatus('unauthenticated')
        if (error.code === 'AUTH_INVALID_CREDENTIALS') {
          setError('password', { message: 'Invalid email or password.' })
        } else if (error.code === 'RATE_LIMITED') {
          toast.error(error.message)
        } else {
          toast.error(error.message)
        }
      },
    })
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Sign in to Zekura</CardTitle>
        <CardDescription>Enter your credentials to access your vault</CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="email">Email</Label>
            <Input
              id="email"
              type="email"
              placeholder="you@company.com"
              autoComplete="email"
              autoFocus
              {...register('email')}
            />
            {errors.email && <p className="text-destructive text-sm">{errors.email.message}</p>}
          </div>

          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <Label htmlFor="password">Password</Label>
              <Link
                to="/forgot-password"
                className="text-muted-foreground text-sm hover:underline"
              >
                Forgot password?
              </Link>
            </div>
            <Input
              id="password"
              type="password"
              autoComplete="current-password"
              {...register('password')}
            />
            {errors.password && (
              <p className="text-destructive text-sm">{errors.password.message}</p>
            )}
          </div>

          <Button type="submit" className="w-full" disabled={login.isPending}>
            {login.isPending ? 'Signing in...' : 'Sign in'}
          </Button>

          <p className="text-center text-muted-foreground text-sm">
            Don't have an account?{' '}
            <Link to="/register" className="hover:underline">
              Register
            </Link>
          </p>
        </form>
      </CardContent>
    </Card>
  )
}

// ─── 2FA verify form ────────────────────────────────────────────────────────

const twoFactorSchema = z.object({
  code: z.string().optional(),
  recoveryCode: z.string().optional(),
})

type TwoFactorValues = z.infer<typeof twoFactorSchema>

function TwoFactorForm() {
  const navigate = useNavigate()
  const location = useLocation()
  const { clear, setTwoFactorToken } = useAuthStore()
  const verify2fa = useVerify2fa()
  const [useRecoveryCode, setUseRecoveryCode] = useState(false)

  const from = (location.state as { from?: string })?.from ?? '/'

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm<TwoFactorValues>({
    resolver: zodResolver(
      twoFactorSchema.refine(
        (data) => (useRecoveryCode ? !!data.recoveryCode : !!data.code),
        {
          message: useRecoveryCode ? 'Recovery code is required' : 'Code is required',
          path: useRecoveryCode ? ['recoveryCode'] : ['code'],
        },
      ),
    ),
  })

  const onSubmit = (values: TwoFactorValues) => {
    verify2fa.mutate(
      {
        code: useRecoveryCode ? undefined : values.code,
        recoveryCode: useRecoveryCode ? values.recoveryCode : undefined,
      },
      {
        onSuccess: () => {
          navigate(from, { replace: true })
        },
        onError: (error: ApiError) => {
          if (error.code === 'TWO_FACTOR_INVALID') {
            setError(useRecoveryCode ? 'recoveryCode' : 'code', {
              message: 'Invalid code. Please try again.',
            })
          } else if (error.code === 'RATE_LIMITED') {
            toast.error(error.message)
          } else {
            toast.error(error.message)
          }
        },
      },
    )
  }

  const handleBack = () => {
    setTwoFactorToken(null)
    clear()
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Two-factor authentication</CardTitle>
        <CardDescription>
          {useRecoveryCode
            ? 'Enter one of your recovery codes'
            : 'Enter the 6-digit code from your authenticator app'}
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          {!useRecoveryCode ? (
            <div className="space-y-2">
              <Label htmlFor="code">Authentication code</Label>
              <Input
                id="code"
                type="text"
                inputMode="numeric"
                pattern="[0-9]*"
                maxLength={6}
                placeholder="123456"
                autoComplete="one-time-code"
                autoFocus
                className="text-center text-lg tracking-widest"
                {...register('code')}
              />
              {errors.code && (
                <p className="text-destructive text-sm">{errors.code.message}</p>
              )}
            </div>
          ) : (
            <div className="space-y-2">
              <Label htmlFor="recoveryCode">Recovery code</Label>
              <Input
                id="recoveryCode"
                type="text"
                placeholder="xxxx-xxxx"
                autoFocus
                {...register('recoveryCode')}
              />
              {errors.recoveryCode && (
                <p className="text-destructive text-sm">{errors.recoveryCode.message}</p>
              )}
            </div>
          )}

          <Button type="submit" className="w-full" disabled={verify2fa.isPending}>
            {verify2fa.isPending ? 'Verifying...' : 'Verify'}
          </Button>

          <div className="flex items-center justify-between text-sm">
            <button
              type="button"
              onClick={() => setUseRecoveryCode(!useRecoveryCode)}
              className="text-muted-foreground hover:underline"
            >
              {useRecoveryCode ? 'Use authenticator code' : 'Use recovery code'}
            </button>
            <button
              type="button"
              onClick={handleBack}
              className="text-muted-foreground hover:underline"
            >
              Back to login
            </button>
          </div>
        </form>
      </CardContent>
    </Card>
  )
}

// ─── Login page (switches between login and 2FA) ────────────────────────────

export function LoginPage() {
  const { status } = useAuthStore()

  return (
    <div className="flex min-h-screen items-center justify-center bg-background p-4">
      <div className="w-full max-w-md">
        {status === 'requires_2fa' ? <TwoFactorForm /> : <LoginForm />}
      </div>
    </div>
  )
}
