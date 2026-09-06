import { useState } from 'react'
import { useParams } from 'react-router-dom'
import {
  Lock,
  Mail,
  KeyRound,
  Clock,
  Eye,
  AlertCircle,
  CheckCircle2,
  Loader2,
  Shield,
} from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { CopyButton } from '@/components/shared/CopyButton'
import {
  usePublicLinkInfo,
  useVerifyPublicLink,
  useSendEmailVerification,
  useConfirmEmailVerification,
  usePublicLinkResource,
} from '@/features/secure-links/hooks/use-secure-links'

export function PublicLinkPage() {
  const { token } = useParams<{ token: string }>()
  const uuid = token ?? ''

  const { data: linkInfo, isLoading: infoLoading, isError: infoError } = usePublicLinkInfo(uuid)

  const [accessToken, setAccessToken] = useState<string | null>(null)
  const [password, setPassword] = useState('')
  const [otpCode, setOtpCode] = useState('')
  const [email, setEmail] = useState('')
  const [emailCode, setEmailCode] = useState('')
  const [emailSent, setEmailSent] = useState(false)

  const verifyMutation = useVerifyPublicLink(uuid)
  const sendEmailMutation = useSendEmailVerification(uuid)
  const confirmEmailMutation = useConfirmEmailVerification(uuid)

  const resourceQuery = usePublicLinkResource(uuid, accessToken)

  const handleVerify = () => {
    verifyMutation.mutate(
      {
        password: password || null,
        otp_code: otpCode || null,
      },
      {
        onSuccess: (res) => {
          setAccessToken(res.data.access_token)
          toast.success('Access verified.')
        },
        onError: () => toast.error('Invalid password or OTP code.'),
      },
    )
  }

  const handleSendEmail = () => {
    if (!email) {
      toast.error('Enter your email address.')
      return
    }
    sendEmailMutation.mutate(
      { email },
      {
        onSuccess: () => {
          setEmailSent(true)
          toast.success('Verification code sent to your email.')
        },
        onError: () => toast.error('Failed to send verification code.'),
      },
    )
  }

  const handleConfirmEmail = () => {
    confirmEmailMutation.mutate(
      { code: emailCode },
      {
        onSuccess: (res) => {
          setAccessToken(res.data.access_token)
          toast.success('Email verified.')
        },
        onError: () => toast.error('Invalid verification code.'),
      },
    )
  }

  // Loading state
  if (infoLoading) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-muted/30">
        <div className="flex flex-col items-center gap-3">
          <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
          <p className="text-muted-foreground text-sm">Loading secure link...</p>
        </div>
      </div>
    )
  }

  // Error / expired / revoked
  if (infoError || !linkInfo) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-muted/30 p-4">
        <Card className="max-w-md">
          <CardContent className="flex flex-col items-center gap-3 py-8 text-center">
            <AlertCircle className="h-12 w-12 text-destructive" />
            <h1 className="text-xl font-bold">Link Unavailable</h1>
            <p className="text-muted-foreground text-sm">
              This link is invalid, expired, or has been revoked.
            </p>
          </CardContent>
        </Card>
      </div>
    )
  }

  if (linkInfo.is_expired || linkInfo.is_revoked) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-muted/30 p-4">
        <Card className="max-w-md">
          <CardContent className="flex flex-col items-center gap-3 py-8 text-center">
            <Clock className="h-12 w-12 text-muted-foreground" />
            <h1 className="text-xl font-bold">
              {linkInfo.is_revoked ? 'Link Revoked' : 'Link Expired'}
            </h1>
            <p className="text-muted-foreground text-sm">
              {linkInfo.is_revoked
                ? 'The owner has revoked this link.'
                : 'This link has reached its time or view limit.'}
            </p>
          </CardContent>
        </Card>
      </div>
    )
  }

  const resourceName = String(linkInfo.resource_name ?? 'Shared Resource')

  // If we have access token, show resource
  if (accessToken && resourceQuery.data) {
    const resource = resourceQuery.data
    return (
      <div className="min-h-screen bg-muted/30 p-4">
        <div className="mx-auto max-w-2xl space-y-4">
          <Card>
            <CardHeader>
              <div className="flex items-center gap-2">
                <Shield className="h-5 w-5 text-primary" />
                <CardTitle>{String(resource.name ?? resourceName)}</CardTitle>
                <Badge variant="outline" className="ml-auto">
                  {resource.permission}
                </Badge>
              </div>
            </CardHeader>
            <CardContent className="space-y-4">
              {resource.content && (
                <div className="space-y-2">
                  <Label>Content</Label>
                  <div className="flex items-start gap-2">
                    <pre className="min-w-0 flex-1 overflow-x-auto whitespace-pre-wrap rounded bg-muted p-3 text-sm">
                      {resource.content}
                    </pre>
                    <CopyButton value={resource.content} />
                  </div>
                </div>
              )}
              {resource.download_enabled && (
                <Button variant="outline" className="w-full">
                  Download
                </Button>
              )}
            </CardContent>
          </Card>

          <p className="text-center text-muted-foreground text-xs">
            This resource was shared via Keyora secure link. Your access has been logged.
          </p>
        </div>
      </div>
    )
  }

  // Verification required
  const needsPassword = linkInfo.requires_password
  const needsOtp = linkInfo.requires_otp
  const needsEmail = linkInfo.requires_email_verification
  const needsVerification = needsPassword || needsOtp || needsEmail

  return (
    <div className="flex min-h-screen items-center justify-center bg-muted/30 p-4">
      <Card className="w-full max-w-md">
        <CardHeader>
          <div className="flex items-center gap-2">
            <Lock className="h-5 w-5 text-primary" />
            <CardTitle>Secure Link</CardTitle>
          </div>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="rounded bg-muted p-3">
            <p className="text-sm">
              <span className="text-muted-foreground">Resource:</span>{' '}
              <span className="font-medium">{resourceName}</span>
            </p>
            {linkInfo.expires_at && (
              <p className="mt-1 flex items-center gap-1 text-muted-foreground text-xs">
                <Clock className="h-3 w-3" />
                Expires {formatExpiry(linkInfo.expires_at)}
              </p>
            )}
            {linkInfo.views_remaining !== null && (
              <p className="mt-1 flex items-center gap-1 text-muted-foreground text-xs">
                <Eye className="h-3 w-3" />
                {linkInfo.views_remaining} view{linkInfo.views_remaining !== 1 ? 's' : ''} remaining
              </p>
            )}
          </div>

          {!needsVerification ? (
            <div className="space-y-3">
              <p className="text-muted-foreground text-sm">
                Click below to access this shared resource.
              </p>
              <Button
                className="w-full"
                onClick={() => setAccessToken('public')}
              >
                <CheckCircle2 className="mr-2 h-4 w-4" />
                Access Resource
              </Button>
            </div>
          ) : (
            <div className="space-y-4">
              {/* Password */}
              {needsPassword && (
                <div className="space-y-2">
                  <Label className="flex items-center gap-1.5">
                    <KeyRound className="h-3.5 w-3.5" />
                    Password
                  </Label>
                  <Input
                    type="password"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    placeholder="Enter password"
                  />
                </div>
              )}

              {/* OTP */}
              {needsOtp && (
                <div className="space-y-2">
                  <Label className="flex items-center gap-1.5">
                    <KeyRound className="h-3.5 w-3.5" />
                    OTP Code
                  </Label>
                  <Input
                    value={otpCode}
                    onChange={(e) => setOtpCode(e.target.value)}
                    placeholder="Enter OTP code"
                  />
                </div>
              )}

              {/* Email verification */}
              {needsEmail && !emailSent && (
                <div className="space-y-2">
                  <Label className="flex items-center gap-1.5">
                    <Mail className="h-3.5 w-3.5" />
                    Email Address
                  </Label>
                  <div className="flex gap-2">
                    <Input
                      type="email"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      placeholder="your@email.com"
                    />
                    <Button
                      variant="outline"
                      onClick={handleSendEmail}
                      disabled={sendEmailMutation.isPending}
                    >
                      Send
                    </Button>
                  </div>
                </div>
              )}

              {needsEmail && emailSent && (
                <div className="space-y-2">
                  <Label className="flex items-center gap-1.5">
                    <Mail className="h-3.5 w-3.5" />
                    Verification Code
                  </Label>
                  <div className="flex gap-2">
                    <Input
                      value={emailCode}
                      onChange={(e) => setEmailCode(e.target.value)}
                      placeholder="Enter code from email"
                    />
                    <Button
                      variant="outline"
                      onClick={handleConfirmEmail}
                      disabled={confirmEmailMutation.isPending}
                    >
                      Verify
                    </Button>
                  </div>
                </div>
              )}

              {/* Submit (for password/OTP only — email has its own buttons) */}
              {(needsPassword || needsOtp) && !needsEmail && (
                <Button
                  className="w-full"
                  onClick={handleVerify}
                  disabled={verifyMutation.isPending}
                >
                  {verifyMutation.isPending ? 'Verifying...' : 'Verify & Access'}
                </Button>
              )}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}

function formatExpiry(expiresAt: string): string {
  const date = new Date(expiresAt)
  const now = new Date()
  const diffMs = date.getTime() - now.getTime()
  if (diffMs <= 0) return 'soon'
  const diffH = Math.floor(diffMs / (1000 * 60 * 60))
  if (diffH < 1) return 'in < 1 hour'
  if (diffH < 24) return `in ${diffH} hours`
  const diffD = Math.floor(diffH / 24)
  return `in ${diffD} days`
}
