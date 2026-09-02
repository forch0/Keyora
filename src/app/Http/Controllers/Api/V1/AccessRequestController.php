<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\ApproveAccessRequestAction;
use App\Actions\CreateAccessRequestAction;
use App\Actions\RejectAccessRequestAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\AccessRequests\ApproveAccessRequestRequest;
use App\Http\Requests\AccessRequests\CreateAccessRequestRequest;
use App\Http\Requests\AccessRequests\RejectAccessRequestRequest;
use App\Http\Resources\V1\AccessRequestResource;
use App\Models\AccessRequest;
use App\Models\SecureFile;
use App\Models\SecureNote;
use App\Models\User;
use App\Models\VaultItem;
use App\Notifications\AccessRequestApprovedNotification;
use App\Notifications\AccessRequestReceived;
use App\Notifications\AccessRequestRejectedNotification;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Access Requests')]
class AccessRequestController extends Controller
{
    public function __construct(
        private readonly CreateAccessRequestAction $createRequest,
        private readonly ApproveAccessRequestAction $approveRequest,
        private readonly RejectAccessRequestAction $rejectRequest,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        $query = AccessRequest::query()
            ->forUser($user)
            ->with(['requester', 'resourceOwner', 'reviewer']);

        if ($request->has('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->has('direction')) {
            $direction = $request->string('direction')->toString();
            if ($direction === 'sent') {
                $query->where('requester_id', $user->id);
            } elseif ($direction === 'received') {
                $query->where('resource_owner_id', $user->id);
            }
        }

        $requests = $query->latest()->paginate(15);

        return AccessRequestResource::collection($requests);
    }

    public function store(CreateAccessRequestRequest $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $resource = $this->resolveResource(
            $request->validated('resource_type'),
            (int) $request->validated('resource_id'),
        );

        if ($resource === null) {
            abort(404, 'Resource not found.');
        }

        $accessRequest = ($this->createRequest)($user, $resource, $request->validated());

        // Send notification to resource owner
        $owner = $accessRequest->resourceOwner;
        if ($owner !== null) {
            $resourceName = $this->getResourceName($resource);
            $owner->notify(new AccessRequestReceived($accessRequest, $user->name, $resourceName));
        }

        return (new AccessRequestResource($accessRequest))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, AccessRequest $accessRequest): JsonResponse
    {
        $this->authenticatedUser($request);
        $this->authorize('view', $accessRequest);

        $accessRequest->load(['requester', 'resourceOwner', 'reviewer']);

        return (new AccessRequestResource($accessRequest))->response();
    }

    public function approve(ApproveAccessRequestRequest $request, AccessRequest $accessRequest): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $this->authorize('approve', $accessRequest);

        if ($accessRequest->status !== 'pending') {
            abort(422, 'Only pending requests can be approved.');
        }

        $accessRequest = ($this->approveRequest)($user, $accessRequest, $request->validated());

        // Notify the requester
        $requester = $accessRequest->requester;
        if ($requester !== null) {
            $resourceName = $this->getResourceName($accessRequest->resource);
            $requester->notify(new AccessRequestApprovedNotification($accessRequest, $resourceName));
        }

        return (new AccessRequestResource($accessRequest))->response();
    }

    public function reject(RejectAccessRequestRequest $request, AccessRequest $accessRequest): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $this->authorize('reject', $accessRequest);

        if ($accessRequest->status !== 'pending') {
            abort(422, 'Only pending requests can be rejected.');
        }

        $accessRequest = ($this->rejectRequest)(
            $user,
            $accessRequest,
            $request->validated('review_note') !== null
                ? (string) $request->validated('review_note')
                : null,
        );

        // Notify the requester
        $requester = $accessRequest->requester;
        if ($requester !== null) {
            $resourceName = $this->getResourceName($accessRequest->resource);
            $requester->notify(new AccessRequestRejectedNotification($accessRequest, $resourceName));
        }

        return (new AccessRequestResource($accessRequest))->response();
    }

    public function destroy(Request $request, AccessRequest $accessRequest): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $this->authorize('cancel', $accessRequest);

        $accessRequest->update(['status' => 'cancelled']);

        return response()->json(null, 204);
    }

    public function history(Request $request): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        $query = AccessRequest::query()
            ->forUser($user)
            ->with(['requester', 'resourceOwner', 'reviewer']);

        if ($request->has('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $requests = $query->latest()->paginate(25);

        return AccessRequestResource::collection($requests);
    }

    private function resolveResource(string $type, int $id): ?Model
    {
        return match ($type) {
            VaultItem::class => VaultItem::query()->withoutTenant()->find($id),
            SecureFile::class => SecureFile::query()->find($id),
            SecureNote::class => SecureNote::query()->find($id),
            default => null,
        };
    }

    private function getResourceName(?Model $resource): string
    {
        if ($resource === null) {
            return 'Unknown Resource';
        }

        return $resource->getAttribute('title')
            ?? $resource->getAttribute('name')
            ?? 'Resource #'.$resource->getKey();
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
