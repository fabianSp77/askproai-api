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

        /*
         |--------------------------------------------------------------
         | Passport deaktiviert
         |--------------------------------------------------------------
         |  use Laravel\Passport\Passport;
         |  Passport::routes();
         | einfach wieder aktivieren, wenn das Paket installiert ist.
         */
    }
}
