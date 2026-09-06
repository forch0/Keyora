<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SecureFile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SecureFile> */
class SecureFileFactory extends Factory
{
    protected $model = SecureFile::class;

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
            'name' => $this->faker->word().'.txt',
            'file_path' => $this->faker->uuid().'/'.$this->faker->word().'.txt',
            'mime_type' => 'text/plain',
            'size' => $this->faker->numberBetween(100, 1000000),
            'checksum' => hash('sha256', $this->faker->text()),
            'description' => null,
            'metadata' => null,
            'download_enabled' => true,
            'expires_at' => null,
            'archived_at' => null,
        ];
    }
}
