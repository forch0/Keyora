import { useState } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { ArrowLeft } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { VaultItemForm } from '@/features/vault/components/VaultItemForm'
import { useCreateVaultItem } from '@/features/vault/hooks/use-vault-item-mutations'
import type { VaultItemFormData } from '@/features/vault/hooks/use-vault-item-mutations'
import type { ApiError } from '@/types/api-error'

export function VaultItemCreatePage() {
  const navigate = useNavigate()
  const createItem = useCreateVaultItem()
  const [submitError, setSubmitError] = useState<ApiError | null>(null)

  const handleSubmit = (data: VaultItemFormData) => {
    setSubmitError(null)
    createItem.mutate(data, {
      onSuccess: (response) => {
        toast.success('Vault item created.')
        navigate(`/vault/items/${response.data.id}`, { replace: true })
      },
      onError: (error: ApiError) => {
        setSubmitError(error)
        if (!error.errors) {
          toast.error(error.message)
        }
      },
    })
  }

  return (
    <div className="space-y-4">
      <Link to="/vault">
        <Button variant="ghost" size="sm">
          <ArrowLeft className="mr-2 h-4 w-4" />
          Back to vault
        </Button>
      </Link>

      <VaultItemForm
        mode="create"
        onSubmit={handleSubmit}
        isSubmitting={createItem.isPending}
        error={submitError}
      />
    </div>
  )
}
