<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Permission;
use App\Models\AccessGrant;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VaultItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AccessGrant> */
class AccessGrantFactory extends Factory
{
    protected $model = AccessGrant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'grantable_type' => VaultItem::class,
            'grantable_id' => VaultItem::factory(),
            'subject_type' => User::class,
            'subject_id' => User::factory(),
            'permission' => Permission::View,
            'expires_at' => null,
            'max_views' => null,
            'views_count' => 0,
            'starts_at' => null,
            'start_on_first_view' => false,
            'first_viewed_at' => null,
            'granted_by' => User::factory(),
            'revoked_at' => null,
            'revoked_by' => null,
            'revoke_reason' => null,
        ];
    }
}
