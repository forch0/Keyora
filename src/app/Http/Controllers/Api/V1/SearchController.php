<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AccessGrant;
use App\Models\PersonalVaultItem;
use App\Models\ResourceView;
use App\Models\SecureFile;
use App\Models\SecureLink;
use App\Models\SecureNote;
use App\Models\User;
use App\Services\GlobalSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SearchController extends Controller
{
    public function __construct(
        private readonly GlobalSearch $search,
    ) {}

    /**
     * Global search across all resource types.
     */
    public function search(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $query = $request->string('q')->toString();
        $type = $request->string('type')->toString() ?: null;

        if ($query === '') {
            return response()->json([
                'data' => [],
                'meta' => ['query' => '', 'total_results' => 0],
            ]);
        }

        $results = $this->search->search($user, $query, $type);

        $totalResults = collect($results)->sum(fn (Collection $items): int => $items->count());

        return response()->json([
            'data' => $results,
            'meta' => [
                'query' => $query,
                'total_results' => $totalResults,
            ],
        ]);
    }

    /**
     * Recently accessed items across all types.
     */
    public function recent(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $views = ResourceView::where('user_id', $user->id)
            ->latest('viewed_at')
            ->limit(50)
            ->get();

        $grouped = $views->groupBy('resource_type')->map(function (Collection $group, string $type): Collection {
            $ids = $group->pluck('resource_id')->unique()->take(20);

            return $this->resolveResources($type, $ids->all());
        });

        return response()->json(['data' => $grouped]);
    }

    /**
     * Recently created items across all types.
     */
    public function recentCreated(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $vaultItems = PersonalVaultItem::where('user_id', $user->id)
            ->latest()->limit(20)->get()
            ->map(fn (PersonalVaultItem $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'type' => 'vault_item',
                'created_at' => $item->getAttribute('created_at')?->toIso8601String(),
            ]);

        $files = SecureFile::where('user_id', $user->id)
            ->latest()->limit(20)->get()
            ->map(fn (SecureFile $file): array => [
                'id' => $file->id,
                'name' => $file->name,
                'type' => 'file',
                'created_at' => $file->getAttribute('created_at')?->toIso8601String(),
            ]);

        $notes = SecureNote::where('user_id', $user->id)
            ->latest()->limit(20)->get()
            ->map(fn (SecureNote $note): array => [
                'id' => $note->id,
                'title' => $note->title,
                'type' => 'note',
                'created_at' => $note->getAttribute('created_at')?->toIso8601String(),
            ]);

        return response()->json([
            'data' => [
                'vault_items' => $vaultItems,
                'files' => $files,
                'notes' => $notes,
            ],
        ]);
    }

    /**
     * Items with access expiring within 48 hours.
     */
    public function expiring(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $threshold = Carbon::now()->addHours(48);

        $grants = AccessGrant::withoutTenant()
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '<=', $threshold)
            ->where('expires_at', '>', Carbon::now())
            ->orderBy('expires_at')
            ->limit(50)
            ->get();

        $links = SecureLink::where('created_by', $user->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '<=', $threshold)
            ->where('expires_at', '>', Carbon::now())
            ->orderBy('expires_at')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => [
                'access_grants' => $grants->map(fn (AccessGrant $grant): array => [
                    'id' => $grant->id,
                    'resource_type' => $grant->grantable_type,
                    'resource_id' => $grant->grantable_id,
                    'expires_at' => $grant->getAttribute('expires_at')?->toIso8601String(),
                ]),
                'secure_links' => $links->map(fn (SecureLink $link): array => [
                    'id' => $link->id,
                    'uuid' => $link->uuid,
                    'resource_type' => $link->resource_type,
                    'resource_id' => $link->resource_id,
                    'expires_at' => $link->getAttribute('expires_at')?->toIso8601String(),
                ]),
            ],
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    /**
     * @param  array<int>  $ids
     * @return Collection<int, mixed>
     */
    private function resolveResources(string $type, array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        return match ($type) {
            PersonalVaultItem::class => PersonalVaultItem::whereIn('id', $ids)->get()
                ->map(fn (PersonalVaultItem $item): array => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'type' => $item->type,
                ]),
            SecureFile::class => SecureFile::whereIn('id', $ids)->get()
                ->map(fn (SecureFile $file): array => [
                    'id' => $file->id,
                    'name' => $file->name,
                ]),
            SecureNote::class => SecureNote::whereIn('id', $ids)->get()
                ->map(fn (SecureNote $note): array => [
                    'id' => $note->id,
                    'title' => $note->title,
                ]),
            default => collect(),
        };
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
