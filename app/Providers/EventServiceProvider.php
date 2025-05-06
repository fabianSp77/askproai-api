<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Hier kannst du später Event→Listener-Zuordnungen eintragen.
     *
     * protected $listen = [
     *     SomeEvent::class => [ SomeListener::class ],
     * ];
     */
    protected $listen = [];

    public function boot(): void
    {
        // leer reicht – Hauptsache die Klasse existiert
    }
}
