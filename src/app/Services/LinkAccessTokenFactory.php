<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SecureLink;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class LinkAccessTokenFactory
{
    private const TOKEN_TTL_MINUTES = 15;

    /**
     * Generate a temporary signed access token for a secure link.
     */
    public function __invoke(SecureLink $link): string
    {
        return URL::temporarySignedRoute(
            'api.v1.public-link.resource',
            Carbon::now()->addMinutes(self::TOKEN_TTL_MINUTES),
            ['uuid' => $link->uuid],
        );
    }
}
