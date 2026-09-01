<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PersonalVaultItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonalVaultItem>
 */
class PersonalVaultItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'type' => fake()->randomElement(['password', 'api_key', 'server', 'database']),
            'username' => fake()->userName(),
            'password' => fake()->password(),
            'url' => fake()->url(),
            'notes' => fake()->optional()->sentence(),
            'metadata' => null,
            'custom_fields' => null,
            'favorite' => fake()->boolean(20),
            'archived_at' => null,
        ];
    }
}
