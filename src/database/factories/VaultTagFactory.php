<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\VaultTag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VaultTag>
 */
class VaultTagFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->unique()->word(),
            'color' => fake()->optional()->hexColor(),
        ];
    }
}
