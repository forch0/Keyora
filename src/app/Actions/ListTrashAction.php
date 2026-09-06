<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Pagination\LengthAwarePaginator;

class ListTrashAction
{
    /**
     * List trashed items for a given model class, scoped by user or tenant.
     *
     * @param  class-string<Model>  $modelClass
     * @return LengthAwarePaginator<int, Model>
     */
    public function __invoke(string $modelClass, User $user, ?int $tenantId = null, int $perPage = 20): LengthAwarePaginator
    {
        if (! in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            throw new \InvalidArgumentException('Model does not use SoftDeletes.');
        }

        // Cap per_page to prevent excessive result sets
        $perPage = max(1, min($perPage, 100));

        /** @var Model&SoftDeletes $modelClass */
        /** @var Builder<Model> $query */
        $query = $modelClass::onlyTrashed();

        // Scope by user_id for user-owned models (PersonalVaultItem)
        if (in_array('user_id', (new $modelClass)->getFillable(), true) && $tenantId === null) {
            $query->where('user_id', $user->id);
        }

        // Scope by tenant_id for tenant-scoped models
        if ($tenantId !== null && in_array(BelongsToTenant::class, class_uses_recursive($modelClass), true)) {
            $query->where('tenant_id', $tenantId);
        }

        /** @var LengthAwarePaginator<int, Model> $result */
        $result = $query->orderBy('deleted_at', 'desc')->paginate($perPage);

        return $result;
    }
}
