<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NoteTag;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NoteTag> */
class NoteTagFactory extends Factory
{
    protected $model = NoteTag::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => null,
            'name' => $this->faker->word(),
            'color' => $this->faker->hexColor(),
        ];
    }
}
