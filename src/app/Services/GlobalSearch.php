<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PersonalVaultItem;
use App\Models\SecureFile;
use App\Models\SecureNote;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GlobalSearch
{
    private const LIMIT_PER_TYPE = 20;

    /**
     * Search across all resource types.
     *
     * @return array<string, Collection<int, array<string, mixed>>>
     */
    public function search(User $user, string $query, ?string $type = null): array
    {
        $results = [];

        if ($type === null || $type === 'vault_items') {
            $results['vault_items'] = $this->searchVaultItems($user, $query);
        }

        if ($type === null || $type === 'files') {
            $results['files'] = $this->searchFiles($user, $query);
        }

        if ($type === null || $type === 'notes') {
            $results['notes'] = $this->searchNotes($user, $query);
        }

        if ($type === null || $type === 'people') {
            $results['people'] = $this->searchPeople($user, $query);
        }

        if ($type === null || $type === 'teams') {
            $results['teams'] = $this->searchTeams($user, $query);
        }

        return $results;
    }

    /**
     * @return Collection<int, mixed>
     */
    private function searchVaultItems(User $user, string $query): Collection
    {
        return PersonalVaultItem::query()
            ->where('user_id', $user->id)
            ->where(function (Builder $q) use ($query): void {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('url', 'LIKE', "%{$query}%");
            })
            ->limit(self::LIMIT_PER_TYPE)
            ->get()
            ->map(fn (PersonalVaultItem $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'type' => $item->type,
                'url' => $item->url,
            ]);
    }

    /**
     * @return Collection<int, mixed>
     */
    private function searchFiles(User $user, string $query): Collection
    {
        return SecureFile::query()
            ->where('user_id', $user->id)
            ->where(function (Builder $q) use ($query): void {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->limit(self::LIMIT_PER_TYPE)
            ->get()
            ->map(fn (SecureFile $file): array => [
                'id' => $file->id,
                'name' => $file->name,
                'mime_type' => $file->getAttribute('mime_type'),
            ]);
    }

    /**
     * @return Collection<int, mixed>
     */
    private function searchNotes(User $user, string $query): Collection
    {
        return SecureNote::query()
            ->where('user_id', $user->id)
            ->where('title', 'LIKE', "%{$query}%")
            ->limit(self::LIMIT_PER_TYPE)
            ->get()
            ->map(fn (SecureNote $note): array => [
                'id' => $note->id,
                'title' => $note->title,
            ]);
    }

    /**
     * @return Collection<int, mixed>
     */
    private function searchPeople(User $user, string $query): Collection
    {
        $tenantId = $user->tenants()->first()?->id;

        if ($tenantId === null) {
            return collect();
        }

        return User::query()
            ->whereHas('tenants', fn (Builder $q) => $q->where('tenants.id', $tenantId))
            ->where(function (Builder $q) use ($query): void {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('email', 'LIKE', "%{$query}%");
            })
            ->limit(self::LIMIT_PER_TYPE)
            ->get()
            ->map(fn (User $person): array => [
                'id' => $person->id,
                'name' => $person->name,
                'email' => $person->email,
            ]);
    }

    /**
     * @return Collection<int, mixed>
     */
    private function searchTeams(User $user, string $query): Collection
    {
        $teamIds = $user->teams()->pluck('teams.id');

        if ($teamIds->isEmpty()) {
            return collect();
        }

        return Team::query()
            ->whereIn('id', $teamIds)
            ->where(function (Builder $q) use ($query): void {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->limit(self::LIMIT_PER_TYPE)
            ->get()
            ->map(fn (Team $team): array => [
                'id' => $team->id,
                'name' => $team->name,
            ]);
    }
}
