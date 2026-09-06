import { useState } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { ArrowLeft } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { VaultItemForm } from '@/features/vault/components/VaultItemForm'
import { useCreateOrgVaultItem } from '@/features/shared-vault/hooks/use-shared-vault'
import type { SharedVaultItemFormData } from '@/features/shared-vault/hooks/use-shared-vault'
import type { ApiError } from '@/types/api-error'

export function SharedVaultItemCreatePage() {
  const navigate = useNavigate()
  const createItem = useCreateOrgVaultItem()
  const [submitError, setSubmitError] = useState<ApiError | null>(null)

  const handleSubmit = (data: SharedVaultItemFormData) => {
    setSubmitError(null)
    createItem.mutate(data, {
      onSuccess: (response) => {
        toast.success('Shared item created.')
        navigate(`/shared/items/${response.data.id}`, { replace: true })
      },
      onError: (error: ApiError) => {
        setSubmitError(error)
        if (error.status === 403) {
          toast.error('You do not have permission to create shared items.')
        } else if (!error.errors) {
          toast.error(error.message)
        }
      },
    })
  }

  return (
    <div className="space-y-4">
      <Link to="/shared">
        <Button variant="ghost" size="sm">
          <ArrowLeft className="mr-2 h-4 w-4" />
          Back to shared vault
        </Button>
      </Link>

      <VaultItemForm
        mode="create"
        onSubmit={handleSubmit as (data: unknown) => void}
        isSubmitting={createItem.isPending}
        error={submitError}
      />
    </div>
  )
}
