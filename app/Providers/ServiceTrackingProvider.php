<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Monitoring\ServiceUsageTracker;

class ServiceTrackingProvider extends ServiceProvider
{
    /**
     * Services to track automatically
     */
    private array $trackedServices = [
        // Calcom Services
        \App\Services\CalcomService::class,
        \App\Services\CalcomV2Service::class,
        \App\Services\CalcomEnhancedIntegration::class,
        \App\Services\CalcomService_v1_only::class,
        \App\Services\CalcomBookingService::class,
        \App\Services\CalcomAvailabilityService::class,
        \App\Services\CalcomEventTypeService::class,
        
        // Retell Services  
        \App\Services\RetellService::class,
        \App\Services\RetellV2Service::class,
        \App\Services\RetellAgentService::class,
        \App\Services\RetellWebhookService::class,
        \App\Services\RetellCallService::class,
        
        // MCP Services
        \App\Services\MCP\CalcomMCPServer::class,
        \App\Services\MCP\RetellMCPServer::class,
        \App\Services\MCP\WebhookMCPServer::class,
        \App\Services\MCP\KnowledgeMCPServer::class,
        \App\Services\MCP\StripeMCPServer::class,
        
        // Event Type Parsers
        \App\Services\EventTypeNameParser::class,
        \App\Services\ImprovedEventTypeNameParser::class,
        \App\Services\SmartEventTypeNameParser::class,
        
        // Other Critical Services
        \App\Services\PhoneNumberResolver::class,
        \App\Services\AppointmentBookingService::class,
        \App\Services\BookingService::class,
        \App\Services\StaffAvailabilityService::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the tracker as singleton
        $this->app->singleton(ServiceUsageTracker::class, function () {
            return ServiceUsageTracker::getInstance();
        });
        
        // Wrap each tracked service
        foreach ($this->trackedServices as $serviceClass) {
            if (class_exists($serviceClass)) {
                $this->wrapService($serviceClass);
            }
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Flush tracker buffer on app termination
        $this->app->terminating(function () {
            app(ServiceUsageTracker::class)->flush();
        });
    }
    
    /**
     * Wrap a service with tracking proxy
     */
    private function wrapService(string $serviceClass): void
    {
        // For now, we'll use a trait-based approach instead of proxy
        // This avoids type hint issues
        // TODO: Implement proper proxy with interface extraction
    }
}