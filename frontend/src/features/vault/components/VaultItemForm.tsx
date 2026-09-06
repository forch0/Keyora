import { useState } from 'react'
import { useForm, useFieldArray, Controller } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Plus, Eye, EyeOff, X } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Separator } from '@/components/ui/separator'
import type { ApiError } from '@/types/api-error'
import type { VaultItem } from '@/types/vault'
import type { VaultItemFormData, ItemTypeCode } from '@/features/vault/hooks/use-vault-item-mutations'
import { FolderSelector } from '@/features/vault/components/FolderSelector'
import { TagSelector } from '@/features/vault/components/TagSelector'

// ─── Types & validation ─────────────────────────────────────────────────────

const TYPE_OPTIONS: { value: ItemTypeCode; label: string }[] = [
  { value: 'password', label: 'Password' },
  { value: 'api_key', label: 'API Key' },
  { value: 'server', label: 'Server Credential' },
  { value: 'database', label: 'Database Credential' },
]

const baseSchema = z.object({
  name: z.string().min(1, 'Name is required').max(255, 'Name is too long'),
  type: z.enum(['password', 'api_key', 'server', 'database']),
  username: z.string().max(255).optional().nullable(),
  password: z.string().optional().nullable(),
  url: z.string().url('Must be a valid URL').optional().or(z.literal('')).nullable(),
  notes: z.string().optional().nullable(),
  custom_fields: z
    .array(
      z.object({
        key: z.string().min(1, 'Key is required'),
        value: z.string(),
      }),
    )
    .optional()
    .nullable(),
  folder_id: z.number().nullable().optional(),
  tag_ids: z.array(z.number()).optional().nullable(),
})

type FormValues = z.infer<typeof baseSchema>

interface VaultItemFormProps {
  mode: 'create' | 'edit'
  initialData?: VaultItem
  onSubmit: (data: VaultItemFormData) => void
  isSubmitting: boolean
  error?: ApiError | null
}

// ─── Component ──────────────────────────────────────────────────────────────

