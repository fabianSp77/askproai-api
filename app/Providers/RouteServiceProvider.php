<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log; // <-- Log importieren

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home'; // Anpassen, falls nötig

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        Log::channel('single')->debug('RouteServiceProvider: boot() method STARTED.'); // <-- Logging hier

        $this->configureRateLimiting();

        $this->routes(function () {
             Log::channel('single')->debug('RouteServiceProvider: Loading API routes...'); // <-- Logging hier
            Route::middleware('api') // Stelle sicher, dass die 'api' Gruppe verwendet wird
                ->prefix('api')
                ->group(base_path('routes/api.php'));

             Log::channel('single')->debug('RouteServiceProvider: Loading Web routes...'); // <-- Logging hier
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

             Log::channel('single')->debug('RouteServiceProvider: Route loading complete.'); // <-- Logging hier
        });

         Log::channel('single')->debug('RouteServiceProvider: boot() method FINISHED.'); // <-- Logging hier
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
             // Rate Limiting anpassen oder vorerst deaktivieren zum Testen: return Limit::none();
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
