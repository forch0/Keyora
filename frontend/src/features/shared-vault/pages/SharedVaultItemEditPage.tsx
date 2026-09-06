import { useState } from 'react'
import { useParams, useNavigate, Link } from 'react-router-dom'
import { ArrowLeft } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { EmptyState } from '@/components/shared/EmptyState'
import { VaultItemForm } from '@/features/vault/components/VaultItemForm'
import {
  useOrgVaultItem,
  useUpdateOrgVaultItem,
} from '@/features/shared-vault/hooks/use-shared-vault'
import type { SharedVaultItemFormData } from '@/features/shared-vault/hooks/use-shared-vault'
import type { ApiError } from '@/types/api-error'
import type { VaultItem } from '@/types/vault'
import { Lock } from 'lucide-react'

export function SharedVaultItemEditPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const itemId = parseInt(id ?? '0', 10)

  const { data: item, isLoading, isError } = useOrgVaultItem(itemId)
  const updateItem = useUpdateOrgVaultItem(itemId)
  const [submitError, setSubmitError] = useState<ApiError | null>(null)

  const handleSubmit = (data: SharedVaultItemFormData) => {
    setSubmitError(null)
    updateItem.mutate(data, {
      onSuccess: () => {
        toast.success('Shared item updated.')
        navigate(`/shared/items/${itemId}`, { replace: true })
      },
      onError: (error: ApiError) => {
        setSubmitError(error)
        if (error.status === 403) {
          toast.error('You do not have permission to edit this item.')
        } else if (!error.errors) {
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
        description="This item may have been deleted, or you don't have access."
        action={
          <Button variant="outline" onClick={() => navigate('/shared')}>
            Back to shared vault
          </Button>
        }
      />
    )
  }

  // Adapt SharedVaultItem to VaultItem shape expected by VaultItemForm
  const formData = {
    ...item,
    tags: [],
    last_accessed_at: null,
    archived_at: null,
  } as unknown as VaultItem

  return (
    <div className="space-y-4">
      <Link to={`/shared/items/${itemId}`}>
        <Button variant="ghost" size="sm">
          <ArrowLeft className="mr-2 h-4 w-4" />
          Back to item
        </Button>
      </Link>

      <VaultItemForm
        mode="edit"
        initialData={formData}
        onSubmit={handleSubmit as (data: unknown) => void}
        isSubmitting={updateItem.isPending}
        error={submitError}
      />
    </div>
  )
}
