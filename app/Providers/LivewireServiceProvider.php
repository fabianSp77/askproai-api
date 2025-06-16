<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class LivewireServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Configure Livewire for HTTPS
        if (app()->environment('production')) {
            \URL::forceScheme('https');
        }
        
        // Don't override the asset URL - let Livewire handle it properly
        config([
            'livewire.app_url' => 'https://api.askproai.de',
        ]);
    }
}