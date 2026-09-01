<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use App\Models\VaultItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VaultItem>
 */
class VaultItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'team_id' => null,
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'type' => fake()->randomElement(['password', 'api_key', 'server', 'database', 'note']),
            'username' => fake()->optional()->userName(),
            'password' => fake()->optional()->password(),
            'url' => fake()->optional()->url(),
            'notes' => fake()->optional()->sentence(),
            'metadata' => null,
            'custom_fields' => null,
            'favorite' => fake()->boolean(20),
            'folder_id' => null,
        ];
    }
}
