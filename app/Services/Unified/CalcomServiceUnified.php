<?php

namespace App\Services\Unified;

use App\Services\CalcomService;
use App\Services\CalcomV2Service;
use App\Services\CalcomEnhancedIntegration;
use App\Services\Calcom\CalcomBackwardsCompatibility;
use App\Services\FeatureFlagService;
use App\Services\Monitoring\ServiceUsageTracker;
use App\Traits\TracksServiceUsage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Unified CalcomService that intelligently routes to the appropriate version
 * based on feature flags and company preferences
 */
class CalcomServiceUnified
{
    use TracksServiceUsage;
    
    private $v1Service = null; // Can be CalcomService or CalcomBackwardsCompatibility
    private ?CalcomV2Service $v2Service = null;
    private ?CalcomEnhancedIntegration $enhancedService = null;
    private FeatureFlagService $featureFlags;
    private ServiceUsageTracker $tracker;
    
    private const CACHE_TTL = 300; // 5 minutes
    private const SHADOW_MODE_ENABLED = true;
    
    public function __construct(
        FeatureFlagService $featureFlags,
        ServiceUsageTracker $tracker
    ) {
        $this->featureFlags = $featureFlags;
        $this->tracker = $tracker;
    }
    
    /**
     * Get event types with intelligent routing
     */
    public function getEventTypes(?string $companyId = null): array
    {
        return $this->executeWithRouting('getEventTypes', [$companyId], $companyId);
    }
    
    /**
     * Create booking with automatic fallback
     */
    public function createBooking(array $data, ?string $companyId = null): array
    {
        return $this->executeWithRouting('createBooking', [$data], $companyId);
    }
    
