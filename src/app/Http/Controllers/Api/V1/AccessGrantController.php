<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AccessGrantResource;
use App\Models\User;
use App\Models\VaultItem;
use App\Services\AccessResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AccessGrantController extends Controller
{
    public function __construct(
        private readonly AccessResolver $accessResolver,
    ) {}

    /**
     * List all active access grants for a vault item.
     */
    public function index(Request $request, VaultItem $item): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        // Only the owner or someone with 'share' permission can view grants
        if (! $this->accessResolver->can($user, Permission::Share, $item)) {
            // Fall back to owner check
            if ($item->user_id !== $user->id && ! $user->isAdminOf($item->tenant)) {
                abort(403);
            }
        }

        $grants = $this->accessResolver->whoHasAccess($item);

        return AccessGrantResource::collection($grants);
    }

    /**
     * Summary of who has access, grouped by subject type.
     */
    public function summary(Request $request, VaultItem $item): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if (! $this->accessResolver->can($user, Permission::Share, $item)) {
            if ($item->user_id !== $user->id && ! $user->isAdminOf($item->tenant)) {
                abort(403);
            }
        }

        $grants = $this->accessResolver->whoHasAccess($item);

        $grouped = $grants->groupBy('subject_type')->map(fn ($group) => [
            'count' => $group->count(),
            'grants' => AccessGrantResource::collection($group),
        ]);

        return response()->json([
            'total' => $grants->count(),
            'groups' => $grouped,
        ]);
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Unauthenticated.');
        }

        return $user;
    }
}
