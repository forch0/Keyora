<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Permission;
use App\Events\SecureLinkCreated;
use App\Models\SecureLink;
use App\Models\User;
use App\Notifications\SecureLinkCreatedNotification;
use App\Notifications\SecureLinkOtpNotification;
use App\Services\AccessResolver;
use App\Services\TenantManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class CreateSecureLinkAction
{
    public function __construct(
        private readonly AccessResolver $accessResolver,
        private readonly TenantManager $tenantManager,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{link: SecureLink, otp_code: string|null}
     */
    public function __invoke(User $createdBy, Model $resource, array $data): array
    {
        // Verify creator has share permission
        if (! $this->accessResolver->can($createdBy, Permission::Share, $resource)) {
            abort(403, 'You do not have permission to share this resource.');
        }

        $tenantId = $this->tenantManager->currentTenantId();

        // Handle one-time link
        $maxViews = $data['max_views'] ?? null;
        $isOneTime = $data['is_one_time'] ?? false;
        if ($isOneTime) {
            $maxViews = 1;
        }

        // Hash password if provided
        $passwordHash = null;
        if (! empty($data['password'])) {
            $passwordHash = bcrypt($data['password']);
        }

        // Generate OTP if required
        $otpCode = null;
        $otpCodeHash = null;
        $otpSentAt = null;
        if (! empty($data['require_otp']) && ! empty($data['recipient_email'])) {
            $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otpCodeHash = bcrypt($otpCode);
            $otpSentAt = Carbon::now();
        }

        $link = SecureLink::create([
            'tenant_id' => $tenantId,
            'uuid' => Str::uuid()->toString(),
            'resource_type' => $resource::class,
            'resource_id' => $resource->getKey(),
            'created_by' => $createdBy->id,
            'recipient_email' => $data['recipient_email'] ?? null,
            'password_hash' => $passwordHash,
            'otp_code_hash' => $otpCodeHash,
            'otp_sent_at' => $otpSentAt,
            'email_verified_at' => null,
            'permission' => $data['permission'] ?? 'view',
            'download_enabled' => $data['download_enabled'] ?? true,
            'expires_at' => $data['expires_at'] ?? null,
            'first_view_expires_hours' => $data['first_view_expires_hours'] ?? null,
            'max_views' => $maxViews,
            'views_count' => 0,
            'first_viewed_at' => null,
            'is_one_time' => $isOneTime,
            'revoked_at' => null,
            'revoked_by' => null,
            'revoke_reason' => null,
        ]);

        // Send OTP email to recipient
        $recipientEmail = $data['recipient_email'] ?? null;
        if ($otpCode !== null && $recipientEmail !== null && $recipientEmail !== '') {
            $resourceName = $resource->getAttribute('title')
                ?? $resource->getAttribute('name')
                ?? 'Resource #'.$resource->getKey();

            Notification::route('mail', $recipientEmail)
                ->notify(new SecureLinkOtpNotification($otpCode, $link->url(), $createdBy->name, $resourceName));
        }

        // Send confirmation to creator
        $resourceName = $resource->getAttribute('title')
            ?? $resource->getAttribute('name')
            ?? 'Resource #'.$resource->getKey();
        $createdBy->notify(new SecureLinkCreatedNotification($link, $resourceName));

        SecureLinkCreated::dispatch($link, $createdBy);

        return ['link' => $link, 'otp_code' => $otpCode];
    }
}
