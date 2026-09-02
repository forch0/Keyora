<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SecureLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SecureLinkCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly SecureLink $link,
        public readonly string $resourceName,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'link_id' => $this->link->id,
            'uuid' => $this->link->uuid,
            'resource_name' => $this->resourceName,
            'recipient_email' => $this->link->recipient_email,
            'url' => $this->link->url(),
            'type' => 'secure_link_created',
        ];
    }
}
