<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Horizon\Horizon;           // kannst du bei Bedarf wieder auskommentieren

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // hier könnten Paket-Bindings rein
    }

    public function boot(): void
    {
        /* -------------------------------------------------------------
         |  Fallback-Binding  →  verhindert "currentTenant"-Exceptions
         * ----------------------------------------------------------- */
        if (! app()->bound('currentTenant')) {
            app()->instance('currentTenant', null);
        }

        /* -------------------------------------------------------------
         |  Horizon absichern (nur für Admin-Rollen sichtbar)
         * ----------------------------------------------------------- */
        if (class_exists(Horizon::class)) {
            Horizon::auth(function ($request) {
                return optional($request->user())->hasRole('admin');
            });
        }
    }
}
