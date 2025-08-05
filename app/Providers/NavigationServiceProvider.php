<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Filament\Admin\Navigation\NavigationBadgeCalculator;

/**
 * Navigation Service Provider
 * 
 * Registers navigation-related services and configurations
 */
class NavigationServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Register NavigationBadgeCalculator as singleton
        $this->app->singleton(NavigationBadgeCalculator::class, function ($app) {
            return new NavigationBadgeCalculator();
        });
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Register event listeners for cache invalidation
        $this->registerEventListeners();
        
        // Register navigation macros
        $this->registerNavigationMacros();
    }

    /**
     * Register event listeners for badge cache invalidation
     */
    protected function registerEventListeners(): void
    {
        // Clear badge cache when relevant models are updated
        $events = [
            'eloquent.created: App\Models\Call' => 'handleCallEvent',
            'eloquent.updated: App\Models\Call' => 'handleCallEvent',
            'eloquent.deleted: App\Models\Call' => 'handleCallEvent',
            
            'eloquent.created: App\Models\Appointment' => 'handleAppointmentEvent',
            'eloquent.updated: App\Models\Appointment' => 'handleAppointmentEvent',
            'eloquent.deleted: App\Models\Appointment' => 'handleAppointmentEvent',
            
            'eloquent.created: App\Models\CallCampaign' => 'handleCampaignEvent',
            'eloquent.updated: App\Models\CallCampaign' => 'handleCampaignEvent',
            
            'eloquent.created: App\Models\CustomerFeedback' => 'handleFeedbackEvent',
            'eloquent.updated: App\Models\CustomerFeedback' => 'handleFeedbackEvent',
            
            'eloquent.updated: App\Models\PrepaidBalance' => 'handleBalanceEvent',
        ];

        foreach ($events as $event => $handler) {
            \Event::listen($event, [$this, $handler]);
        }
    }

    /**
     * Handle call events
     */
    public function handleCallEvent($model): void
    {
        // Clear live calls count cache for all users
        $this->clearBadgeCache(['live_calls_count']);
    }

    /**
     * Handle appointment events
     */
    public function handleAppointmentEvent($model): void
    {
        // Clear today's appointments cache
        $this->clearBadgeCache(['today_appointments_count']);
    }

    /**
     * Handle campaign events
     */
    public function handleCampaignEvent($model): void
    {
        // Clear active campaigns cache
        $this->clearBadgeCache(['active_campaigns_count']);
    }

    /**
     * Handle feedback events
     */
    public function handleFeedbackEvent($model): void
    {
        // Clear unread feedback cache
        $this->clearBadgeCache(['unread_feedback_count']);
    }

    /**
     * Handle balance events
     */
    public function handleBalanceEvent($model): void
    {
        // Clear low balance cache
        $this->clearBadgeCache(['low_balance_count']);
    }

    /**
     * Clear specific badge caches
     */
    protected function clearBadgeCache(array $badges): void
    {
        foreach ($badges as $badge) {
            // Clear for all users - in production, use more targeted approach
            \Cache::tags(['navigation_badges'])->flush();
        }
    }

    /**
     * Register navigation-related macros
     */
    protected function registerNavigationMacros(): void
    {
        // Add a macro to check if user can see navigation group
        \Illuminate\Support\Collection::macro('filterNavigationItems', function () {
            return $this->filter(function ($item) {
                return \App\Filament\Admin\Navigation\NavigationConfig::canSeeNavigationItem($item);
            });
        });
    }
}