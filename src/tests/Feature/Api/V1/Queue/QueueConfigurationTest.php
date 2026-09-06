<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Queue;

use App\Notifications\AccessExpiringAlert;
use App\Notifications\AccessGrantedNotification;
use App\Notifications\EmployeeInvitation;
use App\Notifications\EmployeeOffboardedNotification;
use App\Notifications\NewDeviceLogin;
use App\Notifications\PasswordResetNotification;
use App\Notifications\RecoveryCodesRegenerated;
use App\Notifications\SuspiciousActivityAlert;
use App\Notifications\TwoFactorDisabled;
use App\Notifications\TwoFactorEnabled;
use App\Notifications\WelcomeNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Tests\TestCase;

class QueueConfigurationTest extends TestCase
{
    /**
     * All notifications must implement ShouldQueue so they don't
     * block the HTTP response while sending email or writing to DB.
     */
    public function test_all_notifications_implement_should_queue(): void
    {
        $notifications = [
            AccessExpiringAlert::class,
            AccessGrantedNotification::class,
            EmployeeInvitation::class,
            EmployeeOffboardedNotification::class,
            NewDeviceLogin::class,
            PasswordResetNotification::class,
            RecoveryCodesRegenerated::class,
            SuspiciousActivityAlert::class,
            TwoFactorDisabled::class,
            TwoFactorEnabled::class,
            WelcomeNotification::class,
        ];

        foreach ($notifications as $notificationClass) {
            $implements = class_implements($notificationClass) ?: [];

            $this->assertContains(
                ShouldQueue::class,
                $implements,
                "{$notificationClass} must implement ShouldQueue to avoid blocking HTTP responses.",
            );
        }
    }
}
