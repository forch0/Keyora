import { useState } from 'react'
import { ChevronsUpDown, Plus, Check, Building2 } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import { toast } from 'sonner'
import { useAuthStore } from '@/stores/auth-store'
import { useTenants, useCreateTenant } from '@/features/tenant/hooks/use-tenants'

export function TenantSwitcher() {
  const { data: tenants, isLoading } = useTenants()
  const createTenant = useCreateTenant()
  const { selectedTenantId, setTenant } = useAuthStore()
  const [open, setOpen] = useState(false)
  const [showCreateDialog, setShowCreateDialog] = useState(false)
  const [newName, setNewName] = useState('')
  const [newSlug, setNewSlug] = useState('')

  const currentTenant = tenants?.find((t) => t.id === selectedTenantId)

  const handleSelect = (id: number) => {
    setTenant(id)
    setOpen(false)
  }

  const handleCreate = (e: React.FormEvent) => {
    e.preventDefault()
    if (!newName.trim()) return
    createTenant.mutate(
      { name: newName.trim(), slug: newSlug.trim() || null },
      {
        onSuccess: (res) => {
          setTenant(res.data.id)
          setShowCreateDialog(false)
          setNewName('')
          setNewSlug('')
          toast.success('Workspace created.')
        },
        onError: () => toast.error('Failed to create workspace.'),
      },
    )
  }

  if (isLoading) {
    return <Skeleton className="h-8 w-40" />
  }

  // No tenants — show create prompt
  if (!tenants || tenants.length === 0) {
    return (
      <>
        <Button variant="outline" size="sm" onClick={() => setShowCreateDialog(true)}>
          <Plus className="mr-2 h-4 w-4" />
          Create workspace
        </Button>
        <CreateTenantDialog
          open={showCreateDialog}
          onOpenChange={setShowCreateDialog}
          name={newName}
          slug={newSlug}
          onNameChange={setNewName}
          onSlugChange={setNewSlug}
          onSubmit={handleCreate}
          isSubmitting={createTenant.isPending}
        />
      </>
    )
  }

  return (
    <>
      <Popover open={open} onOpenChange={setOpen}>
        <PopoverTrigger asChild>
          <Button variant="outline" size="sm" className="gap-2">
            <Building2 className="h-4 w-4 shrink-0" />
            <span className="hidden max-w-[120px] truncate sm:inline">
              {currentTenant?.name ?? 'Select workspace'}
            </span>
            <ChevronsUpDown className="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
          </Button>
        </PopoverTrigger>
        <PopoverContent className="w-64" align="start">
          <div className="space-y-1">
            <p className="px-2 py-1.5 text-muted-foreground text-xs font-medium">
              Workspaces
            </p>
            {tenants.map((tenant) => (
              <button
                key={tenant.id}
                onClick={() => handleSelect(tenant.id)}
                className={cn(
                  'flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-accent',
                  tenant.id === selectedTenantId && 'bg-accent',
                )}
              >
                <Building2 className="h-4 w-4 shrink-0 text-muted-foreground" />
                <div className="flex-1 text-left">
                  <p className="truncate font-medium">{tenant.name}</p>
                  {tenant.role && (
                    <p className="text-muted-foreground text-xs capitalize">{tenant.role}</p>
                  )}
                </div>
                {tenant.id === selectedTenantId && <Check className="h-4 w-4" />}
              </button>
            ))}
            <div className="border-t pt-1">
              <button
                onClick={() => {
                  setOpen(false)
                  setShowCreateDialog(true)
                }}
                className="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-accent"
              >
                <Plus className="h-4 w-4" />
                Create workspace
              </button>
            </div>
          </div>
        </PopoverContent>
      </Popover>

      <CreateTenantDialog
        open={showCreateDialog}
        onOpenChange={setShowCreateDialog}
        name={newName}
        slug={newSlug}
        onNameChange={setNewName}
        onSlugChange={setNewSlug}
        onSubmit={handleCreate}
        isSubmitting={createTenant.isPending}
      />
    </>
  )
}

// ─── Create tenant dialog ───────────────────────────────────────────────────

interface CreateTenantDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  name: string
  slug: string
  onNameChange: (v: string) => void
  onSlugChange: (v: string) => void
  onSubmit: (e: React.FormEvent) => void
  isSubmitting: boolean
}

function CreateTenantDialog({
  open,
  onOpenChange,
  name,
  slug,
  onNameChange,
  onSlugChange,
  onSubmit,
  isSubmitting,
}: CreateTenantDialogProps) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Create Workspace</DialogTitle>
          <DialogDescription>
            Create a new workspace for your organization. You'll be the owner.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={onSubmit} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="tenant-name">Workspace name *</Label>
            <Input
              id="tenant-name"
              value={name}
              onChange={(e) => onNameChange(e.target.value)}
              placeholder="Acme Inc."
              autoFocus
              required
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="tenant-slug">Slug (optional)</Label>
            <Input
              id="tenant-slug"
              value={slug}
              onChange={(e) => onSlugChange(e.target.value)}
              placeholder="acme"
            />
            <p className="text-muted-foreground text-xs">
              Used in URLs. Auto-generated from name if left blank.
            </p>
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
              Cancel
            </Button>
            <Button type="submit" disabled={isSubmitting || !name.trim()}>
              {isSubmitting ? 'Creating...' : 'Create'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}
