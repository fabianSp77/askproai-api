<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class LivewireRouteFix extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Use Livewire's built-in route registration
        \Livewire\Livewire::setUpdateRoute(function ($handle) {
            return \Route::post('/livewire/update', $handle)
                ->middleware(['web'])
                ->name('livewire.update');
        });
    }
}