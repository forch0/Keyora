<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vault\CreateTagRequest;
use App\Http\Requests\Vault\UpdateTagRequest;
use App\Http\Resources\V1\PersonalVaultTagResource;
use App\Models\PersonalVaultTag;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Personal Vault Tags')]
class PersonalVaultTagController extends Controller
{
    /**
     * List all tags for the authenticated user.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);

        $tags = PersonalVaultTag::where('user_id', $user->id)
            ->orderBy('name')
            ->get();

        return PersonalVaultTagResource::collection($tags);
    }

    /**
     * Create a new tag.
     */
    public function store(CreateTagRequest $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $tag = PersonalVaultTag::create([
            'user_id' => $user->id,
            'name' => $request->validated('name'),
            'color' => $request->validated('color'),
        ]);

        return (new PersonalVaultTagResource($tag))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a single tag.
     */
    public function show(Request $request, PersonalVaultTag $tag): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if ($tag->user_id !== $user->id) {
            abort(404);
        }

        return (new PersonalVaultTagResource($tag))->response();
    }

    /**
     * Update a tag.
     */
    public function update(UpdateTagRequest $request, PersonalVaultTag $tag): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if ($tag->user_id !== $user->id) {
            abort(404);
        }

        $tag->update($request->validated());

        return (new PersonalVaultTagResource($tag->fresh()))->response();
    }

    /**
     * Delete a tag. Removes from all items via pivot cascade.
     */
    public function destroy(Request $request, PersonalVaultTag $tag): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if ($tag->user_id !== $user->id) {
            abort(404);
        }

        $tag->delete();

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
