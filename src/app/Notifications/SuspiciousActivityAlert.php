<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a user when suspicious activity is detected.
 *
 * @param  array<string, mixed>  $activityInfo
 */
class SuspiciousActivityAlert extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $activityInfo
     */
    public function __construct(
        public readonly array $activityInfo,
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
            ->subject('Suspicious activity detected')
            ->line('We detected suspicious activity on your account.')
            ->line('Activity: '.($this->activityInfo['activity_type'] ?? 'Unknown'))
            ->line('IP: '.($this->activityInfo['ip_address'] ?? 'Unknown'))
            ->line('Count: '.($this->activityInfo['count'] ?? 0))
            ->line('Time: '.now()->toDateTimeString())
            ->action('Review your account', url('/security'))
            ->line('If this was not you, please secure your account immediately.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->activityInfo;
    }
}
