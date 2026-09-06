<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Permission;
use App\Events\AccessRequested;
use App\Models\AccessRequest;
use App\Models\User;
use App\Services\AccessResolver;
use App\Services\TenantManager;
use Illuminate\Database\Eloquent\Model;

class CreateAccessRequestAction
{
    public function __construct(
        private readonly AccessResolver $accessResolver,
        private readonly TenantManager $tenantManager,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(User $requester, Model $resource, array $data): AccessRequest
    {
        $tenantId = $this->tenantManager->currentTenantId();

        // Check if requester already has access
        $requestedPermission = Permission::from($data['requested_permission']);
        if ($this->accessResolver->can($requester, $requestedPermission, $resource)) {
            abort(422, 'You already have access to this resource.');
        }

        // Check for existing pending request
        $existing = AccessRequest::where('tenant_id', $tenantId)
            ->where('requester_id', $requester->id)
            ->where('resource_type', $resource::class)
            ->where('resource_id', $resource->getKey())
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            abort(422, 'A pending access request for this resource already exists.');
        }

        // Resolve resource owner
        $ownerId = (int) $resource->getAttribute('user_id');

        $request = AccessRequest::create([
            'tenant_id' => $tenantId,
            'requester_id' => $requester->id,
            'resource_type' => $resource::class,
            'resource_id' => $resource->getKey(),
            'resource_owner_id' => $ownerId,
            'requested_permission' => $data['requested_permission'],
            'requested_duration' => $data['requested_duration'] ?? null,
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        AccessRequested::dispatch($request);

        return $request;
    }
}
