<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Permission;
use App\Events\AccessUpdated;
use App\Models\AccessGrant;
use App\Models\User;
use App\Services\AccessResolver;
use Illuminate\Support\Carbon;

class UpdateAccessAction
{
    public function __construct(
        private readonly AccessResolver $accessResolver,
    ) {}

    /**
     * Update an existing access grant's permission level and constraints.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(User $updatedBy, AccessGrant $grant, array $attributes): AccessGrant
    {
        $resource = $grant->grantable;

        if ($resource === null) {
            abort(404, 'Resource not found.');
        }

        // Verify updater has share or manage permission
        if (! $this->accessResolver->can($updatedBy, Permission::Share, $resource)) {
            abort(403, 'You do not have permission to modify access grants.');
        }

        $updateData = [];

        if (isset($attributes['permission'])) {
            $updateData['permission'] = $attributes['permission'];
        }

        if (array_key_exists('expires_at', $attributes)) {
            $updateData['expires_at'] = $attributes['expires_at'] ? Carbon::parse($attributes['expires_at']) : null;
        }

        if (array_key_exists('max_views', $attributes)) {
            $updateData['max_views'] = $attributes['max_views'];
        }

        $grant->update($updateData);
        $grant->refresh();

        AccessUpdated::dispatch($grant, $updatedBy);

        return $grant;
    }
}
