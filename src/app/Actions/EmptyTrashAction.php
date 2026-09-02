<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmptyTrashAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * Permanently delete all trashed items for a model class, scoped by user or tenant.
     *
     * @param  class-string<Model>  $modelClass
     * @return int Number of items deleted
     */
    public function __invoke(string $modelClass, User $user, ?int $tenantId = null): int
    {
        if (! in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            throw new \InvalidArgumentException('Model does not use SoftDeletes.');
        }

        // @phpstan-ignore-next-line staticMethod.notFound (SoftDeletes trait provides onlyTrashed via class-string)
        $query = $modelClass::onlyTrashed();

        if (in_array('user_id', (new $modelClass)->getFillable(), true) && $tenantId === null) {
            $query->where('user_id', $user->id);
        }

        if ($tenantId !== null && in_array(BelongsToTenant::class, class_uses_recursive($modelClass), true)) {
            $query->where('tenant_id', $tenantId);
        }

        $trashed = $query->get();
        $count = 0;

        foreach ($trashed as $item) {
            $item->forceDelete();
            $count++;
        }

        if ($count > 0) {
            $this->activityLogger->log(
                user: $user,
                action: 'empty_trash',
                subject: null,
                properties: ['model' => $modelClass, 'count' => $count],
            );
        }

        return $count;
    }
}
