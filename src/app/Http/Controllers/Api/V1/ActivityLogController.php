<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\PersonalVaultItem;
use App\Models\SecureFile;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VaultItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityLogController extends Controller
{
    /**
     * Personal activity history (current user's actions).
     */
    public function personalHistory(Request $request): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        $query = ActivityLog::forUser($user->id)->latest();

        $this->applyFilters($request, $query);

        return ActivityLogResource::collection($query->paginate(50));
    }

    /**
     * Company activity feed (all tenant actions) — admin/owner only.
     */
    public function companyFeed(Request $request, Tenant $tenant): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        $this->authorize('viewActivityFeed', [$tenant, $user]);

        $query = ActivityLog::forTenant($tenant->id)->latest();

        $this->applyFilters($request, $query);

        return ActivityLogResource::collection($query->paginate(50));
    }

    /**
     * Resource-specific activity history (vault item or file).
     *
     * @param  int|string  $item
     */
    public function resourceHistory(Request $request, $item): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);
        $itemId = (int) $item;

        // Try PersonalVaultItem first (personal vault route)
        $resource = PersonalVaultItem::find($itemId);

        // Try SecureFile (files route)
        if ($resource === null) {
            $resource = SecureFile::find($itemId);
        }

        // Try VaultItem (org vault)
        if ($resource === null) {
            $resource = VaultItem::find($itemId);
        }

        if ($resource === null) {
            abort(404);
        }

        // Check access
        if ($resource instanceof PersonalVaultItem && $resource->user_id !== $user->id) {
            abort(403);
        }

        $query = ActivityLog::where('subject_type', $resource::class)
            ->where('subject_id', $resource->getKey())
            ->latest();

        $this->applyFilters($request, $query);

        return ActivityLogResource::collection($query->paginate(50));
    }

    /**
     * Employee access overview — admin/owner only.
     */
    public function employeeOverview(Request $request, Tenant $tenant, User $user): AnonymousResourceCollection
    {
        $currentUser = $this->authenticatedUser($request);

        $this->authorize('viewActivityFeed', [$tenant, $currentUser]);

        $query = ActivityLog::forTenant($tenant->id)
            ->forUser($user->id)
            ->latest();

        $this->applyFilters($request, $query);

        return ActivityLogResource::collection($query->paginate(50));
    }

    /**
     * Apply common filters (action, user_id, subject_type, from, to).
     *
     * @param  Builder<ActivityLog>  $query
     */
    private function applyFilters(Request $request, Builder $query): void
    {
        if ($action = $request->string('action')->toString()) {
            $query->action($action);
        }

        if ($userId = $request->integer('user_id')) {
            $query->forUser($userId);
        }

        if ($subjectType = $request->string('subject_type')->toString()) {
            $query->where('subject_type', $subjectType);
        }

        if ($from = $request->string('from')->toString()) {
            $query->where('created_at', '>=', $from);
        }

        if ($to = $request->string('to')->toString()) {
            $query->where('created_at', '<=', $to);
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
