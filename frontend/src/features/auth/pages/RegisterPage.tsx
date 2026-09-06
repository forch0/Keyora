import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Link, useNavigate } from 'react-router-dom'
import { toast } from 'sonner'
import { useRegister } from '@/features/auth/hooks/use-auth'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import type { ApiError } from '@/types/api-error'

const registerSchema = z
  .object({
    name: z.string().min(1, 'Name is required').max(255, 'Name is too long'),
    email: z.string().email('Enter a valid email address'),
    password: z.string().min(8, 'Password must be at least 8 characters'),
    passwordConfirmation: z.string().min(8, 'Password must be at least 8 characters'),
  })
  .refine((data) => data.password === data.passwordConfirmation, {
    message: 'Passwords do not match',
    path: ['passwordConfirmation'],
  })

type RegisterValues = z.infer<typeof registerSchema>

export function RegisterPage() {
  const navigate = useNavigate()
  const register = useRegister()

  const {
    register: registerField,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm<RegisterValues>({
    resolver: zodResolver(registerSchema),
  })

  const onSubmit = (values: RegisterValues) => {
    register.mutate(
      {
        name: values.name,
        email: values.email,
        password: values.password,
        passwordConfirmation: values.passwordConfirmation,
      },
      {
        onSuccess: () => navigate('/', { replace: true }),
        onError: (error: ApiError) => {
          if (error.errors) {
            Object.entries(error.errors).forEach(([field, messages]) => {
              const fieldName =
                field === 'password_confirmation' ? 'passwordConfirmation' : field
              setError(fieldName as keyof RegisterValues, { message: messages[0] })
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

  return (
    <div className="flex min-h-screen items-center justify-center bg-background p-4">
      <div className="w-full max-w-md">
        <Card>
          <CardHeader>
            <CardTitle>Create an account</CardTitle>
            <CardDescription>Register to start managing your passwords</CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="name">Name</Label>
                <Input
                  id="name"
                  type="text"
                  placeholder="John Doe"
                  autoComplete="name"
                  autoFocus
                  {...registerField('name')}
                />
                {errors.name && (
                  <p className="text-destructive text-sm">{errors.name.message}</p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="email">Email</Label>
                <Input
                  id="email"
                  type="email"
                  placeholder="you@company.com"
                  autoComplete="email"
                  {...registerField('email')}
                />
                {errors.email && (
                  <p className="text-destructive text-sm">{errors.email.message}</p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="password">Password</Label>
                <Input
                  id="password"
                  type="password"
                  autoComplete="new-password"
                  {...registerField('password')}
                />
                {errors.password && (
                  <p className="text-destructive text-sm">{errors.password.message}</p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="passwordConfirmation">Confirm password</Label>
                <Input
                  id="passwordConfirmation"
                  type="password"
                  autoComplete="new-password"
                  {...registerField('passwordConfirmation')}
                />
                {errors.passwordConfirmation && (
                  <p className="text-destructive text-sm">
                    {errors.passwordConfirmation.message}
                  </p>
                )}
              </div>

              <Button type="submit" className="w-full" disabled={register.isPending}>
                {register.isPending ? 'Creating account...' : 'Register'}
              </Button>

              <p className="text-center text-muted-foreground text-sm">
                Already have an account?{' '}
                <Link to="/login" className="hover:underline">
                  Sign in
                </Link>
              </p>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
