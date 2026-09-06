<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NoteFolder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NoteFolder> */
class NoteFolderFactory extends Factory
{
    protected $model = NoteFolder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'team_id' => null,
            'user_id' => null,
            'name' => $this->faker->word(),
            'parent_id' => null,
            'created_by' => User::factory(),
        ];
    }
}
