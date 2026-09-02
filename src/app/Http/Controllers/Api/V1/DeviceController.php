<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DeviceResource;
use App\Models\User;
use App\Models\UserDevice;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Device Management')]
class DeviceController extends Controller
{
    /**
     * List user's known devices.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        $devices = UserDevice::where('user_id', $user->id)
            ->latest('last_seen_at')
            ->get();

        return DeviceResource::collection($devices);
    }

    /**
     * Revoke a device (delete it — forces re-detection on next login).
     */
    public function destroy(Request $request, UserDevice $device): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if ($device->user_id !== $user->id) {
            abort(403);
        }

        $device->delete();

        return response()->json(null, 204);
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
