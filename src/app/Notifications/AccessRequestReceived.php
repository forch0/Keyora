<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\AccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccessRequestReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly AccessRequest $request,
        public readonly string $requesterName,
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
        return (new MailMessage)
            ->subject('Access Request: '.$this->resourceName)
            ->line($this->requesterName.' has requested '.$this->request->requested_permission.' access to "'.$this->resourceName.'".')
            ->line('Reason: '.$this->request->reason)
            ->action('Review Request', url('/access-requests/'.$this->request->id));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'request_id' => $this->request->id,
            'requester_name' => $this->requesterName,
            'resource_name' => $this->resourceName,
            'requested_permission' => $this->request->requested_permission,
            'reason' => $this->request->reason,
            'type' => 'access_request_received',
        ];
    }
}
