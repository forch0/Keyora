<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to a user when their access to a resource is expiring soon.
 *
 * @param  array<string, mixed>  $accessInfo
 */
class AccessExpiringAlert extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $accessInfo
     */
    public function __construct(
        public readonly array $accessInfo,
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
        return $this->accessInfo;
    }
}
