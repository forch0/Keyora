<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\Permission;
use App\Models\AccessGrant;
use Illuminate\Notifications\Notification;

class AccessGrantedNotification extends Notification
{
    public function __construct(
        public readonly AccessGrant $grant,
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
        $resource = $this->grant->grantable;
        $grantedBy = $this->grant->grantedBy;

        /** @var Permission $permission */
        $permission = $this->grant->getAttribute('permission');

        return [
            'grant_id' => $this->grant->id,
            'resource_type' => $this->grant->grantable_type,
            'resource_id' => $this->grant->grantable_id,
            'resource_name' => $resource?->getAttribute('name'),
            'permission' => $permission->value,
            'granted_by' => $grantedBy?->name,
            'expires_at' => $this->grant->getAttribute('expires_at')?->toIso8601String(),
        ];
    }
}
