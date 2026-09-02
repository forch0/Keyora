<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SecurityAlert;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SecurityAlert> */
class SecurityAlertFactory extends Factory
{
    protected $model = SecurityAlert::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => SecurityAlert::TYPE_NEW_DEVICE_LOGIN,
            'severity' => SecurityAlert::SEVERITY_INFO,
            'title' => 'New device login',
            'message' => 'Your account was accessed from a new device.',
            'properties' => null,
            'read_at' => null,
            'dismissed_at' => null,
        ];
    }
}
