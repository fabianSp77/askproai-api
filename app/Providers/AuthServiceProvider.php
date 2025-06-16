<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\CalcomEventType::class => \App\Policies\CalcomEventTypePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        /**
         * -----------------------------------------------------------------
         *  Gate::before() ENTFERNT - Permissions funktionieren jetzt normal
         * -----------------------------------------------------------------
         */

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
