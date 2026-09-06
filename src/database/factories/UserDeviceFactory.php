<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<UserDevice> */
class UserDeviceFactory extends Factory
{
    protected $model = UserDevice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'device_fingerprint' => $this->faker->uuid(),
            'browser' => 'Chrome',
            'os' => 'Windows',
            'device_type' => 'desktop',
            'ip_address' => $this->faker->ipv4(),
            'last_seen_at' => now(),
            'first_seen_at' => now(),
        ];
    }
}
