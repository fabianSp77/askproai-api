<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;

class HorizonServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // E-Mails, die das Dashboard sehen dürfen:
        Horizon::auth(fn ($request) =>
            in_array(
                optional($request->user())->email,
                [
                    'fabian@askproai.de',
                    'admin@askproai.de'    // Admin user für Horizon Zugriff
                ]
            )
        );
    }
}
