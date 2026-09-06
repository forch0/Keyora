<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SecureNote;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SecureNote> */
class SecureNoteFactory extends Factory
{
    protected $model = SecureNote::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'team_id' => null,
            'user_id' => User::factory(),
            'folder_id' => null,
            'title' => $this->faker->sentence(3),
            'content' => $this->faker->paragraph(),
            'content_format' => 'markdown',
            'is_pinned' => false,
        ];
    }
}
