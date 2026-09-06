import { useState } from 'react'
import { useParams, useNavigate, Link } from 'react-router-dom'
import { ArrowLeft } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { EmptyState } from '@/components/shared/EmptyState'
import { Lock } from 'lucide-react'
import { VaultItemForm } from '@/features/vault/components/VaultItemForm'
import { useVaultItem } from '@/features/vault/hooks/use-vault-items'
import { useUpdateVaultItem } from '@/features/vault/hooks/use-vault-item-mutations'
import type { VaultItemFormData } from '@/features/vault/hooks/use-vault-item-mutations'
import type { ApiError } from '@/types/api-error'

export function VaultItemEditPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const itemId = parseInt(id ?? '0', 10)

  const { data: item, isLoading, isError } = useVaultItem(itemId)
  const updateItem = useUpdateVaultItem(itemId)
  const [submitError, setSubmitError] = useState<ApiError | null>(null)

  const handleSubmit = (data: VaultItemFormData) => {
    setSubmitError(null)
    updateItem.mutate(data, {
      onSuccess: () => {
        toast.success('Vault item updated.')
        navigate(`/vault/items/${itemId}`, { replace: true })
      },
      onError: (error: ApiError) => {
        setSubmitError(error)
        if (!error.errors) {
          toast.error(error.message)
        }
      },
    })
  }

  if (isLoading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-32" />
        <Skeleton className="h-96 w-full" />
      </div>
    )
  }

  if (isError || !item) {
    return (
      <EmptyState
        icon={Lock}
        title="Item not found"
        description="This vault item may have been deleted."
        action={
          <Button variant="outline" onClick={() => navigate('/vault')}>
            Back to vault
          </Button>
        }
      />
    )
  }

  return (
    <div className="space-y-4">
      <Link to={`/vault/items/${itemId}`}>
        <Button variant="ghost" size="sm">
          <ArrowLeft className="mr-2 h-4 w-4" />
          Back to item
        </Button>
      </Link>

      <VaultItemForm
        mode="edit"
        initialData={item}
        onSubmit={handleSubmit}
        isSubmitting={updateItem.isPending}
        error={submitError}
      />
    </div>
  )
}
