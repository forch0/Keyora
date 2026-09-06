<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\SecureLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SecureLink */
class SecureLinkResource extends JsonResource
{
    private bool $withOtp = false;

    private ?string $otpCode = null;

    public function withOtp(string $otpCode): self
    {
        $this->withOtp = true;
        $this->otpCode = $otpCode;

        return $this;
    }

    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $morphResource = $this->resource()->first();

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'url' => $this->url(),
            'resource' => [
                'type' => $this->resource_type,
                'id' => $this->resource_id,
                'name' => $morphResource?->getAttribute('title')
                    ?? $morphResource?->getAttribute('name')
                    ?? 'Resource #'.$this->resource_id,
            ],
            'recipient_email' => $this->recipient_email,
            'permission' => $this->permission,
            'download_enabled' => $this->download_enabled,
            'expires_at' => $this->getAttribute('expires_at')?->toIso8601String(),
            'first_view_expires_hours' => $this->first_view_expires_hours,
            'max_views' => $this->max_views,
            'views_count' => $this->views_count,
            'is_one_time' => $this->is_one_time,
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'first_viewed_at' => $this->getAttribute('first_viewed_at')?->toIso8601String(),
            'revoked_at' => $this->getAttribute('revoked_at')?->toIso8601String(),
            'revoke_reason' => $this->revoke_reason,
            'has_password' => $this->hasPassword(),
            'requires_otp' => $this->requiresOtp(),
            'requires_email_verification' => $this->requiresEmailVerification(),
            'otp_code' => $this->when($this->withOtp, fn () => $this->otpCode),
            'created_at' => $this->getAttribute('created_at')?->toIso8601String(),
        ];
    }
}
