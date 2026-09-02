<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\AccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccessRequestApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly AccessRequest $request,
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
        $granted = $this->request->granted_permission ?? $this->request->requested_permission;
        $expires = $this->request->getAttribute('granted_expires_at');

        return (new MailMessage)
            ->subject('Access Approved: '.$this->resourceName)
            ->line('Your access request for "'.$this->resourceName.'" has been approved.')
            ->line('Granted permission: '.$granted)
            ->line('Expires: '.($expires !== null ? $expires->toDateTimeString() : 'Never'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'request_id' => $this->request->id,
            'resource_name' => $this->resourceName,
            'granted_permission' => $this->request->granted_permission,
            'expires_at' => $this->request->getAttribute('granted_expires_at')?->toIso8601String(),
            'type' => 'access_request_approved',
        ];
    }
}