export function VaultItemForm({
  mode,
  initialData,
  onSubmit,
  isSubmitting,
  error,
}: VaultItemFormProps) {
  const [showPassword, setShowPassword] = useState(false)
  const [changePassword, setChangePassword] = useState(mode === 'create')

  const {
    register,
    control,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(baseSchema),
    defaultValues: {
      name: initialData?.name ?? '',
      type: (initialData?.type as ItemTypeCode) ?? 'password',
      username: initialData?.username ?? '',
      password: '',
      url: initialData?.url ?? '',
      notes: initialData?.notes ?? '',
      custom_fields: initialData?.custom_fields ?? [],
      folder_id: initialData?.folder_id ?? null,
      tag_ids: initialData?.tags?.map((t) => t.id) ?? [],
    },
  })

  const { fields, append, remove } = useFieldArray({
    control,
    name: 'custom_fields',
  })

  const handleFormSubmit = (values: FormValues) => {
    const data: VaultItemFormData = {
      name: values.name,
      type: values.type,
      username: values.username || null,
      password: mode === 'edit' && !changePassword ? undefined : (values.password || null),
      url: values.url || null,
      notes: values.notes || null,
      custom_fields: values.custom_fields?.length ? values.custom_fields : null,
      folder_id: values.folder_id ?? null,
      tag_ids: values.tag_ids ?? null,
    }
    onSubmit(data)
  }

  // Map server validation errors to form fields
  const handleServerError = (err: ApiError) => {
    if (err.errors) {
      Object.entries(err.errors).forEach(([field, messages]) => {
        const fieldName = field === 'password_confirmation' ? 'password' : field
        if (fieldName in (errors as Record<string, unknown>)) {
          setError(fieldName as keyof FormValues, { message: messages[0] })
        }
      })
    }
  }

  // Expose error handler via effect-like pattern
  if (error) {
    handleServerError(error)
  }

  return (
    <form onSubmit={handleSubmit(handleFormSubmit)} className="space-y-6">
      {/* Basic fields */}
      <Card>
        <CardHeader>
          <CardTitle>{mode === 'create' ? 'New Vault Item' : 'Edit Vault Item'}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Name */}
          <div className="space-y-2">
            <Label htmlFor="name">Name *</Label>
            <Input id="name" placeholder="e.g. GitHub Account" {...register('name')} />
            {errors.name && <p className="text-destructive text-sm">{errors.name.message}</p>}
          </div>

          {/* Type */}
          <div className="space-y-2">
            <Label htmlFor="type">Type *</Label>
            <Controller
              control={control}
              name="type"
              render={({ field }) => (
                <Select value={field.value} onValueChange={field.onChange}>
                  <SelectTrigger id="type">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {TYPE_OPTIONS.map((opt) => (
                      <SelectItem key={opt.value} value={opt.value}>
                        {opt.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              )}
            />
          </div>

          {/* Username */}
          <div className="space-y-2">
            <Label htmlFor="username">Username / Email</Label>
            <Input
              id="username"
              placeholder="username or email"
              autoComplete="off"
              {...register('username')}
            />
            {errors.username && (
              <p className="text-destructive text-sm">{errors.username.message}</p>
            )}
          </div>

          {/* Password */}
          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <Label htmlFor="password">Password</Label>
              {mode === 'edit' && !changePassword && (
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  onClick={() => {
                    setChangePassword(true)
                    setShowPassword(true)
                  }}
                >
                  Change password
                </Button>
              )}
            </div>
            {mode === 'edit' && !changePassword ? (
              <div className="flex items-center gap-2 rounded-md border bg-muted px-3 py-2">
                <span className="text-muted-foreground text-sm">••••••••</span>
              </div>
            ) : (
              <div className="flex items-center gap-2">
                <Input
                  id="password"
                  type={showPassword ? 'text' : 'password'}
                  placeholder="Enter password"
                  autoComplete="new-password"
                  {...register('password')}
                />
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  onClick={() => setShowPassword(!showPassword)}
                  className="shrink-0"
                >
                  {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </Button>
                {/* TODO: Module F08 — password generator button */}
              </div>
            )}
            {errors.password && (
              <p className="text-destructive text-sm">{errors.password.message}</p>
            )}
          </div>

          {/* URL */}
          <div className="space-y-2">
            <Label htmlFor="url">URL</Label>
            <Input id="url" placeholder="https://example.com" {...register('url')} />
            {errors.url && <p className="text-destructive text-sm">{errors.url.message}</p>}
          </div>

          {/* Notes */}
          <div className="space-y-2">
            <Label htmlFor="notes">Notes</Label>
            <textarea
              id="notes"
              rows={4}
              placeholder="Additional notes..."
              className="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
              {...register('notes')}
            />
            {errors.notes && <p className="text-destructive text-sm">{errors.notes.message}</p>}
          </div>
        </CardContent>
      </Card>

      {/* Custom fields */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle>Custom Fields</CardTitle>
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={() => append({ key: '', value: '' })}
            >
              <Plus className="mr-2 h-4 w-4" />
              Add field
            </Button>
          </div>
        </CardHeader>
        <CardContent className="space-y-3">
          {fields.length === 0 && (
            <p className="text-muted-foreground text-sm">No custom fields added.</p>
          )}
          {fields.map((field, index) => (
            <div key={field.id} className="flex items-start gap-2">
              <div className="flex-1 space-y-1">
                <Input
                  placeholder="Key"
                  {...register(`custom_fields.${index}.key`)}
                />
                {errors.custom_fields?.[index]?.key && (
                  <p className="text-destructive text-sm">
                    {errors.custom_fields[index]?.key?.message}
                  </p>
                )}
              </div>
              <div className="flex-1 space-y-1">
                <Input
                  placeholder="Value"
                  {...register(`custom_fields.${index}.value`)}
                />
              </div>
              <Button
                type="button"
                variant="ghost"
                size="sm"
                onClick={() => remove(index)}
                className="mt-0 shrink-0 text-destructive"
              >
                <X className="h-4 w-4" />
              </Button>
            </div>
          ))}
        </CardContent>
      </Card>

      {/* Folder & Tags */}
      <Card>
        <CardHeader>
          <CardTitle>Organization</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label>Folder</Label>
            <Controller
              control={control}
              name="folder_id"
              render={({ field }) => (
                <FolderSelector
                  value={field.value ?? null}
                  onChange={field.onChange}
                />
              )}
            />
          </div>
          <Separator />
          <div className="space-y-2">
            <Label>Tags</Label>
            <Controller
              control={control}
              name="tag_ids"
              render={({ field }) => (
                <TagSelector
                  selectedTagIds={field.value ?? []}
                  onChange={field.onChange}
                />
              )}
            />
          </div>
        </CardContent>
      </Card>

      {/* Submit */}
      <div className="flex items-center gap-3">
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting
            ? 'Saving...'
            : mode === 'create'
              ? 'Create item'
              : 'Save changes'}
        </Button>
        <Button type="button" variant="outline" asChild>
          <a href={mode === 'edit' && initialData ? `/vault/items/${initialData.id}` : '/vault'}>
            Cancel
          </a>
        </Button>
      </div>
    </form>
  )
}
