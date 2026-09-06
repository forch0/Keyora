<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateNoteAction;
use App\Actions\EmptyTrashAction;
use App\Actions\ForceDeleteModelAction;
use App\Actions\ListTrashAction;
use App\Actions\RestoreModelAction;
use App\Actions\UpdateNoteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notes\CreateNoteRequest;
use App\Http\Requests\Notes\UpdateNoteRequest;
use App\Http\Resources\V1\SecureNoteResource;
use App\Models\SecureNote;
use App\Models\User;
use App\Services\AccessResolver;
use App\Services\TenantManager;
use App\Services\ViewTracker;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Secure Notes')]
class SecureNoteController extends Controller
{
    public function __construct(
        private readonly CreateNoteAction $createNote,
        private readonly UpdateNoteAction $updateNote,
        private readonly AccessResolver $accessResolver,
        private readonly TenantManager $tenantManager,
        private readonly ViewTracker $viewTracker,
        private readonly ListTrashAction $listTrash,
        private readonly RestoreModelAction $restoreModel,
        private readonly ForceDeleteModelAction $forceDeleteModel,
        private readonly EmptyTrashAction $emptyTrash,
    ) {}

    /**
     * List notes: personal notes + shared notes + team notes.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);
        $tenantId = $this->tenantManager->currentTenantId();

        $query = SecureNote::query()->with(['tags']);

        // Personal notes (user_id = current user, tenant_id = null)
        // OR team notes where user is a team member
        // OR notes shared with the user via access grants
        $query->where(function (Builder $q) use ($user, $tenantId): void {
            // Personal notes
            $q->where('user_id', $user->id)
                ->whereNull('tenant_id');

            // Team notes (if tenant context)
            if ($tenantId !== null) {
                $teamIds = $user->teams()->where('teams.tenant_id', $tenantId)->pluck('teams.id');

                if ($teamIds->isNotEmpty()) {
                    $q->orWhereIn('team_id', $teamIds);
                }

                // Org-wide notes
                $q->orWhere('tenant_id', $tenantId);
            }

            // Notes shared with the user via access grants
            $sharedGrants = $this->accessResolver->whatDoesUserHaveAccessTo($user)
                ->filter(fn ($grant) => $grant->grantable_type === SecureNote::class)
                ->pluck('grantable_id');

            if ($sharedGrants->isNotEmpty()) {
                $q->orWhereIn('id', $sharedGrants);
            }
        });

        // Filter by folder
        if ($request->has('folder_id')) {
            $query->where('folder_id', $request->integer('folder_id'));
        }

        // Filter by team
        if ($request->has('team_id')) {
            $query->where('team_id', $request->integer('team_id'));
        }

        $notes = $query->orderByDesc('is_pinned')
            ->orderByDesc('updated_at')
            ->paginate(15);

        return SecureNoteResource::collection($notes);
    }

    /**
     * Create a note.
     */
    public function store(CreateNoteRequest $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $note = ($this->createNote)($user, $request->validated());

        return (new SecureNoteResource($note))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a note with decrypted content.
     */
    public function show(Request $request, SecureNote $note): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $this->authorize('view', $note);

        $this->viewTracker->recordView($user, $note);

        $note->load(['tags', 'user']);

        return (new SecureNoteResource($note))->response();
    }

    /**
     * Update a note.
     */
    public function update(UpdateNoteRequest $request, SecureNote $note): JsonResponse
    {
        $this->authenticatedUser($request);
        $this->authorize('update', $note);

        $note = ($this->updateNote)($note, $request->validated());

        return (new SecureNoteResource($note))->response();
    }

    /**
     * Soft-delete a note.
     */
    public function destroy(Request $request, SecureNote $note): JsonResponse
    {
        $this->authenticatedUser($request);
        $this->authorize('delete', $note);

        $note->delete();

        return response()->json(null, 204);
    }

    /**
     * Toggle the pinned status of a note.
     */
    public function togglePin(Request $request, SecureNote $note): JsonResponse
    {
        $this->authenticatedUser($request);
        $this->authorize('update', $note);

        $note->update(['is_pinned' => ! $note->is_pinned]);

        return (new SecureNoteResource($note->refresh()))->response();
    }

    /**
     * Search notes by title (plaintext, not encrypted content).
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        $user = $this->authenticatedUser($request);
        $tenantId = $this->tenantManager->currentTenantId();

        $query = $request->string('q')->toString();

        if ($query === '') {
            return SecureNoteResource::collection(collect());
        }

        $notes = SecureNote::query()
            ->where('title', 'like', '%'.$query.'%')
            ->where(function (Builder $q) use ($user, $tenantId): void {
                $q->where('user_id', $user->id)
                    ->whereNull('tenant_id');

                if ($tenantId !== null) {
                    $teamIds = $user->teams()->where('teams.tenant_id', $tenantId)->pluck('teams.id');

                    if ($teamIds->isNotEmpty()) {
                        $q->orWhereIn('team_id', $teamIds);
                    }

                    $q->orWhere('tenant_id', $tenantId);
                }

                $sharedGrants = $this->accessResolver->whatDoesUserHaveAccessTo($user)
                    ->filter(fn ($grant) => $grant->grantable_type === SecureNote::class)
                    ->pluck('grantable_id');

                if ($sharedGrants->isNotEmpty()) {
                    $q->orWhereIn('id', $sharedGrants);
                }
            })
            ->orderByDesc('updated_at')
            ->paginate(15);

        return SecureNoteResource::collection($notes);
    }

    /**
     * List trashed notes.
     */
    public function trash(Request $request): AnonymousResourceCollection
    {
        $tenantId = $this->tenantManager->currentTenantId();
        $items = ($this->listTrash)(
            SecureNote::class,
            $this->authenticatedUser($request),
            $tenantId,
            $request->integer('per_page', 20),
        );

        return SecureNoteResource::collection($items);
    }

    /**
     * Restore a trashed note.
     */
    public function restoreFromTrash(Request $request, int $note): JsonResponse
    {
        $model = ($this->restoreModel)(SecureNote::class, $note, $this->authenticatedUser($request), 'tenant_id');

        return (new SecureNoteResource($model))->response();
    }

    /**
     * Permanently delete a trashed note.
     */
    public function forceDelete(Request $request, int $note): JsonResponse
    {
        ($this->forceDeleteModel)(SecureNote::class, $note, $this->authenticatedUser($request), 'tenant_id');

        return response()->json(null, 204);
    }

    /**
     * Permanently delete all trashed notes.
     */
    public function emptyTrash(Request $request): JsonResponse
    {
        $tenantId = $this->tenantManager->currentTenantId();
        $count = ($this->emptyTrash)(SecureNote::class, $this->authenticatedUser($request), $tenantId);

        return response()->json(['deleted' => $count]);
    }
}
