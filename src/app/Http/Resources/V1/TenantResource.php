<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tenant */
class TenantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'plan' => $this->plan,
            'settings' => $this->settings,
            'trial_ends_at' => $this->trial_ends_at,
            'role' => $this->whenPivotLoaded('tenant_user', function () {
                $pivot = $this->resource->getRelation('pivot');

                return $pivot instanceof Pivot ? $pivot->getAttribute('role') : null;
            }),
            'created_at' => $this->created_at,
        ];
    }
}
