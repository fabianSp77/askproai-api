<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     */
    protected $listen = [
        \App\Events\CallCreated::class => [
            [\App\Listeners\CallEventListener::class, 'onCallCreated'],
        ],
        \App\Events\CallUpdated::class => [
            [\App\Listeners\CallEventListener::class, 'onCallUpdated'],
        ],
        // Queue job listeners for connection management
        \Illuminate\Queue\Events\JobProcessed::class => [
            \App\Listeners\ReleaseDbConnectionAfterJob::class,
        ],
        \Illuminate\Queue\Events\JobFailed::class => [
            \App\Listeners\ReleaseDbConnectionAfterJob::class,
        ],
    ];
    
    /**
     * The subscriber classes to register.
     */
    protected $subscribe = [
        \App\Listeners\LogAuthenticationEvents::class,
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
