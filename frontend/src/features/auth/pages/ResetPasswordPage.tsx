import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { toast } from 'sonner'
import { useResetPassword } from '@/features/auth/hooks/use-auth'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import type { ApiError } from '@/types/api-error'

const resetPasswordSchema = z
  .object({
    password: z.string().min(8, 'Password must be at least 8 characters'),
    passwordConfirmation: z.string().min(8, 'Password must be at least 8 characters'),
  })
  .refine((data) => data.password === data.passwordConfirmation, {
    message: 'Passwords do not match',
    path: ['passwordConfirmation'],
  })

type ResetPasswordValues = z.infer<typeof resetPasswordSchema>

export function ResetPasswordPage() {
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const resetPassword = useResetPassword()

  const token = searchParams.get('token') ?? ''
  const email = searchParams.get('email') ?? ''

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm<ResetPasswordValues>({
    resolver: zodResolver(resetPasswordSchema),
  })

  const onSubmit = (values: ResetPasswordValues) => {
    if (!token || !email) {
      toast.error('Invalid reset link. Please request a new one.')
      return
    }

    resetPassword.mutate(
      {
        email,
        token,
        password: values.password,
        passwordConfirmation: values.passwordConfirmation,
      },
      {
        onSuccess: () => {
          toast.success('Password has been reset successfully.')
          navigate('/login', { replace: true })
        },
        onError: (error: ApiError) => {
          if (error.errors) {
            Object.entries(error.errors).forEach(([field, messages]) => {
              const fieldName =
                field === 'password_confirmation' ? 'passwordConfirmation' : field
              setError(fieldName as keyof ResetPasswordValues, { message: messages[0] })
            })
          } else {
            toast.error(error.message)
          }
        },
      },
    )
  }

  if (!token || !email) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-background p-4">
        <div className="w-full max-w-md">
          <Card>
            <CardHeader>
              <CardTitle>Invalid reset link</CardTitle>
              <CardDescription>
                This reset link is missing required parameters. Please request a new one.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Link to="/forgot-password">
                <Button className="w-full">Request new link</Button>
              </Link>
            </CardContent>
          </Card>
        </div>
      </div>
    )
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-background p-4">
      <div className="w-full max-w-md">
        <Card>
          <CardHeader>
            <CardTitle>Reset password</CardTitle>
            <CardDescription>Enter your new password below</CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="password">New password</Label>
                <Input
                  id="password"
                  type="password"
                  autoComplete="new-password"
                  autoFocus
                  {...register('password')}
                />
                {errors.password && (
                  <p className="text-destructive text-sm">{errors.password.message}</p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="passwordConfirmation">Confirm new password</Label>
                <Input
                  id="passwordConfirmation"
                  type="password"
                  autoComplete="new-password"
                  {...register('passwordConfirmation')}
                />
                {errors.passwordConfirmation && (
                  <p className="text-destructive text-sm">
                    {errors.passwordConfirmation.message}
                  </p>
                )}
              </div>

              <Button type="submit" className="w-full" disabled={resetPassword.isPending}>
                {resetPassword.isPending ? 'Resetting...' : 'Reset password'}
              </Button>

              <p className="text-center text-muted-foreground text-sm">
                <Link to="/login" className="hover:underline">
                  Back to login
                </Link>
              </p>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
