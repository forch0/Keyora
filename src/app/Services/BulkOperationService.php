<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Permission;
use App\Enums\SubjectType;
use App\Events\AccessGranted;
use App\Models\AccessGrant;
use App\Models\PersonalVaultItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BulkOperationService
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly AccessResolver $accessResolver,
    ) {}

    /**
     * Bulk delete items. Only items the user owns (or has delete permission on) are deleted.
     *
     * @param  class-string<Model>  $modelClass
     * @param  array<int>  $ids
     * @return array{deleted: int, failed: int}
     */
    public function bulkDelete(string $modelClass, array $ids, User $actor): array
    {
        return DB::transaction(function () use ($modelClass, $ids, $actor): array {
            $deleted = 0;
            $failed = 0;

            foreach ($ids as $id) {
                $item = $this->findItem($modelClass, (int) $id);
                if ($item === null || ! $this->actorCanDelete($item, $actor)) {
                    $failed++;

                    continue;
                }
                $item->delete();
                $deleted++;
            }

            $this->logBulkAction($actor, 'bulk_delete', $modelClass, $deleted);

            return ['deleted' => $deleted, 'failed' => $failed];
        });
    }

    /**
     * Bulk move items to a folder.
     *
     * @param  class-string<Model>  $modelClass
     * @param  array<int>  $ids
     * @return array{moved: int, failed: int}
     */
    public function bulkMove(string $modelClass, array $ids, ?int $folderId, User $actor): array
    {
        return DB::transaction(function () use ($modelClass, $ids, $folderId, $actor): array {
            $moved = 0;
            $failed = 0;

            foreach ($ids as $id) {
                $item = $this->findItem($modelClass, (int) $id);
                if ($item === null || ! $this->actorOwns($item, $actor)) {
                    $failed++;

                    continue;
                }
                $item->update(['folder_id' => $folderId]);
                $moved++;
            }

            $this->logBulkAction($actor, 'bulk_move', $modelClass, $moved);

            return ['moved' => $moved, 'failed' => $failed];
        });
    }

    /**
     * Bulk archive items.
     *
     * @param  class-string<Model>  $modelClass
     * @param  array<int>  $ids
     * @return array{archived: int, failed: int}
     */
    public function bulkArchive(string $modelClass, array $ids, User $actor): array
    {
        return DB::transaction(function () use ($modelClass, $ids, $actor): array {
            $archived = 0;
            $failed = 0;

            foreach ($ids as $id) {
                $item = $this->findItem($modelClass, (int) $id);
                if ($item === null || ! $this->actorOwns($item, $actor)) {
                    $failed++;

                    continue;
                }
                $item->update(['archived_at' => now()]);
                $archived++;
            }

            $this->logBulkAction($actor, 'bulk_archive', $modelClass, $archived);

            return ['archived' => $archived, 'failed' => $failed];
        });
    }

    /**
     * Bulk restore archived items.
     *
     * @param  class-string<Model>  $modelClass
     * @param  array<int>  $ids
     * @return array{restored: int, failed: int}
     */
    public function bulkRestore(string $modelClass, array $ids, User $actor): array
    {
        return DB::transaction(function () use ($modelClass, $ids, $actor): array {
            $restored = 0;
            $failed = 0;

            foreach ($ids as $id) {
                $item = $this->findItem($modelClass, (int) $id);
                if ($item === null || ! $this->actorOwns($item, $actor)) {
                    $failed++;

                    continue;
                }
                $item->update(['archived_at' => null]);
                $restored++;
            }

            $this->logBulkAction($actor, 'bulk_restore', $modelClass, $restored);

            return ['restored' => $restored, 'failed' => $failed];
        });
    }

    /**
     * Bulk tag items. Action: attach, detach, or sync.
     *
     * @param  class-string<Model>  $modelClass
     * @param  array<int>  $ids
     * @param  array<int>  $tagIds
     * @return array{tagged: int, failed: int}
     */
    public function bulkTag(string $modelClass, array $ids, array $tagIds, string $action, User $actor): array
    {
        return DB::transaction(function () use ($modelClass, $ids, $tagIds, $action, $actor): array {
            $tagged = 0;
            $failed = 0;

            foreach ($ids as $id) {
                $item = $this->findItem($modelClass, (int) $id);
                if ($item === null || ! $this->actorOwns($item, $actor)) {
                    $failed++;

                    continue;
                }

                if (method_exists($item, 'tags')) {
                    match ($action) {
                        'attach' => $item->tags()->attach($tagIds),
                        'detach' => $item->tags()->detach($tagIds),
                        'sync' => $item->tags()->sync($tagIds),
                        default => null,
                    };
                }
                $tagged++;
            }

            $this->logBulkAction($actor, 'bulk_tag', $modelClass, $tagged);

            return ['tagged' => $tagged, 'failed' => $failed];
        });
    }

    /**
     * Bulk share items with a subject (user, team, or tenant).
     *
     * @param  class-string<Model>  $modelClass
     * @param  array<int>  $ids
     * @return array{shared: int, failed: int}
     */
    public function bulkShare(string $modelClass, array $ids, string $subjectType, int $subjectId, string $permission, User $actor, ?string $expiresAt = null): array
    {
        // Defense in depth: validate subject_type even though the Form Request
        // should already enforce the whitelist. This prevents arbitrary
        // class-string injection if this service is called from elsewhere.
        if (! in_array($subjectType, SubjectType::validClassStrings(), true)) {
            throw ValidationException::withMessages([
                'subject_type' => 'Subject type must be User, Team, or Tenant.',
            ]);
        }

        return DB::transaction(function () use ($modelClass, $ids, $subjectType, $subjectId, $permission, $actor, $expiresAt): array {
            $shared = 0;
            $failed = 0;

            foreach ($ids as $id) {
                $item = $this->findItem($modelClass, (int) $id);
                if ($item === null || ! $this->actorCanShare($item, $actor)) {
                    $failed++;

                    continue;
                }

                $tenantId = $actor->tenants()->first()?->id;
                if ($tenantId === null) {
                    $failed++;

                    continue;
                }

                $grant = AccessGrant::create([
                    'tenant_id' => $tenantId,
                    'grantable_type' => $modelClass,
                    'grantable_id' => $id,
                    'subject_type' => $subjectType,
                    'subject_id' => $subjectId,
                    'permission' => $permission,
                    'expires_at' => $expiresAt,
                    'granted_by' => $actor->id,
                ]);

                AccessGranted::dispatch($grant, $actor);
                $shared++;
            }

            $this->logBulkAction($actor, 'bulk_share', $modelClass, $shared);

            return ['shared' => $shared, 'failed' => $failed];
        });
    }

    /**
     * Bulk create personal vault items.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{created: int, errors: array<int, array{index: int, field: string, message: string}>}
     */
    public function bulkCreatePersonalVaultItems(array $items, User $actor): array
    {
        return DB::transaction(function () use ($items, $actor): array {
            $created = 0;
            $errors = [];

            foreach ($items as $index => $itemData) {
                $itemData['user_id'] = $actor->id;

                try {
                    PersonalVaultItem::create($itemData);
                    $created++;
                } catch (ValidationException $e) {
                    foreach ($e->errors() as $field => $messages) {
                        $errors[] = [
                            'index' => $index,
                            'field' => $field,
                            'message' => $messages[0],
                        ];
                    }
                } catch (QueryException $e) {
                    $errors[] = [
                        'index' => $index,
                        'field' => 'general',
                        'message' => $e->getMessage(),
                    ];
                }
            }

            $this->logBulkAction($actor, 'bulk_create', PersonalVaultItem::class, $created);

            return ['created' => $created, 'errors' => $errors];
        });
    }

    /**
     * Find a single item by ID, returning null if not found or if a collection is returned.
     *
     * @param  class-string<Model>  $modelClass
     */
    private function findItem(string $modelClass, int $id): ?Model
    {
        $result = $modelClass::find($id);

        if ($result instanceof Model) {
            return $result;
        }

        return null;
    }

    /**
     * Check if the actor owns the item (has a user_id matching the actor).
     */
    private function actorOwns(Model $item, User $actor): bool
    {
        $userId = $item->getAttribute('user_id');

        if ($userId !== null) {
            return $userId === $actor->id;
        }

        return false;
    }

    /**
     * Check if the actor can delete the item.
     *
     * For user-owned models (PersonalVaultItem), only the owner can delete.
     * For tenant-scoped models (VaultItem, SecureFile, SecureNote), the
     * actor must have Manage permission via AccessResolver.
     */
    private function actorCanDelete(Model $item, User $actor): bool
    {
        if ($this->actorOwns($item, $actor)) {
            return true;
        }

        // Tenant-scoped models: check Manage permission via AccessResolver
        if ($item->getAttribute('tenant_id') !== null) {
            return $this->accessResolver->can($actor, Permission::Manage, $item);
        }

        return false;
    }

    /**
     * Check if the actor can share the item.
     *
     * For user-owned models, only the owner can share.
     * For tenant-scoped models, the actor must have Share permission.
     */
    private function actorCanShare(Model $item, User $actor): bool
    {
        if ($this->actorOwns($item, $actor)) {
            return true;
        }

        // Tenant-scoped models: check Share permission via AccessResolver
        if ($item->getAttribute('tenant_id') !== null) {
            return $this->accessResolver->can($actor, Permission::Share, $item);
        }

        return false;
    }

    /**
     * Log a single bulk activity entry.
     */
    private function logBulkAction(User $actor, string $action, string $modelClass, int $count): void
    {
        if ($count > 0) {
            $this->activityLogger->log(
                user: $actor,
                action: $action,
                subject: null,
                properties: ['model' => $modelClass, 'count' => $count],
            );
        }
    }
}
