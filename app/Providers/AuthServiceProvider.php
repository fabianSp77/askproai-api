<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        /**
         * -----------------------------------------------------------------
         *  Sofort-Bypass für Filament-Login
         *  (entfernen, sobald Shield/Permissions korrekt greifen)
         * -----------------------------------------------------------------
         */
        Gate::before(function ($user, string $ability) {
            // erlaubt JEDEM eingeloggten User den Zugang zu allen Panels
            if ($ability === 'viewFilament') {
                return true;
            }
            return null; // andere Abilities normal prüfen
        });

        /*
         |--------------------------------------------------------------
         | Passport deaktiviert
         |--------------------------------------------------------------
         | use Laravel\Passport\Passport;
         | Passport::routes();
         | einfach wieder aktivieren, wenn das Paket installiert ist.
         */
    }
}
