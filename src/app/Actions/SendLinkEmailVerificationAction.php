<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\SecureLink;
use App\Notifications\SecureLinkEmailVerificationNotification;
use Illuminate\Support\Facades\Notification;

class SendLinkEmailVerificationAction
{
    /**
     * Send a 6-digit verification code to the provided email.
     */
    public function __invoke(SecureLink $link, string $email): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store the hashed code on the link (reuse otp_code_hash field for email verification)
        $link->update([
            'otp_code_hash' => bcrypt($code),
            'otp_sent_at' => now(),
            'recipient_email' => $email,
        ]);

        $resource = $link->resource;
        $resourceName = $resource?->getAttribute('title')
            ?? $resource?->getAttribute('name')
            ?? 'Resource #'.$link->resource_id;

        Notification::route('mail', $email)
            ->notify(new SecureLinkEmailVerificationNotification($code, $link->url(), $resourceName));

        return $code;
    }
}
