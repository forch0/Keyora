<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a user when their account is accessed from a new device.
 *
 * @param  array<string, mixed>  $deviceInfo
 */
class NewDeviceLogin extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $deviceInfo
     */
    public function __construct(
        public readonly array $deviceInfo,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New device login detected')
            ->line('Your account was accessed from a new device.')
            ->line('Browser: '.($this->deviceInfo['browser'] ?? 'Unknown'))
            ->line('OS: '.($this->deviceInfo['os'] ?? 'Unknown'))
            ->line('IP: '.($this->deviceInfo['ip_address'] ?? 'Unknown'))
            ->line('Time: '.now()->toDateTimeString())
            ->action('Review your devices', url('/devices'))
            ->line('If this was not you, please change your password immediately.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->deviceInfo;
    }
}
