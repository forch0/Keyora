<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\AccessGranted;
use App\Events\AccessRevoked;
use App\Listeners\LogAccessGranted;
use App\Listeners\LogAccessRevoked;
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
    ];
}
