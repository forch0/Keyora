<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\TenantInvitation;
use App\Notifications\EmployeeInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SendInviteEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly TenantInvitation $invitation,
    ) {}

    public function handle(): void
    {
        $tenant = $this->invitation->tenant;
        $inviter = $this->invitation->inviter;

        if ($tenant === null || $inviter === null) {
            return;
        }

        // Notify by email — the notifiable is a generic route since the
        // invitee may not have an account yet. We route to the invitation's
        // email address directly via an anonymous notifiable.
        Notification::route('mail', $this->invitation->email)
            ->notify(new EmployeeInvitation($this->invitation, $tenant, $inviter));
    }
}
