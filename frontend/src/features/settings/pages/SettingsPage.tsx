import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import {
  User as UserIcon,
  KeyRound,
  Shield,
  Save,
  Smartphone,
  Download,
  Eye,
  EyeOff,
  Loader2,
  CheckCircle,
  AlertTriangle,
  Monitor,
} from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import { useAuthStore } from '@/stores/auth-store'
import {
  useProfile,
  useUpdateProfile,
  useChangePassword,
  useEnable2fa,
  useConfirm2fa,
  useDisable2fa,
  useRecoveryCodes,
} from '@/features/settings/hooks/use-settings'
import { useCheckStrength } from '@/features/tools/hooks/use-password-tools'

// ─── SettingsPage ───────────────────────────────────────────────────────────

export function SettingsPage() {
  return (
    <div className="mx-auto max-w-2xl space-y-6">
      <div>
        <h1 className="text-2xl font-bold">Settings</h1>
        <p className="text-muted-foreground text-sm">
          Manage your profile, password, and security settings
        </p>
      </div>

      <ProfileSection />
      <PasswordSection />
      <TwoFactorSection />
      <DevicesLink />
    </div>
  )
}

// ─── Profile section ────────────────────────────────────────────────────────

function ProfileSection() {
  const { data: user, isLoading } = useProfile()
  const updateMutation = useUpdateProfile()

  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [edited, setEdited] = useState(false)

  useEffect(() => {
    if (user) {
      setName(user.name)
      setEmail(user.email)
      setEdited(false)
    }
  }, [user])

  const handleSave = () => {
    if (!name.trim() || !email.trim()) {
      toast.error('Name and email are required.')
      return
    }
    updateMutation.mutate(
      { name: name.trim(), email: email.trim() },
      {
        onSuccess: () => {
          toast.success('Profile updated.')
          setEdited(false)
        },
        onError: () => toast.error('Failed to update profile.'),
      },
    )
  }

  if (isLoading) {
    return (
      <Card>
        <CardHeader>
          <Skeleton className="h-6 w-32" />
        </CardHeader>
        <CardContent className="space-y-3">
          <Skeleton className="h-10" />
          <Skeleton className="h-10" />
        </CardContent>
      </Card>
    )
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <UserIcon className="h-5 w-5" />
          Profile
        </CardTitle>
        <CardDescription>Update your name and email address</CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="space-y-2">
          <Label htmlFor="profile-name">Name</Label>
          <Input
            id="profile-name"
            value={name}
            onChange={(e) => {
              setName(e.target.value)
              setEdited(true)
            }}
          />
        </div>
        <div className="space-y-2">
          <Label htmlFor="profile-email">Email</Label>
          <Input
            id="profile-email"
            type="email"
            value={email}
            onChange={(e) => {
              setEmail(e.target.value)
              setEdited(true)
            }}
          />
          {user?.email_verified_at ? (
            <p className="flex items-center gap-1 text-green-600 text-xs">
              <CheckCircle className="h-3 w-3" />
              Email verified
            </p>
          ) : (
            <p className="flex items-center gap-1 text-yellow-600 text-xs">
              <AlertTriangle className="h-3 w-3" />
              Email not verified
            </p>
          )}
        </div>
        {edited && (
          <div className="flex justify-end gap-2">
            <Button
              variant="outline"
              size="sm"
              onClick={() => {
                if (user) {
                  setName(user.name)
                  setEmail(user.email)
                }
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
  )
}

// ─── Password section ───────────────────────────────────────────────────────

function PasswordSection() {
  const changeMutation = useChangePassword()
  const strengthMutation = useCheckStrength()

  const [currentPassword, setCurrentPassword] = useState('')
  const [newPassword, setNewPassword] = useState('')
  const [confirmPassword, setConfirmPassword] = useState('')
  const [showCurrent, setShowCurrent] = useState(false)
  const [showNew, setShowNew] = useState(false)

  // Debounced strength check
  useEffect(() => {
    if (!newPassword || newPassword.length < 4) return
    const timer = setTimeout(() => {
      strengthMutation.mutate({ password: newPassword })
    }, 400)
    return () => clearTimeout(timer)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [newPassword])

  const strength = strengthMutation.data?.data?.strength
  const strengthColor: Record<string, string> = {
    very_weak: 'bg-red-500',
    weak: 'bg-orange-500',
    fair: 'bg-yellow-500',
    strong: 'bg-green-500',
    very_strong: 'bg-green-600',
  }
  const strengthWidth: Record<string, string> = {
    very_weak: 'w-1/5',
    weak: 'w-2/5',
    fair: 'w-3/5',
    strong: 'w-4/5',
    very_strong: 'w-full',
  }

  const handleChangePassword = () => {
    if (!currentPassword || !newPassword || !confirmPassword) {
      toast.error('All fields are required.')
      return
    }
    if (newPassword !== confirmPassword) {
      toast.error('New passwords do not match.')
      return
    }
    changeMutation.mutate(
      {
        current_password: currentPassword,
        password: newPassword,
        password_confirmation: confirmPassword,
      },
      {
        onSuccess: () => {
          toast.success('Password changed.')
          setCurrentPassword('')
          setNewPassword('')
          setConfirmPassword('')
        },
        onError: () => toast.error('Failed to change password.'),
      },
    )
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <KeyRound className="h-5 w-5" />
          Password
        </CardTitle>
        <CardDescription>Change your account password</CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="space-y-2">
          <Label htmlFor="current-password">Current Password</Label>
          <div className="relative">
            <Input
              id="current-password"
              type={showCurrent ? 'text' : 'password'}
              value={currentPassword}
              onChange={(e) => setCurrentPassword(e.target.value)}
              className="pr-10"
            />
            <button
              type="button"
              onClick={() => setShowCurrent(!showCurrent)}
              className="absolute top-2.5 right-2.5 text-muted-foreground"
            >
              {showCurrent ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
            </button>
          </div>
        </div>
        <div className="space-y-2">
          <Label htmlFor="new-password">New Password</Label>
          <div className="relative">
            <Input
              id="new-password"
              type={showNew ? 'text' : 'password'}
              value={newPassword}
              onChange={(e) => setNewPassword(e.target.value)}
              className="pr-10"
            />
            <button
              type="button"
              onClick={() => setShowNew(!showNew)}
              className="absolute top-2.5 right-2.5 text-muted-foreground"
            >
              {showNew ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
            </button>
          </div>
          {strength && (
            <div className="space-y-1">
              <div className="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                <div
                  className={`h-full transition-all ${strengthColor[strength]} ${strengthWidth[strength]}`}
                />
              </div>
              <p className="text-muted-foreground text-xs capitalize">
                Strength: {strength.replace('_', ' ')}
              </p>
            </div>
          )}
        </div>
        <div className="space-y-2">
          <Label htmlFor="confirm-password">Confirm New Password</Label>
          <Input
            id="confirm-password"
            type="password"
            value={confirmPassword}
            onChange={(e) => setConfirmPassword(e.target.value)}
          />
        </div>
        <div className="flex justify-end">
          <Button
            size="sm"
            onClick={handleChangePassword}
            disabled={changeMutation.isPending || !currentPassword || !newPassword || !confirmPassword}
          >
            {changeMutation.isPending ? (
              <Loader2 className="mr-1 h-3.5 w-3.5 animate-spin" />
            ) : (
              <KeyRound className="mr-1 h-3.5 w-3.5" />
            )}
            Change Password
          </Button>
        </div>
      </CardContent>
    </Card>
  )
}

// ─── 2FA section ────────────────────────────────────────────────────────────

function TwoFactorSection() {
  const user = useAuthStore((s) => s.user)
  const twoFactorEnabled = user?.two_factor_enabled ?? false

  const [step, setStep] = useState<'idle' | 'enabling' | 'enabled' | 'disabling'>('idle')
  const [qrData, setQrData] = useState<{ secret: string; qr_code_uri: string } | null>(null)
  const [totpCode, setTotpCode] = useState('')
  const [recoveryCodes, setRecoveryCodes] = useState<string[] | string | null>(null)
  const [disablePassword, setDisablePassword] = useState('')

  const enableMutation = useEnable2fa()
  const confirmMutation = useConfirm2fa()
  const disableMutation = useDisable2fa()
  const recoveryCodesQuery = useRecoveryCodes()

  const handleEnable = () => {
    enableMutation.mutate(undefined, {
      onSuccess: (res) => {
        setQrData(res.data)
        setStep('enabling')
      },
      onError: () => toast.error('Failed to enable 2FA.'),
    })
  }

  const handleConfirm = () => {
    if (!totpCode.trim()) {
      toast.error('Please enter the TOTP code.')
      return
    }
    confirmMutation.mutate(totpCode, {
      onSuccess: (res) => {
        setRecoveryCodes(res.data.recovery_codes)
        setStep('enabled')
        setTotpCode('')
        toast.success('2FA enabled. Save your recovery codes.')
      },
      onError: () => toast.error('Invalid TOTP code.'),
    })
  }

  const handleDisable = () => {
    if (!disablePassword) {
      toast.error('Password is required.')
      return
    }
    disableMutation.mutate(disablePassword, {
      onSuccess: () => {
        toast.success('2FA disabled.')
        setStep('idle')
        setDisablePassword('')
      },
      onError: () => toast.error('Failed to disable 2FA.'),
    })
  }

  const handleViewRecoveryCodes = () => {
    recoveryCodesQuery.refetch().then((res) => {
      if (res.data) {
        setRecoveryCodes(res.data.data.recovery_codes)
        setStep('enabled')
      }
    }).catch(() => toast.error('Failed to fetch recovery codes.'))
  }

  const handleDownloadCodes = () => {
    if (!recoveryCodes) return
    const codes = Array.isArray(recoveryCodes) ? recoveryCodes.join('\n') : recoveryCodes
    const blob = new Blob([`Keyora Recovery Codes\n\n${codes}\n`], { type: 'text/plain' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'keyora-recovery-codes.txt'
    a.click()
    URL.revokeObjectURL(url)
  }

  const parseRecoveryCodes = (): string[] => {
    if (!recoveryCodes) return []
    if (Array.isArray(recoveryCodes)) return recoveryCodes
    try {
      const parsed = JSON.parse(recoveryCodes)
      return Array.isArray(parsed) ? parsed : [recoveryCodes]
    } catch {
      return recoveryCodes.split('\n').filter(Boolean)
    }
  }

  const codes = parseRecoveryCodes()

  // ─── 2FA enabled: show status ─────────────────────────────────────────────

  if (twoFactorEnabled) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Shield className="h-5 w-5" />
            Two-Factor Authentication
          </CardTitle>
          <CardDescription>
            An extra layer of security for your account
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex items-center gap-2">
            <Badge variant="default" className="bg-green-600">
              <CheckCircle className="mr-1 h-3 w-3" />
              Enabled
            </Badge>
          </div>

          {step === 'enabled' && codes.length > 0 && (
            <div className="rounded border bg-yellow-50 p-3 dark:bg-yellow-950/20">
              <p className="mb-2 font-medium text-sm">
                Recovery Codes — save these now
              </p>
              <div className="grid grid-cols-2 gap-1 font-mono text-sm">
                {codes.map((code, i) => (
                  <div key={i} className="truncate">{code}</div>
                ))}
              </div>
              <Button
                variant="outline"
                size="sm"
                className="mt-2"
                onClick={handleDownloadCodes}
              >
                <Download className="mr-1 h-3.5 w-3.5" />
                Download
              </Button>
            </div>
          )}

          <div className="flex gap-2">
            <Button
              variant="outline"
              size="sm"
              onClick={handleViewRecoveryCodes}
              disabled={recoveryCodesQuery.isFetching}
            >
              {recoveryCodesQuery.isFetching ? (
                <Loader2 className="mr-1 h-3.5 w-3.5 animate-spin" />
              ) : (
                <Eye className="mr-1 h-3.5 w-3.5" />
              )}
              View Recovery Codes
            </Button>
            <Button
              variant="outline"
              size="sm"
              className="text-destructive"
              onClick={() => setStep('disabling')}
            >
              Disable 2FA
            </Button>
          </div>

          {step === 'disabling' && (
            <div className="space-y-3 rounded border p-3">
              <p className="text-sm">
                Enter your password to disable 2FA.
              </p>
              <Input
                type="password"
                placeholder="Password"
                value={disablePassword}
                onChange={(e) => setDisablePassword(e.target.value)}
              />
              <div className="flex gap-2">
                <Button
                  variant="destructive"
                  size="sm"
                  onClick={handleDisable}
                  disabled={disableMutation.isPending}
                >
                  {disableMutation.isPending ? 'Disabling...' : 'Confirm Disable'}
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => {
                    setStep('idle')
                    setDisablePassword('')
                  }}
                >
                  Cancel
                </Button>
              </div>
            </div>
          )}
        </CardContent>
      </Card>
    )
  }

  // ─── 2FA enabling: show QR + TOTP input ────────────────────────────────────

  if (step === 'enabling' && qrData) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Smartphone className="h-5 w-5" />
            Set Up 2FA
          </CardTitle>
          <CardDescription>
            Scan the QR code with your authenticator app
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex flex-col items-center gap-3">
            <QRCodeDisplay uri={qrData.qr_code_uri} />
            <div className="w-full space-y-1">
              <p className="text-muted-foreground text-xs">Or enter this secret manually:</p>
              <code className="block rounded bg-muted p-2 font-mono text-xs break-all">
                {qrData.secret}
              </code>
            </div>
          </div>
          <div className="space-y-2">
            <Label htmlFor="totp-code">Enter the 6-digit code from your app</Label>
            <Input
              id="totp-code"
              value={totpCode}
              onChange={(e) => setTotpCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
              placeholder="123456"
              className="text-center font-mono text-lg tracking-widest"
              autoFocus
            />
          </div>
          <div className="flex justify-end gap-2">
            <Button
              variant="outline"
              size="sm"
              onClick={() => {
                setStep('idle')
                setQrData(null)
                setTotpCode('')
              }}
            >
              Cancel
            </Button>
            <Button
              size="sm"
              onClick={handleConfirm}
              disabled={confirmMutation.isPending || totpCode.length !== 6}
            >
              {confirmMutation.isPending ? (
                <Loader2 className="mr-1 h-3.5 w-3.5 animate-spin" />
              ) : null}
              Confirm
            </Button>
          </div>
        </CardContent>
      </Card>
    )
  }

  // ─── 2FA not enabled: show enable button ───────────────────────────────────

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Shield className="h-5 w-5" />
          Two-Factor Authentication
        </CardTitle>
        <CardDescription>
          Add an extra layer of security to your account
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="flex items-center gap-2">
          <Badge variant="outline">Not Enabled</Badge>
        </div>
        <p className="text-muted-foreground text-sm">
          Enable 2FA to require a one-time code from your authenticator app
          in addition to your password.
        </p>
        <Button size="sm" onClick={handleEnable} disabled={enableMutation.isPending}>
          {enableMutation.isPending ? (
            <Loader2 className="mr-1 h-3.5 w-3.5 animate-spin" />
          ) : (
            <Shield className="mr-1 h-3.5 w-3.5" />
          )}
          Enable 2FA
        </Button>
      </CardContent>
    </Card>
  )
}

// ─── QR code display ────────────────────────────────────────────────────────

function QRCodeDisplay({ uri }: { uri: string }) {
  // Use a QR code API to render the URI as an image.
  // The URI is an otpauth:// URI that authenticator apps can scan.
  const encoded = encodeURIComponent(uri)
  return (
    <div className="rounded border bg-white p-3">
      <img
        src={`https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encoded}`}
        alt="2FA QR Code"
        width={200}
        height={200}
        className="rounded"
      />
    </div>
  )
}

// ─── Devices link ───────────────────────────────────────────────────────────

function DevicesLink() {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Monitor className="h-5 w-5" />
          Devices
        </CardTitle>
        <CardDescription>Manage your active devices and sessions</CardDescription>
      </CardHeader>
      <CardContent>
        <Button variant="outline" size="sm" asChild>
          <Link to="/settings/devices">
            <Monitor className="mr-1 h-3.5 w-3.5" />
            Manage Devices
          </Link>
        </Button>
      </CardContent>
    </Card>
  )
}
