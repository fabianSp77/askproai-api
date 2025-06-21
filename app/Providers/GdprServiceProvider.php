<?php

namespace App\Providers;

use App\Services\CookieConsentService;
use App\Services\GdprService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;

class GdprServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register services as singletons
        $this->app->singleton(CookieConsentService::class);
        $this->app->singleton(GdprService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Share cookie consent data with all views
        View::composer('*', function ($view) {
            $consentService = app(CookieConsentService::class);
            
            $view->with([
                'cookieConsent' => $consentService->getCurrentConsent(),
                'showCookieBanner' => $consentService->shouldShowBanner(),
                'cookieCategories' => $consentService->getCookieCategories(),
            ]);
        });

        // Register Blade directives
        Blade::directive('cookieConsent', function ($expression) {
            return "<?php if (app(App\Services\CookieConsentService::class)->hasConsent({$expression})): ?>";
        });

        Blade::directive('endCookieConsent', function () {
            return '<?php endif; ?>';
        });

        // Usage example:
        // @cookieConsent('analytics')
        //     <!-- Google Analytics code here -->
        // @endCookieConsent

        // Publish config file
        $this->publishes([
            __DIR__.'/../../config/gdpr.php' => config_path('gdpr.php'),
        ], 'gdpr-config');

        // Load translations
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'gdpr');
    }
}