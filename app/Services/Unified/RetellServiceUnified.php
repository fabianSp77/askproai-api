<?php

namespace App\Services\Unified;

use App\Services\RetellService;
use App\Services\RetellV2Service;
use App\Services\RetellAgentService;
use App\Services\Integrations\RetellDeepIntegration;
use App\Services\Setup\RetellSetupService;
use App\Services\FeatureFlagService;
use App\Services\Monitoring\ServiceUsageTracker;
use App\Traits\TracksServiceUsage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Unified RetellService that intelligently routes to the appropriate version
 * based on feature flags and method requirements
 * 
 * Consolidates:
 * - RetellService (v1 basic API)
 * - RetellV2Service (v2 with circuit breaker)
 * - RetellAgentService (agent management)
 * - RetellDeepIntegration (deep integration features)
 * - RetellSetupService (setup and provisioning)
 */
class RetellServiceUnified
{
    use TracksServiceUsage;
    
    private ?RetellService $v1Service = null;
    private ?RetellV2Service $v2Service = null;
    private ?RetellAgentService $agentService = null;
    private ?RetellDeepIntegration $deepIntegration = null;
    private ?RetellSetupService $setupService = null;
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
     * Get all agents with intelligent routing
     */
    public function getAgents(?string $companyId = null): array
    {
        return $this->executeWithRouting('getAgents', [], $companyId);
    }
    
    /**
     * Get single agent details
     */
    public function getAgent(string $agentId, ?string $companyId = null): ?array
    {
        return $this->executeWithRouting('getAgent', [$agentId], $companyId);
    }
    
    /**
     * Get agent with full details (deep integration)
     */
    public function getAgentFullDetails(string $agentId, ?string $companyId = null): array
    {
        // This method requires deep integration
        return $this->executeWithRouting('getAgentFullDetails', [$agentId], $companyId, 'deep');
    }
    
    /**
     * Update agent configuration
     */
    public function updateAgent(string $agentId, array $data, ?string $companyId = null): ?array
    {
        return $this->executeWithRouting('updateAgent', [$agentId, $data], $companyId);
    }
    
    /**
     * Create phone call (V2 API)
     */
    public function createPhoneCall(array $payload, ?string $companyId = null): array
    {
        // This method requires V2 service
        return $this->executeWithRouting('createPhoneCall', [$payload], $companyId, 'v2');
    }
    
    /**
     * Get call details (V2 API)
     */
    public function getCallDetails(string $callId, ?string $companyId = null): ?array
    {
        return $this->executeWithRouting('getCallDetails', [$callId], $companyId, 'v2');
    }
    
    /**
     * List phone calls (V2 API)
     * Note: This method might not be available in all Retell API versions
     */
    public function listPhoneCalls(array $filters = [], ?string $companyId = null): array
    {
        // Try V2 first, fallback to empty array if not available
        try {
            return $this->executeWithRouting('listPhoneCalls', [$filters], $companyId, 'v2');
        } catch (\BadMethodCallException $e) {
            // Method doesn't exist, return empty result
            return ['calls' => []];
        }
    }
    
    /**
     * Get agent statistics
     */
    public function getAgentStatistics(string $agentId, ?string $companyId = null): array
    {
        return $this->executeWithRouting('getAgentStatistics', [$agentId], $companyId, 'agent');
    }
    
    /**
     * Provision agent for company (setup service)
     */
    public function provisionAgent($company, array $branches, array $config, ?string $companyId = null): void
    {
        $this->executeWithRouting('provisionAgent', [$company, $branches, $config], $companyId, 'setup');
    }
    
    /**
     * Build inbound response for calls
     */
    public function buildInboundResponse(string $agentId, ?string $fromNumber = null, array $dynamicVariables = []): array
    {
        // This is a static method in v1, we'll handle it directly
        return RetellService::buildInboundResponse($agentId, $fromNumber, $dynamicVariables);
    }
    
    /**
     * Clear cache
     */
    public function clearCache(): void
    {
        Cache::forget('retell_agents');
        Cache::tags(['retell'])->flush();
    }
    
