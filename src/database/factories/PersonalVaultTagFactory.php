<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PersonalVaultTag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonalVaultTag>
 */
class PersonalVaultTagFactory extends Factory
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
            'name' => fake()->unique()->word(),
            'color' => fake()->optional()->hexColor(),
        ];
    }
}
