<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SecureFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FileExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly SecureFile $file,
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
        $expiresAt = $this->file->getAttribute('expires_at');

        return (new MailMessage)
            ->subject('File Expired: '.$this->file->name)
            ->line('Your file "'.$this->file->name.'" has expired and has been archived.')
            ->line('Expired at: '.($expiresAt !== null ? $expiresAt->toDateTimeString() : 'N/A'))
            ->line('If you need this file, please contact an administrator to restore it.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'file_id' => $this->file->id,
            'file_name' => $this->file->name,
            'type' => 'file_expired',
        ];
    }
}
