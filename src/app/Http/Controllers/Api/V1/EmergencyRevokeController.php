<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\EmergencyRevokeAction;
use App\Actions\RevokeAllAccessAction;
use App\Actions\RevokeTeamAccessAction;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\AccessRequests\EmergencyRevokeRequest;
use App\Models\SecureFile;
use App\Models\SecureNote;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VaultItem;
use App\Services\AccessResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmergencyRevokeController extends Controller
{
    public function __construct(
        private readonly EmergencyRevokeAction $emergencyRevoke,
        private readonly RevokeAllAccessAction $revokeAll,
        private readonly RevokeTeamAccessAction $revokeTeam,
        private readonly AccessResolver $accessResolver,
    ) {}

    /**
     * Emergency revoke ALL access for a user (admin/owner only).
     */
    public function revokeAllForUser(EmergencyRevokeRequest $request, Tenant $tenant, User $user): JsonResponse
    {
        $admin = $this->authenticatedUser($request);
        $this->ensureAdminOfTenant($admin, $tenant);

        $reason = $request->validated('reason') !== null
            ? (string) $request->validated('reason')
            : 'emergency';

        $count = ($this->emergencyRevoke)($admin, $user, $reason);

        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'revoked_count' => $count,
                'reason' => $reason,
            ],
        ]);
    }

    /**
     * Revoke all access for a vault item (owner or manage permission).
     */
    public function revokeAllForResource(Request $request, VaultItem $item): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $this->ensureCanManage($user, $item);

        $count = ($this->revokeAll)($user, $item, $this->resolveReason($request));

        return $this->resourceResponse(VaultItem::class, $item->id, $count, $this->resolveReason($request));
    }

    /**
     * Revoke all access for a secure file.
     */
    public function revokeAllForFile(Request $request, SecureFile $file): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $this->ensureCanManage($user, $file);

        $reason = $this->resolveReason($request);
        $count = ($this->revokeAll)($user, $file, $reason);

        return $this->resourceResponse(SecureFile::class, $file->id, $count, $reason);
    }

    /**
     * Revoke all access for a secure note.
     */
    public function revokeAllForNote(Request $request, SecureNote $note): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $this->ensureCanManage($user, $note);

        $reason = $this->resolveReason($request);
        $count = ($this->revokeAll)($user, $note, $reason);

        return $this->resourceResponse(SecureNote::class, $note->id, $count, $reason);
    }

    /**
     * Revoke team access for a vault item.
     */
    public function revokeTeamAccess(Request $request, VaultItem $item, Team $team): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $this->ensureCanManage($user, $item);

        $reason = $this->resolveReason($request);
        $count = ($this->revokeTeam)($user, $item, $team, $reason);

        return response()->json([
            'data' => [
                'resource_type' => VaultItem::class,
                'resource_id' => $item->id,
                'team_id' => $team->id,
                'revoked_count' => $count,
                'reason' => $reason,
            ],
        ]);
    }

    private function resolveReason(Request $request): string
    {
        $reason = $request->input('reason');

        return is_string($reason) ? $reason : 'manual';
    }

    private function resourceResponse(string $type, int $id, int $count, string $reason): JsonResponse
    {
        return response()->json([
            'data' => [
                'resource_type' => $type,
                'resource_id' => $id,
                'revoked_count' => $count,
                'reason' => $reason,
            ],
        ]);
    }

    private function ensureAdminOfTenant(User $user, Tenant $tenant): void
    {
        if (! $user->isAdminOf($tenant)) {
            abort(403, 'Only tenant admins can perform emergency revocation.');
        }
    }

    private function ensureCanManage(User $user, Model $resource): void
    {
        if ($resource->getAttribute('user_id') === $user->id) {
            return;
        }

        if (! $this->accessResolver->can($user, Permission::Manage, $resource)) {
            abort(403, 'You need manage permission to revoke all access for this resource.');
        }
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
