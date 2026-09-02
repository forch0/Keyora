<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AccessRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AccessRequest> */
class AccessRequestFactory extends Factory
{
    protected $model = AccessRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'requester_id' => User::factory(),
            'resource_type' => 'App\\Models\\VaultItem',
            'resource_id' => 1,
            'resource_owner_id' => User::factory(),
            'requested_permission' => 'view',
            'granted_permission' => null,
            'requested_duration' => '1h',
            'granted_expires_at' => null,
            'reason' => $this->faker->sentence(),
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_note' => null,
        ];
    }
}
