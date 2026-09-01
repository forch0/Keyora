<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeInvitation extends Notification
{
    use Queueable;

    public function __construct(
        public readonly TenantInvitation $invitation,
        public readonly Tenant $tenant,
        public readonly User $inviter,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You're invited to join {$this->tenant->name}")
            ->line("{$this->inviter->name} has invited you to join {$this->tenant->name} as a {$this->invitation->role}.")
            ->action('Accept Invitation', url("/invitations/accept?token={$this->invitation->token}"))
            ->line('This invitation will expire on '.$this->invitation->expires_at->format('M j, Y').'.')
            ->line('If you did not expect this invitation, you can safely ignore this email.');
    }
}
