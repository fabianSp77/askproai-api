<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
// use Laravel\Horizon\Horizon;   // Horizon vorerst deaktiviert

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /*
         |--------------------------------------------------------------
         | Horizon deaktiviert
         |--------------------------------------------------------------
         | Wenn du Horizon erneut installierst, die Slashes unten entfernen.
         */
        // Horizon::auth(fn () => auth()->user()?->hasRole('super_admin'));
    }
}
