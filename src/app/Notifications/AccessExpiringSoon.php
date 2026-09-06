<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\AccessGrant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccessExpiringSoon extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly AccessGrant $grant,
        public readonly string $resourceName,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expiresAt = $this->grant->getAttribute('expires_at');

        return (new MailMessage)
            ->subject('Access Expiring Soon: '.$this->resourceName)
            ->line('Your access to "'.$this->resourceName.'" will expire soon.')
            ->line('Expires at: '.($expiresAt !== null ? $expiresAt->toDateTimeString() : 'N/A'))
            ->line('Please save any work before the expiration time.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'grant_id' => $this->grant->id,
            'resource_name' => $this->resourceName,
            'expires_at' => $this->grant->getAttribute('expires_at')?->toIso8601String(),
            'type' => 'access_expiring_soon',
        ];
    }
}
