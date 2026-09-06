import { useState } from 'react'
import { AlertTriangle } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useCreateSecureLink } from '@/features/secure-links/hooks/use-secure-links'
import type { SecureLinkResourceType, SecureLink } from '@/types/secure-link'

const EXPIRY_OPTIONS = [
  { value: '1h', label: '1 hour', hours: 1 },
  { value: '24h', label: '24 hours', hours: 24 },
  { value: '7d', label: '7 days', hours: 168 },
  { value: '30d', label: '30 days', hours: 720 },
  { value: 'none', label: 'No expiry', hours: 0 },
]

export function CreateSecureLinkDialog({
  resource,
  id,
  open,
  onOpenChange,
  onCreated,
}: {
  resource: SecureLinkResourceType
  id: number
  open: boolean
  onOpenChange: (open: boolean) => void
  onCreated?: (link: SecureLink) => void
}) {
  const [password, setPassword] = useState('')
  const [expiry, setExpiry] = useState('24h')
  const [maxViews, setMaxViews] = useState('')
  const [isOneTime, setIsOneTime] = useState(false)
  const [recipientEmail, setRecipientEmail] = useState('')
  const [requireEmailVerification, setRequireEmailVerification] = useState(false)
  const [permission, setPermission] = useState<'view' | 'download'>('view')

  const createLink = useCreateSecureLink(resource, id)

  const reset = () => {
    setPassword('')
    setExpiry('24h')
    setMaxViews('')
    setIsOneTime(false)
    setRecipientEmail('')
    setRequireEmailVerification(false)
    setPermission('view')
  }

  const handleSubmit = () => {
    const expiryOption = EXPIRY_OPTIONS.find((e) => e.value === expiry)
    const expiresAt =
      expiryOption && expiryOption.hours > 0
        ? new Date(Date.now() + expiryOption.hours * 60 * 60 * 1000).toISOString()
        : null

    createLink.mutate(
      {
        password: password || null,
        expires_at: expiresAt,
        max_views: maxViews ? parseInt(maxViews, 10) : null,
        is_one_time: isOneTime || null,
        recipient_email: recipientEmail || null,
        require_email_verification: requireEmailVerification || null,
        permission,
        download_enabled: permission === 'download' || null,
      },
      {
        onSuccess: (res) => {
          toast.success('Secure link created.')
          onCreated?.(res.data)
          reset()
          onOpenChange(false)
        },
        onError: () => toast.error('Failed to create secure link.'),
      },
    )
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Create Secure Link</DialogTitle>
          <DialogDescription>
            Generate a shareable link for external users. No login required.
          </DialogDescription>
        </DialogHeader>

        <div className="rounded border border-orange-500/50 bg-orange-500/10 p-3">
          <p className="flex items-center gap-2 text-orange-700 text-sm dark:text-orange-400">
            <AlertTriangle className="h-4 w-4 shrink-0" />
            This link bypasses authentication — share carefully.
          </p>
        </div>

        <div className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="recipient-email">Recipient email (optional)</Label>
            <Input
              id="recipient-email"
              type="email"
              value={recipientEmail}
              onChange={(e) => setRecipientEmail(e.target.value)}
              placeholder="someone@example.com"
            />
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-2">
              <Label>Permission</Label>
              <Select value={permission} onValueChange={(v) => setPermission(v as 'view' | 'download')}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="view">View only</SelectItem>
                  <SelectItem value="download">View & download</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Expires in</Label>
              <Select value={expiry} onValueChange={setExpiry}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {EXPIRY_OPTIONS.map((e) => (
                    <SelectItem key={e.value} value={e.value}>
                      {e.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="password">Password (optional)</Label>
            <Input
              id="password"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="Leave empty for no password"
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="max-views">Max views (optional)</Label>
            <Input
              id="max-views"
              type="number"
              min="1"
              value={maxViews}
              onChange={(e) => setMaxViews(e.target.value)}
              placeholder="Unlimited"
            />
          </div>

          <div className="flex items-center gap-2">
            <Switch id="one-time" checked={isOneTime} onCheckedChange={setIsOneTime} />
            <Label htmlFor="one-time" className="text-sm">
              One-time link (auto-revoke after first view)
            </Label>
          </div>

          {recipientEmail && (
            <div className="flex items-center gap-2">
              <Switch
                id="email-verify"
                checked={requireEmailVerification}
                onCheckedChange={setRequireEmailVerification}
              />
              <Label htmlFor="email-verify" className="text-sm">
                Require email verification
              </Label>
            </div>
          )}
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancel
          </Button>
          <Button onClick={handleSubmit} disabled={createLink.isPending}>
            {createLink.isPending ? 'Creating...' : 'Create Link'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
