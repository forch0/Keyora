<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AccessGrant;
use App\Models\AccessRequest;
use App\Models\SecureFile;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ForceDeleteModelAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * Permanently delete a soft-deleted model, cleaning up related data.
     *
     * @param  class-string<Model>  $modelClass
     */
    public function __invoke(string $modelClass, int $id, User $user, string $ownerColumn = 'user_id'): void
    {
        if (! in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            throw new \InvalidArgumentException('Model does not use SoftDeletes.');
        }

        /** @var Model&SoftDeletes $modelClass */
        /** @var Builder<Model> $query */
        $query = $modelClass::onlyTrashed()->where('id', $id);

        if ($ownerColumn !== 'tenant_id') {
            $query->where($ownerColumn, $user->id);
        }

        $model = $query->first();

        if ($model === null) {
            abort(404);
        }

        $this->cleanupRelatedData($model);

        $this->activityLogger->log(
            user: $user,
            action: 'force_delete',
            subject: $model,
        );

        $model->forceDelete();
    }

    /**
     * Clean up related data before force-deleting a model.
     */
    private function cleanupRelatedData(Model $model): void
    {
        // SecureFile: delete the physical file from storage
        if ($model instanceof SecureFile) {
            $path = $model->getAttribute('path');
            if ($path !== null) {
                Storage::disk('private')->delete((string) $path);
            }
        }

        // Delete access grants referencing this model as grantable
        AccessGrant::where('grantable_type', $model::class)
            ->where('grantable_id', $model->getKey())
            ->forceDelete();

        // Delete access requests referencing this model as requestable
        AccessRequest::where('requestable_type', $model::class)
            ->where('requestable_id', $model->getKey())
            ->forceDelete();
    }
}