    /**
     * Execute method with intelligent routing
     */
    private function executeWithRouting(string $method, array $args, ?string $companyId = null, ?string $preferredService = null)
    {
        $startTime = microtime(true);
        $service = $this->determineService($method, $companyId, $preferredService);
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
            if ($this->canFallback($service, $method, $companyId)) {
                Log::warning('RetellService failed, attempting fallback', [
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
     * Determine which service to use based on method and configuration
     */
    private function determineService(string $method, ?string $companyId, ?string $preferredService = null)
    {
        // If preferred service is specified, try to use it
        if ($preferredService) {
            switch ($preferredService) {
                case 'v2':
                    return $this->getV2Service();
                case 'deep':
                    return $this->getDeepIntegration();
                case 'agent':
                    return $this->getAgentService();
                case 'setup':
                    return $this->getSetupService();
            }
        }
        
        // Check feature flags
        if ($this->featureFlags->isEnabled('use_retell_v2_api', $companyId)) {
            // Check if method exists in V2
            if ($this->methodExistsInService($this->getV2Service(), $method)) {
                return $this->getV2Service();
            }
        }
        
        // Method-specific routing
        switch ($method) {
            case 'createPhoneCall':
            case 'getCallDetails':
                return $this->getV2Service();
                
            case 'getAgentFullDetails':
            case 'extractServiceMappings':
                return $this->getDeepIntegration();
                
            case 'getAgentStatistics':
                return $this->getAgentService();
                
            case 'provisionAgent':
            case 'provisionBranchAgent':
                return $this->getSetupService();
                
            default:
                // Default to V1 for basic operations
                return $this->getV1Service();
        }
    }
    
    /**
     * Get V1 service (lazy loaded)
     */
    private function getV1Service(): RetellService
    {
        if (!$this->v1Service) {
            $this->v1Service = app(RetellService::class);
        }
        return $this->v1Service;
    }
    
    /**
     * Get V2 service (lazy loaded)
     */
    private function getV2Service(): RetellV2Service
    {
        if (!$this->v2Service) {
            $this->v2Service = app(RetellV2Service::class);
        }
        return $this->v2Service;
    }
    
    /**
     * Get Agent service (lazy loaded)
     */
    private function getAgentService(): RetellAgentService
    {
        if (!$this->agentService) {
            $this->agentService = app(RetellAgentService::class);
        }
        return $this->agentService;
    }
    
    /**
     * Get Deep Integration service (lazy loaded)
     */
    private function getDeepIntegration(): RetellDeepIntegration
    {
        if (!$this->deepIntegration) {
            $this->deepIntegration = app(RetellDeepIntegration::class);
        }
        return $this->deepIntegration;
    }
    
    /**
     * Get Setup service (lazy loaded)
     */
    private function getSetupService(): RetellSetupService
    {
        if (!$this->setupService) {
            $this->setupService = app(RetellSetupService::class);
        }
        return $this->setupService;
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
     * Check if method exists in service
     */
    private function methodExistsInService($service, string $method): bool
    {
        return method_exists($service, $method);
    }
    
    /**
     * Execute in shadow mode for comparison
     */
    private function executeInShadowMode(string $method, array $args, $primaryService, ?string $companyId)
    {
        $shadowService = $this->getShadowService($primaryService, $method);
        
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
     * Get shadow service for comparison
     */
    private function getShadowService($primaryService, string $method)
    {
        if ($primaryService instanceof RetellService) {
            // Shadow with V2 if method exists
            $v2 = $this->getV2Service();
            if ($this->methodExistsInService($v2, $method)) {
                return $v2;
            }
        }
        
        return null;
    }
    
    /**
     * Check if should compare modes
     */
    private function shouldCompareModes(string $method, ?string $companyId): bool
    {
        // Only compare read operations
        $readMethods = ['getAgents', 'getAgent', 'getAgentDetails'];
        
        if (!in_array($method, $readMethods)) {
            return false;
        }
        
        // Check if company is in shadow mode rollout
        return $this->featureFlags->isEnabled('retell_shadow_mode', $companyId);
    }
    
    /**
     * Check if can fallback
     */
    private function canFallback($currentService, string $method, ?string $companyId): bool
    {
        // Can fallback from V2 to V1 for basic methods
        if ($currentService instanceof RetellV2Service) {
            return $this->methodExistsInService($this->getV1Service(), $method);
        }
        
        // Can fallback from Deep to Agent service
        if ($currentService instanceof RetellDeepIntegration) {
            return $this->methodExistsInService($this->getAgentService(), $method);
        }
        
        return false;
    }
    
    /**
     * Execute fallback
     */
    private function executeFallback(string $method, array $args, $failedService, ?string $companyId)
    {
        if ($failedService instanceof RetellV2Service) {
            return $this->executeMethod($this->getV1Service(), $method, $args);
        }
        
        if ($failedService instanceof RetellDeepIntegration) {
            return $this->executeMethod($this->getAgentService(), $method, $args);
        }
        
        throw new \RuntimeException('No fallback available');
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
        $keysToRemove = ['created_at', 'updated_at', 'last_sync', 'timestamp'];
        
        foreach ($keysToRemove as $key) {
            unset($result[$key]);
        }
        
        return $result;
    }
    
    /**
     * Get service name for logging
     */
    private function getServiceName($service): string
    {
        if ($service instanceof RetellV2Service) return 'v2';
        if ($service instanceof RetellDeepIntegration) return 'deep';
        if ($service instanceof RetellAgentService) return 'agent';
        if ($service instanceof RetellSetupService) return 'setup';
        if ($service instanceof RetellService) return 'v1';
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
        $sensitiveFields = ['api_key', 'token', 'authorization', 'bearer'];
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
            $agents = $this->getV1Service()->getAgents();
            $results['v1'] = ['status' => 'healthy', 'agents_count' => count($agents)];
        } catch (\Exception $e) {
            $results['v1'] = ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
        
        // Check V2
        try {
            // V2 service exists but may not have listPhoneCalls, check listAgents instead
            $agents = $this->getV2Service()->listAgents();
            $results['v2'] = ['status' => 'healthy', 'agents_count' => count($agents['agents'] ?? [])];
        } catch (\Exception $e) {
            $results['v2'] = ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
        
        // Check which is active
        $results['active_version'] = $this->getServiceName($this->determineService('getAgent', $companyId));
        $results['feature_flags'] = [
            'use_retell_v2_api' => $this->featureFlags->isEnabled('use_retell_v2_api', $companyId),
            'retell_shadow_mode' => $this->featureFlags->isEnabled('retell_shadow_mode', $companyId)
        ];
        
        return $results;
    }
    
    /**
     * Get service statistics
     */
    public function getServiceStats(int $hours = 24): array
    {
        return $this->tracker->getUsageStats('Retell', $hours);
    }
}