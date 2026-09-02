<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\AccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccessRequestRejectedNotification extends Notification implements ShouldQueue
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
        $mail = (new MailMessage)
            ->subject('Access Request Rejected: '.$this->resourceName)
            ->line('Your access request for "'.$this->resourceName.'" has been rejected.');

        if ($this->request->review_note !== null) {
            $mail->line('Note: '.$this->request->review_note);
        }

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'request_id' => $this->request->id,
            'resource_name' => $this->resourceName,
            'review_note' => $this->request->review_note,
            'type' => 'access_request_rejected',
        ];
    }
}
