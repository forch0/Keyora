<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Permission;
use App\Events\AccessRevoked;
use App\Models\AccessGrant;
use App\Models\User;
use App\Services\AccessResolver;

class RevokeAccessAction
{
    public function __construct(
        private readonly AccessResolver $accessResolver,
    ) {}

    /**
     * Revoke an access grant (soft delete — sets revoked_at, not hard delete).
     */
    public function __invoke(User $revokedBy, AccessGrant $grant, ?string $reason = null): AccessGrant
    {
        $resource = $grant->grantable;

        if ($resource === null) {
            abort(404, 'Resource not found.');
        }

        // Verify revoker has share or manage permission
        if (! $this->accessResolver->can($revokedBy, Permission::Share, $resource)) {
            abort(403, 'You do not have permission to revoke access.');
        }

        $grant->update([
            'revoked_at' => now(),
            'revoked_by' => $revokedBy->id,
            'revoke_reason' => $reason,
        ]);
        $grant->refresh();

        AccessRevoked::dispatch($grant, $revokedBy, $reason);

        return $grant;
    }
}
