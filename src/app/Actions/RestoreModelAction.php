<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RestoreModelAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * Restore a soft-deleted model.
     *
     * @param  class-string<Model>  $modelClass
     */
    public function __invoke(string $modelClass, int $id, User $user, string $ownerColumn = 'user_id'): Model
    {
        if (! in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            throw new \InvalidArgumentException('Model does not use SoftDeletes.');
        }

        // @phpstan-ignore-next-line staticMethod.notFound (SoftDeletes trait provides onlyTrashed via class-string)
        $query = $modelClass::onlyTrashed()->where('id', $id);

        if ($ownerColumn !== 'tenant_id') {
            $query->where($ownerColumn, $user->id);
        }

        $model = $query->first();

        if ($model === null) {
            abort(404);
        }

        $model->restore();

        $this->activityLogger->log(
            user: $user,
            action: 'restore',
            subject: $model,
        );

        return $model;
    }
}
