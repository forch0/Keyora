<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateSecureLinkAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SecureLinks\CreateSecureLinkRequest;
use App\Http\Resources\V1\SecureLinkResource;
use App\Models\SecureFile;
use App\Models\SecureLink;
use App\Models\SecureNote;
use App\Models\User;
use App\Models\VaultItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SecureLinkController extends Controller
{
    public function __construct(
        private readonly CreateSecureLinkAction $createLink,
    ) {}

    public function storeForVaultItem(CreateSecureLinkRequest $request, VaultItem $item): JsonResponse
    {
        return $this->store($request, $item);
    }

    public function storeForFile(CreateSecureLinkRequest $request, SecureFile $file): JsonResponse
    {
        return $this->store($request, $file);
    }

    public function storeForNote(CreateSecureLinkRequest $request, SecureNote $note): JsonResponse
    {
        return $this->store($request, $note);
    }

    public function indexForVaultItem(Request $request, VaultItem $item): AnonymousResourceCollection
    {
        return $this->index($request, $item);
    }

    public function indexForFile(Request $request, SecureFile $file): AnonymousResourceCollection
    {
        return $this->index($request, $file);
    }

    public function indexForNote(Request $request, SecureNote $note): AnonymousResourceCollection
    {
        return $this->index($request, $note);
    }

    public function destroy(Request $request, SecureLink $link): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $this->authorize('delete', $link);

        $link->update([
            'revoked_at' => now(),
            'revoked_by' => $user->id,
            'revoke_reason' => 'manual',
        ]);

        return response()->json(null, 204);
    }

    private function store(CreateSecureLinkRequest $request, Model $resource): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $result = ($this->createLink)($user, $resource, $request->validated());

        $resourceObj = new SecureLinkResource($result['link']);
        if ($result['otp_code'] !== null) {
            $resourceObj = $resourceObj->withOtp($result['otp_code']);
        }

        return $resourceObj->response()->setStatusCode(201);
    }

    private function index(Request $request, Model $resource): AnonymousResourceCollection
    {
        $this->authenticatedUser($request);

        $links = SecureLink::query()
            ->where('resource_type', $resource::class)
            ->where('resource_id', $resource->getKey())
            ->latest()
            ->get();

        return SecureLinkResource::collection($links);
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
