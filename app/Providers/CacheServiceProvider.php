<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CacheService;
use App\Http\Middleware\CacheApiResponse;
use App\Http\Middleware\CacheApiResponseByRoute;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register CacheService as singleton
        $this->app->singleton(CacheService::class, function ($app) {
            return new CacheService();
        });

        // Register cache middleware aliases
        $this->app['router']->aliasMiddleware('cache.api', CacheApiResponse::class);
        $this->app['router']->aliasMiddleware('cache.api.route', CacheApiResponseByRoute::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish cache strategy config
        $this->publishes([
            __DIR__.'/../../config/cache-strategy.php' => config_path('cache-strategy.php'),
        ], 'cache-config');

        // Register event listeners for cache invalidation
        $this->registerCacheInvalidationListeners();

        // Log cache configuration on boot
        if (config('cache-strategy.monitoring.log_stats')) {
            Log::info('Cache strategy initialized', [
                'driver' => config('cache.default'),
                'warming_enabled' => config('cache-strategy.warming.enabled'),
                'api_cache_enabled' => config('cache-strategy.api_response.enabled'),
            ]);
        }
    }

    /**
     * Register event listeners for automatic cache invalidation
     */
    protected function registerCacheInvalidationListeners(): void
    {
        $events = config('cache-strategy.invalidation.events', []);
        
        foreach ($events as $eventClass => $cacheTags) {
            Event::listen($eventClass, function ($event) use ($cacheTags) {
                $cacheService = app(CacheService::class);
                
                foreach ($cacheTags as $tag) {
                    // Handle dynamic tags (e.g., company:123)
                    if ($tag === 'company' && isset($event->company)) {
                        $cacheService->clearCompanyCache($event->company->id);
                    } elseif ($tag === 'staff' && isset($event->staff)) {
                        $cacheService->clearStaffCache($event->staff->id);
                    } elseif ($tag === 'appointments' && isset($event->appointment)) {
                        $cacheService->clearAppointmentsCache(
                            $event->appointment->starts_at->format('Y-m-d')
                        );
                    } elseif ($tag === 'services') {
                        // Clear all event types cache when services change
                        $cacheService->clearEventTypesCache();
                    }
                }
                
                Log::debug('Cache invalidated by event', [
                    'event' => get_class($event),
                    'tags' => $cacheTags
                ]);
            });
        }
    }
}