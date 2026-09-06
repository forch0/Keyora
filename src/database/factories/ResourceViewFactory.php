<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ResourceView;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ResourceView> */
class ResourceViewFactory extends Factory
{
    protected $model = ResourceView::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'resource_type' => 'App\\Models\\VaultItem',
            'resource_id' => 1,
            'viewed_at' => now(),
        ];
    }
}
