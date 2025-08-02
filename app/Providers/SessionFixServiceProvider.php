<?php

namespace App\Providers;

use Illuminate\Session\Store;
use Illuminate\Support\ServiceProvider;

class SessionFixServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Override the session store's migrate method
        $this->app->extend('session.store', function ($store, $app) {
            // Add a custom migrate method that doesn't destroy data
            Store::macro('migrate', function ($destroy = false) {
                if ($destroy) {
                    // Store all current data
                    $data = $this->all();

                    // Regenerate the session ID
                    $this->regenerate(false);

                    // Restore all data
                    foreach ($data as $key => $value) {
                        $this->put($key, $value);
                    }

                    return true;
                }

                return $this->regenerate($destroy);
            });

            return $store;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
