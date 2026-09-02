<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeOffboardedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly ?string $reason = null,
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
            ->subject('You have been offboarded from '.$this->tenant->name)
            ->line('Your access to '.$this->tenant->name.' has been revoked.')
            ->line('All your team memberships, API tokens, and secure links have been deactivated.')
            ->when($this->reason !== null, fn (MailMessage $m) => $m->line('Reason: '.$this->reason))
            ->line('If you believe this was a mistake, please contact your administrator.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
            'reason' => $this->reason,
        ];
    }
}