    /**
     * Get availability with caching
     */
    public function getAvailability(int $eventTypeId, array $dateRange, ?string $companyId = null): array
    {
        $cacheKey = "calcom.availability.$eventTypeId." . md5(json_encode($dateRange));
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function() use ($eventTypeId, $dateRange, $companyId) {
            return $this->executeWithRouting('getAvailability', [$eventTypeId, $dateRange], $companyId);
        });
    }
    
    /**
     * Cancel booking
     */
    public function cancelBooking(int $bookingId, string $reason = '', ?string $companyId = null): bool
    {
        return $this->executeWithRouting('cancelBooking', [$bookingId, $reason], $companyId);
    }
    
    /**
     * Sync event types
     */
    public function syncEventTypes(?string $companyId = null): array
    {
        return $this->executeWithRouting('syncEventTypes', [$companyId], $companyId);
    }
    
    /**
     * Get booking details
     */
    public function getBooking(int $bookingId, ?string $companyId = null): ?array
    {
        return $this->executeWithRouting('getBooking', [$bookingId], $companyId);
    }
    
    /**
     * Update booking
     */
    public function updateBooking(int $bookingId, array $data, ?string $companyId = null): array
    {
        return $this->executeWithRouting('updateBooking', [$bookingId, $data], $companyId);
    }
    
    /**
     * Get team members
     */
    public function getTeamMembers(?string $teamSlug = null, ?string $companyId = null): array
    {
        return $this->executeWithRouting('getTeamMembers', [$teamSlug], $companyId);
    }
    
    /**
     * Execute method with intelligent routing
     */
    private function executeWithRouting(string $method, array $args, ?string $companyId = null)
    {
        $startTime = microtime(true);
        $service = $this->determineService($method, $companyId);
        $serviceName = $this->getServiceName($service);
        
        try {
            // Track service usage
            $this->trackUsage($method, [
                'service_version' => $serviceName,
                'company_id' => $companyId,
                'method' => $method
            ]);
            
            // Execute in shadow mode if enabled
            if (self::SHADOW_MODE_ENABLED && $this->shouldCompareModes($method, $companyId)) {
                return $this->executeInShadowMode($method, $args, $service, $companyId);
            }
            
            // Regular execution
            $result = $this->executeMethod($service, $method, $args);
            
            // Track success
            $executionTime = (microtime(true) - $startTime) * 1000;
            $this->trackUsage($method, [
                'success' => true,
                'execution_time' => $executionTime,
                'service_version' => $serviceName
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            // Track failure
            $executionTime = (microtime(true) - $startTime) * 1000;
            $this->trackUsage($method, [
                'success' => false,
                'execution_time' => $executionTime,
                'error' => $e->getMessage(),
                'service_version' => $serviceName
            ]);
            
            // Try fallback if available
            if ($this->canFallback($service, $companyId)) {
                Log::warning('CalcomService failed, attempting fallback', [
                    'method' => $method,
                    'service' => $serviceName,
                    'error' => $e->getMessage()
                ]);
                
                return $this->executeFallback($method, $args, $service, $companyId);
            }
            
            throw $e;
        }
    }
    
    /**
     * Execute in shadow mode for comparison
     */
    private function executeInShadowMode(string $method, array $args, $primaryService, ?string $companyId)
    {
        $shadowService = $this->getShadowService($primaryService);
        
        if (!$shadowService || !method_exists($shadowService, $method)) {
            return $this->executeMethod($primaryService, $method, $args);
        }
        
        // Execute primary
        $primaryResult = $this->executeMethod($primaryService, $method, $args);
        
        // Execute shadow in background
        try {
            $shadowResult = $this->executeMethod($shadowService, $method, $args);
            
            // Compare results
            if ($this->resultsAreDifferent($primaryResult, $shadowResult)) {
                Log::warning('Shadow mode detected difference', [
                    'method' => $method,
                    'primary_service' => $this->getServiceName($primaryService),
                    'shadow_service' => $this->getServiceName($shadowService),
                    'primary_result' => $this->sanitizeForLogging($primaryResult),
                    'shadow_result' => $this->sanitizeForLogging($shadowResult)
                ]);
            }
        } catch (\Exception $e) {
            Log::info('Shadow mode execution failed', [
                'method' => $method,
                'shadow_service' => $this->getServiceName($shadowService),
                'error' => $e->getMessage()
            ]);
        }
        
        // Always return primary result
        return $primaryResult;
    }
    
    /**
     * Determine which service to use
     */
    private function determineService(string $method, ?string $companyId)
    {
        // Check feature flags
        if ($this->featureFlags->isEnabled('use_calcom_v2_api', $companyId)) {
            return $this->getV2Service();
        }
        
        // Check if enhanced service is needed for this method
        if ($this->requiresEnhancedService($method)) {
            return $this->getEnhancedService();
        }
        
        // Default to V1
        return $this->getV1Service();
    }
    
    /**
     * Get V1 service (lazy loaded)
     * Returns CalcomService or CalcomBackwardsCompatibility
     */
    private function getV1Service()
    {
        if (!$this->v1Service) {
            $this->v1Service = app(CalcomService::class);
        }
        return $this->v1Service;
    }
    
    /**
     * Get V2 service (lazy loaded)
     */
    private function getV2Service(): CalcomV2Service
    {
        if (!$this->v2Service) {
            $this->v2Service = app(CalcomV2Service::class);
        }
        return $this->v2Service;
    }
    
    /**
     * Get Enhanced service (lazy loaded)
     */
    private function getEnhancedService(): CalcomEnhancedIntegration
    {
        if (!$this->enhancedService) {
            try {
                $this->enhancedService = app(CalcomEnhancedIntegration::class);
            } catch (\Exception $e) {
                // Fall back to V2 if enhanced not available
                Log::warning('CalcomEnhancedIntegration not available, falling back to V2');
                return $this->getV2Service();
            }
        }
        return $this->enhancedService;
    }
    
    /**
     * Execute method on service
     */
    private function executeMethod($service, string $method, array $args)
    {
        if (!method_exists($service, $method)) {
            throw new \BadMethodCallException("Method $method does not exist on " . get_class($service));
        }
        
        return $service->$method(...$args);
    }
    
    /**
     * Check if can fallback
     */
    private function canFallback($currentService, ?string $companyId): bool
    {
        // Can fallback from V2 to V1
        if ($currentService instanceof CalcomV2Service) {
            return true;
        }
        
        // Can fallback from Enhanced to V2
        if ($currentService instanceof CalcomEnhancedIntegration) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Execute fallback
     */
    private function executeFallback(string $method, array $args, $failedService, ?string $companyId)
    {
        if ($failedService instanceof CalcomV2Service) {
            return $this->executeMethod($this->getV1Service(), $method, $args);
        }
        
        if ($failedService instanceof CalcomEnhancedIntegration) {
            return $this->executeMethod($this->getV2Service(), $method, $args);
        }
        
        throw new \RuntimeException('No fallback available');
    }
    
    /**
     * Get shadow service for comparison
     */
    private function getShadowService($primaryService)
    {
        if ($primaryService instanceof CalcomService || $primaryService instanceof CalcomBackwardsCompatibility) {
            return $this->getV2Service();
        }
        
        return null;
    }
    
    /**
     * Check if should compare modes
     */
    private function shouldCompareModes(string $method, ?string $companyId): bool
    {
        // Only compare read operations
        $readMethods = ['getEventTypes', 'getAvailability', 'getBooking', 'getTeamMembers'];
        
        if (!in_array($method, $readMethods)) {
            return false;
        }
        
        // Check if company is in shadow mode rollout
        return $this->featureFlags->isEnabled('calcom_shadow_mode', $companyId);
    }
    
    /**
     * Check if results are different
     */
    private function resultsAreDifferent($result1, $result2): bool
    {
        // Normalize results before comparison
        $normalized1 = $this->normalizeResult($result1);
        $normalized2 = $this->normalizeResult($result2);
        
        return json_encode($normalized1) !== json_encode($normalized2);
    }
    
    /**
     * Normalize result for comparison
     */
    private function normalizeResult($result)
    {
        if (!is_array($result)) {
            return $result;
        }
        
        // Sort arrays by key
        ksort($result);
        
        // Remove timestamps and IDs that might differ
        $keysToRemove = ['created_at', 'updated_at', 'uuid', 'request_id'];
        
        foreach ($keysToRemove as $key) {
            unset($result[$key]);
        }
        
        return $result;
    }
    
    /**
     * Check if method requires enhanced service
     */
    private function requiresEnhancedService(string $method): bool
    {
        $enhancedMethods = ['createRecurringBooking', 'getAnalytics', 'bulkUpdateAvailability'];
        return in_array($method, $enhancedMethods);
    }
    
    /**
     * Get service name for logging
     */
    private function getServiceName($service): string
    {
        if ($service instanceof CalcomV2Service) return 'v2';
        if ($service instanceof CalcomEnhancedIntegration) return 'enhanced';
        if ($service instanceof CalcomService || $service instanceof CalcomBackwardsCompatibility) return 'v1';
        return 'unknown';
    }
    
    /**
     * Sanitize data for logging
     */
    private function sanitizeForLogging($data): array
    {
        if (!is_array($data)) {
            return ['type' => gettype($data), 'value' => 'non-array'];
        }
        
        // Limit array size
        if (count($data) > 10) {
            $data = array_slice($data, 0, 10);
            $data['_truncated'] = true;
        }
        
        // Remove sensitive fields
        $sensitiveFields = ['password', 'api_key', 'token', 'secret'];
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***';
            }
        }
        
        return $data;
    }
    
    /**
     * Health check for all services
     */
    public function healthCheck(?string $companyId = null): array
    {
        $results = [];
        
        // Check V1
        try {
            $this->getV1Service()->getEventTypes($companyId);
            $results['v1'] = ['status' => 'healthy', 'response_time' => null];
        } catch (\Exception $e) {
            $results['v1'] = ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
        
        // Check V2
        try {
            $this->getV2Service()->getEventTypes($companyId);
            $results['v2'] = ['status' => 'healthy', 'response_time' => null];
        } catch (\Exception $e) {
            $results['v2'] = ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
        
        // Check which is active
        $results['active_version'] = $this->getServiceName($this->determineService('getEventTypes', $companyId));
        $results['feature_flags'] = [
            'use_calcom_v2_api' => $this->featureFlags->isEnabled('use_calcom_v2_api', $companyId),
            'calcom_shadow_mode' => $this->featureFlags->isEnabled('calcom_shadow_mode', $companyId)
        ];
        
        return $results;
    }
}