<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SecureLink;
use App\Models\SecureLinkAccess;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SecureLinkAccess> */
class SecureLinkAccessFactory extends Factory
{
    protected $model = SecureLinkAccess::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'secure_link_id' => SecureLink::factory(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'email' => null,
            'accessed_at' => now(),
        ];
    }
}
