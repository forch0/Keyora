<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SecurityAlertResource;
use App\Models\SecurityAlert;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

#[Group('Security Alerts')]
class SecurityAlertController extends Controller
{
    /**
     * List user's security alerts (unread first, then by created_at desc).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        $alerts = SecurityAlert::forUser($user)
            ->orderByRaw('CASE WHEN read_at IS NULL THEN 0 ELSE 1 END')
            ->latest()
            ->paginate(50);

        return SecurityAlertResource::collection($alerts);
    }

    /**
     * Count of unread alerts.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $count = SecurityAlert::forUser($user)->unread()->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Mark a single alert as read.
     */
    public function markRead(Request $request, SecurityAlert $alert): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if ($alert->user_id !== $user->id) {
            abort(403);
        }

        if ($alert->read_at === null) {
            $alert->update(['read_at' => Carbon::now()]);
        }

        return response()->json(null, 204);
    }

    /**
     * Dismiss an alert.
     */
    public function dismiss(Request $request, SecurityAlert $alert): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if ($alert->user_id !== $user->id) {
            abort(403);
        }

        $alert->update(['dismissed_at' => Carbon::now()]);

        return response()->json(null, 204);
    }

    /**
     * Mark all unread alerts as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        SecurityAlert::forUser($user)->unread()->update(['read_at' => Carbon::now()]);

        return response()->json(null, 204);
    }
}
