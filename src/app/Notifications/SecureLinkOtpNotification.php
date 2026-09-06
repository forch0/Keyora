<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SecureLinkOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $otpCode,
        public readonly string $linkUrl,
        public readonly string $senderName,
        public readonly string $resourceName,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Secure Access Code')
            ->line($this->senderName.' has shared "'.$this->resourceName.'" with you via a secure link.')
            ->line('Your one-time access code is: **'.$this->otpCode.'**')
            ->line('This code is valid for 10 minutes.')
            ->action('Open Secure Link', $this->linkUrl);
    }
}
