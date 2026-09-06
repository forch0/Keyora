<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PersonalVaultFolder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonalVaultFolder>
 */
class PersonalVaultFolderFactory extends Factory
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
            'name' => fake()->words(2, true),
            'parent_id' => null,
            'icon' => null,
            'color' => null,
            'sort_order' => 0,
        ];
    }
}
