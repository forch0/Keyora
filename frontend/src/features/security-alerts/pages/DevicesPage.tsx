import { useState } from 'react'
import { Link } from 'react-router-dom'
import {
  Smartphone,
  Laptop,
  Monitor,
  Trash2,
  ArrowLeft,
  Loader2,
} from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { EmptyState } from '@/components/shared/EmptyState'
import {
  useDevices,
  useRevokeDevice,
} from '@/features/security-alerts/hooks/use-security-alerts'
import type { Device } from '@/types/security-alert'

function deviceIcon(deviceType: string) {
  if (deviceType === 'mobile' || deviceType === 'tablet') return Smartphone
  if (deviceType === 'laptop') return Laptop
  return Monitor
}

function isCurrentDevice(device: Device): boolean {
  const v = device.is_current_device
  return v === true || v === 'true' || v === '1'
}

export function DevicesPage() {
  const { data: devices, isLoading } = useDevices()
  const revokeMutation = useRevokeDevice()
  const [confirmingRevoke, setConfirmingRevoke] = useState<number | null>(null)

  const handleRevoke = (device: Device) => {
    revokeMutation.mutate(device.id, {
      onSuccess: () => {
        toast.success('Device revoked.')
        setConfirmingRevoke(null)
      },
      onError: () => toast.error('Failed to revoke device.'),
    })
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" asChild>
          <Link to="/settings">
            <ArrowLeft className="mr-1 h-4 w-4" />
            Settings
          </Link>
        </Button>
        <div>
          <h1 className="text-2xl font-bold">Devices & Sessions</h1>
          <p className="text-muted-foreground text-sm">
            Manage active devices and sessions
          </p>
        </div>
      </div>

      {isLoading ? (
        <div className="space-y-2">
          {Array.from({ length: 3 }).map((_, i) => (
            <Skeleton key={i} className="h-20" />
          ))}
        </div>
      ) : !devices || devices.length === 0 ? (
        <EmptyState
          icon={Laptop}
          title="No devices"
          description="Active devices and sessions will appear here."
        />
      ) : (
        <div className="space-y-2">
          {devices.map((device) => {
            const Icon = deviceIcon(device.device_type)
            const current = isCurrentDevice(device)

            return (
              <Card key={device.id}>
                <CardContent className="flex items-center gap-3 p-4">
                  <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded bg-muted">
                    <Icon className="h-5 w-5 text-muted-foreground" />
                  </div>
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                      <span className="font-medium text-sm">
                        {device.browser} on {device.os}
                      </span>
                      {current && (
                        <Badge variant="default" className="text-xs">
                          This device
                        </Badge>
                      )}
                    </div>
                    <div className="mt-0.5 flex flex-wrap items-center gap-x-2 text-muted-foreground text-xs">
                      <span>{device.ip_address}</span>
                      <span>·</span>
                      <span>Last active: {device.last_seen_at ? new Date(device.last_seen_at).toLocaleString() : '—'}</span>
                    </div>
                    <p className="mt-0.5 text-muted-foreground text-xs">
                      First seen: {device.first_seen_at ? new Date(device.first_seen_at).toLocaleDateString() : '—'}
                    </p>
                  </div>
                  <div className="shrink-0">
                    {confirmingRevoke === device.id ? (
                      <div className="flex items-center gap-2">
                        <span className="text-xs">This will log out that device.</span>
                        <Button
                          variant="destructive"
                          size="sm"
                          onClick={() => handleRevoke(device)}
                          disabled={revokeMutation.isPending}
                        >
                          {revokeMutation.isPending ? (
                            <Loader2 className="h-3.5 w-3.5 animate-spin" />
                          ) : (
                            'Confirm'
                          )}
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => setConfirmingRevoke(null)}
                        >
                          Cancel
                        </Button>
                      </div>
                    ) : (
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => setConfirmingRevoke(device.id)}
                        disabled={current}
                        title={current ? 'Cannot revoke current device' : 'Revoke device'}
                        className={current ? '' : 'text-destructive'}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    )}
                  </div>
                </CardContent>
              </Card>
            )
          })}
        </div>
      )}
    </div>
  )
}
