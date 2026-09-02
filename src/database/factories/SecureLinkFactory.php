<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SecureLink;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<SecureLink> */
class SecureLinkFactory extends Factory
{
    protected $model = SecureLink::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'uuid' => Str::uuid()->toString(),
            'resource_type' => 'App\\Models\\VaultItem',
            'resource_id' => 1,
            'created_by' => User::factory(),
            'recipient_email' => null,
            'password_hash' => null,
            'otp_code_hash' => null,
            'otp_sent_at' => null,
            'email_verified_at' => null,
            'permission' => 'view',
            'download_enabled' => true,
            'expires_at' => null,
            'first_view_expires_hours' => null,
            'max_views' => null,
            'views_count' => 0,
            'first_viewed_at' => null,
            'is_one_time' => false,
            'revoked_at' => null,
            'revoked_by' => null,
            'revoke_reason' => null,
        ];
    }
}
