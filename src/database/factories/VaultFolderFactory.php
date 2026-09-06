<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use App\Models\VaultFolder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VaultFolder>
 */
class VaultFolderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'team_id' => null,
            'name' => fake()->words(2, true),
            'parent_id' => null,
            'created_by' => User::factory(),
        ];
    }
}
