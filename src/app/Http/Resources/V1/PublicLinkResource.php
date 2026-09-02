<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\SecureLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SecureLink */
class PublicLinkResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $morphResource = $this->resource()->first();

        $viewsRemaining = null;
        if ($this->max_views !== null) {
            $viewsRemaining = max(0, $this->max_views - $this->views_count);
        }

        return [
            'uuid' => $this->uuid,
            'resource_type' => $this->resource_type,
            'resource_name' => $morphResource?->getAttribute('title')
                ?? $morphResource?->getAttribute('name')
                ?? 'Resource #'.$this->resource_id,
            'requires_password' => $this->hasPassword(),
            'requires_otp' => $this->requiresOtp(),
            'requires_email_verification' => $this->requiresEmailVerification(),
            'is_expired' => $this->isExpired(),
            'is_revoked' => $this->isRevoked(),
            'views_remaining' => $viewsRemaining,
            'expires_at' => $this->getAttribute('expires_at')?->toIso8601String(),
        ];
    }
}
