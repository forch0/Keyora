<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\SecureLink;
use App\Models\SecureLinkAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class AccessSharedLinkAction
{
    /**
     * Access the shared resource — validates expiration, increments views,
     * logs access, auto-revokes if view limit reached.
     *
     * @param  array<string, mixed>  $context  Request context (ip, user_agent, email)
     * @return array{link: SecureLink, resource: Model}
     */
    public function __invoke(SecureLink $link, array $context): array
    {
        // Check revoked
        if ($link->isRevoked()) {
            abort(410, 'This link has been revoked.');
        }

        // Check expired
        if ($link->isExpired()) {
            abort(410, 'This link has expired.');
        }

        // Check view limit
        if ($link->max_views !== null && $link->views_count >= $link->max_views) {
            abort(410, 'This link has reached its view limit.');
        }

        // Check one-time link
        if ($link->is_one_time && $link->views_count >= 1) {
            abort(410, 'This one-time link has already been used.');
        }

        $now = Carbon::now();

        // Set first viewed at if not set
        if ($link->first_viewed_at === null) {
            $link->update(['first_viewed_at' => $now]);
        }

        // Increment views count
        $newViewsCount = $link->views_count + 1;
        $link->update(['views_count' => $newViewsCount]);

        // Log access
        SecureLinkAccess::create([
            'secure_link_id' => $link->id,
            'ip_address' => $context['ip_address'] ?? '0.0.0.0',
            'user_agent' => $context['user_agent'] ?? '',
            'email' => $context['email'] ?? $link->recipient_email,
            'accessed_at' => $now,
        ]);

        // Auto-revoke if view limit reached
        if ($link->max_views !== null && $newViewsCount >= $link->max_views) {
            $link->update([
                'revoked_at' => $now,
                'revoke_reason' => 'view_limit_reached',
            ]);
        }

        // Auto-revoke one-time links after first view
        if ($link->is_one_time && $newViewsCount >= 1) {
            $link->update([
                'revoked_at' => $now,
                'revoke_reason' => 'view_limit_reached',
            ]);
        }

        $link->refresh();

        $resource = $link->resource;

        if ($resource === null) {
            abort(404, 'Resource not found.');
        }

        return ['link' => $link, 'resource' => $resource];
    }
}
