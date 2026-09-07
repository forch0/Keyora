import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { Save, Trash2, Users, Shield, ArrowLeft } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import { useAuthStore } from '@/stores/auth-store'
import {
  useTenantSettings,
  useUpdateTenant,
  useDeleteTenant,
} from '@/features/admin/hooks/use-admin'

export function TenantSettingsPage() {
  const tenantId = useAuthStore((s) => s.selectedTenantId)
  const { data: tenant, isLoading } = useTenantSettings(tenantId)
  const updateMutation = useUpdateTenant()
  const deleteMutation = useDeleteTenant()

  const [name, setName] = useState('')
  const [slug, setSlug] = useState('')
  const [edited, setEdited] = useState(false)
  const [confirmingDelete, setConfirmingDelete] = useState(false)

  useEffect(() => {
    if (tenant) {
      setName(tenant.name)
      setSlug(tenant.slug)
      setEdited(false)
    }
  }, [tenant])

  const handleSave = () => {
    if (!tenantId) return
    if (!name.trim()) {
      toast.error('Name is required.')
      return
    }
    updateMutation.mutate(
      { id: tenantId, data: { name: name.trim(), slug: slug.trim() || undefined } },
      {
        onSuccess: () => {
          toast.success('Tenant updated.')
          setEdited(false)
        },
        onError: () => toast.error('Failed to update tenant.'),
      },
    )
  }

  const handleDelete = () => {
    if (!tenantId) return
    deleteMutation.mutate(tenantId, {
      onSuccess: () => {
        toast.success('Tenant deleted.')
        window.location.href = '/'
      },
      onError: () => toast.error('Failed to delete tenant.'),
    })
  }

  const isOwner = tenant?.role === 'owner'
  const isAdmin = tenant?.role === 'admin' || isOwner

  if (isLoading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-48" />
        <Skeleton className="h-64" />
      </div>
    )
  }

  if (!tenant) {
    return (
      <div className="py-12 text-center">
        <p className="text-muted-foreground">Tenant not found.</p>
      </div>
    )
  }

  if (!isAdmin) {
    return (
      <div className="py-12 text-center">
        <Shield className="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
        <p className="text-muted-foreground">Admin access required.</p>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" asChild>
          <Link to="/admin">
            <ArrowLeft className="mr-1 h-4 w-4" />
            Admin
          </Link>
        </Button>
        <div>
          <h1 className="text-2xl font-bold">Workspace Settings</h1>
          <p className="text-muted-foreground text-sm">
            Manage workspace name and configuration
          </p>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>General</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="tenant-name">Workspace Name</Label>
            <Input
              id="tenant-name"
              value={name}
              onChange={(e) => {
                setName(e.target.value)
                setEdited(true)
              }}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="tenant-slug">Slug</Label>
            <Input
              id="tenant-slug"
              value={slug}
              onChange={(e) => {
                setSlug(e.target.value)
                setEdited(true)
              }}
              placeholder="my-workspace"
            />
            <p className="text-muted-foreground text-xs">
              Used in URLs and identifiers
            </p>
          </div>
          <div className="flex items-center gap-2">
            <Badge variant="outline">Plan: {tenant.plan}</Badge>
            <Badge variant="outline">Role: {tenant.role}</Badge>
          </div>

          {edited && (
            <div className="flex justify-end gap-2">
              <Button
                variant="outline"
                size="sm"
                onClick={() => {
                  setName(tenant.name)
                  setSlug(tenant.slug)
                  setEdited(false)
                }}
              >
                Cancel
              </Button>
              <Button size="sm" onClick={handleSave} disabled={updateMutation.isPending}>
                <Save className="mr-1 h-3.5 w-3.5" />
                {updateMutation.isPending ? 'Saving...' : 'Save'}
              </Button>
            </div>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Quick Links</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex gap-2">
            <Button variant="outline" size="sm" asChild>
              <Link to="/admin/members">
                <Users className="mr-1 h-4 w-4" />
                Manage Members
              </Link>
            </Button>
          </div>
        </CardContent>
      </Card>

      {isOwner && (
        <Card className="border-destructive">
          <CardHeader>
            <CardTitle className="text-destructive">Danger Zone</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="mb-3 text-sm">
              Deleting this workspace is irreversible. All data, members, and resources
              will be permanently removed.
            </p>
            {confirmingDelete ? (
              <div className="flex items-center gap-2">
                <span className="text-sm font-medium">
                  Type the workspace name to confirm:
                </span>
                <Button variant="destructive" size="sm" onClick={handleDelete} disabled={deleteMutation.isPending}>
                  <Trash2 className="mr-1 h-3.5 w-3.5" />
                  {deleteMutation.isPending ? 'Deleting...' : `Delete "${tenant.name}"`}
                </Button>
                <Button variant="outline" size="sm" onClick={() => setConfirmingDelete(false)}>
                  Cancel
                </Button>
              </div>
            ) : (
              <Button variant="destructive" size="sm" onClick={() => setConfirmingDelete(true)}>
                <Trash2 className="mr-1 h-3.5 w-3.5" />
                Delete Workspace
              </Button>
            )}
          </CardContent>
        </Card>
      )}
    </div>
  )
}
