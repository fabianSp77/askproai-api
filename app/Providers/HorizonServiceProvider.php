<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;

class HorizonServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /*
         |------------------------------------------------------------------
         | Zugriffsschutz – nur Benutzer mit Rolle „admin“
         |------------------------------------------------------------------
         */
        Horizon::auth(fn ($request) =>
            $request->user() && $request->user()->hasRole('admin')
        );
    }
}
