<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     */
    protected $listen = [];
    
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
