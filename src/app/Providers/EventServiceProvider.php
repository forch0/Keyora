<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\AccessExpired;
use App\Events\AccessGranted;
use App\Events\AccessRequestApproved;
use App\Events\AccessRequested;
use App\Events\AccessRequestRejected;
use App\Events\AccessRevoked;
use App\Events\AccessUpdated;
use App\Events\AllAccessRevokedForResource;
use App\Events\EmergencyRevoked;
use App\Events\FileUploaded;
use App\Events\ResourceViewed;
use App\Events\SecureLinkCreated;
use App\Listeners\LogAccessExpired;
use App\Listeners\LogAccessGranted;
use App\Listeners\LogAccessRequestApproved;
use App\Listeners\LogAccessRequested;
use App\Listeners\LogAccessRequestRejected;
use App\Listeners\LogAccessRevoked;
use App\Listeners\LogAccessUpdated;
use App\Listeners\LogAllAccessRevokedForResource;
use App\Listeners\LogEmergencyRevoked;
use App\Listeners\LogFileUploaded;
use App\Listeners\LogResourceViewed;
use App\Listeners\LogSecureLinkCreated;
use App\Listeners\NotifyAccessGranted;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<string, list<string>>
     */
    protected $listen = [
        AccessGranted::class => [
            LogAccessGranted::class,
            NotifyAccessGranted::class,
        ],
        AccessRevoked::class => [
            LogAccessRevoked::class,
        ],
        AccessUpdated::class => [
            LogAccessUpdated::class,
        ],
        AccessExpired::class => [
            LogAccessExpired::class,
        ],
        EmergencyRevoked::class => [
            LogEmergencyRevoked::class,
        ],
        AllAccessRevokedForResource::class => [
            LogAllAccessRevokedForResource::class,
        ],
        AccessRequested::class => [
            LogAccessRequested::class,
        ],
        AccessRequestApproved::class => [
            LogAccessRequestApproved::class,
        ],
        AccessRequestRejected::class => [
            LogAccessRequestRejected::class,
        ],
        FileUploaded::class => [
            LogFileUploaded::class,
        ],
        SecureLinkCreated::class => [
            LogSecureLinkCreated::class,
        ],
        ResourceViewed::class => [
            LogResourceViewed::class,
        ],
    ];
}
