<?php

namespace App\Services\CircuitBreaker;

use Illuminate\Support\Facades\Log;
use App\Services\CircuitBreaker\CircuitBreaker;

/**
 * Circuit Breaker Manager for all external services
 * 
 * Centralized management of circuit breakers for:
 * - Cal.com API
 * - Retell.ai API
 * - Stripe API
 * - Any other external services
 */
class CircuitBreakerManager
{
    private static ?self $instance = null;
    private array $breakers = [];
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Get or create circuit breaker for a service
     */
    public function getBreaker(string $service): CircuitBreaker
    {
        if (!isset($this->breakers[$service])) {
            $config = config("circuit_breaker.services.{$service}", config('circuit_breaker'));
            
            $this->breakers[$service] = new CircuitBreaker(
                $config['failure_threshold'] ?? 5,
                $config['success_threshold'] ?? 2,
                $config['timeout'] ?? 60,
                $config['half_open_requests'] ?? 3
            );
            
            Log::info('Circuit breaker initialized', [
                'service' => $service,
                'config' => $config
            ]);
        }
        
        return $this->breakers[$service];
    }
    
    /**
     * Execute operation with circuit breaker protection
     */
    public function call(string $service, callable $operation, ?callable $fallback = null)
    {
        $breaker = $this->getBreaker($service);
        
        return $breaker->call($service, $operation, $fallback);
    }
    
    /**
     * Check if service is available
     */
    public function isAvailable(string $service): bool
    {
        $breaker = $this->getBreaker($service);
        return !$breaker->isOpen($service);
    }
    
    /**
     * Get status of all circuit breakers
     */
    public function getAllStatus(): array
    {
        $services = ['calcom', 'retell', 'stripe'];
        $status = [];
        
        foreach ($services as $service) {
            $breaker = $this->getBreaker($service);
            $status[$service] = [
                'available' => !$breaker->isOpen($service),
                'state' => $this->getServiceState($service),
                'health_score' => $this->calculateHealthScore($service)
            ];
        }
        
        return $status;
    }
    
    /**
     * Reset circuit breaker for a service
     */
    public function reset(string $service): void
    {
        Log::info('Resetting circuit breaker', ['service' => $service]);
        
        // Clear all cache keys for this service
        $keys = [
            "circuit_breaker.{$service}.failures",
            "circuit_breaker.{$service}.last_failure",
            "circuit_breaker.{$service}.successes",
            "circuit_breaker.{$service}.half_open_requests"
        ];
        
        foreach ($keys as $key) {
            \Cache::forget($key);
        }
    }
    
    /**
     * Get detailed state of a service
     */
    private function getServiceState(string $service): string
    {
        $breaker = $this->getBreaker($service);
        $failures = \Cache::get("circuit_breaker.{$service}.failures", 0);
        $lastFailure = \Cache::get("circuit_breaker.{$service}.last_failure");
        
        $config = config("circuit_breaker.services.{$service}", config('circuit_breaker'));
        $failureThreshold = $config['failure_threshold'] ?? 5;
        $timeout = $config['timeout'] ?? 60;
        
        if ($failures >= $failureThreshold) {
            if ($lastFailure && (time() - $lastFailure) > $timeout) {
                return CircuitState::HALF_OPEN;
            }
            return CircuitState::OPEN;
        }
        
        return CircuitState::CLOSED;
    }
    
    /**
     * Calculate health score for a service (0-100)
     */
    private function calculateHealthScore(string $service): int
    {
        $recentMetrics = \DB::table('circuit_breaker_metrics')
            ->where('service', $service)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->get();
        
        if ($recentMetrics->isEmpty()) {
            return 100; // No recent data means service hasn't been used
        }
        
        $successCount = $recentMetrics->where('status', 'success')->count();
        $totalCount = $recentMetrics->count();
        
        $successRate = ($successCount / $totalCount) * 100;
        
        // Factor in response times
        $avgDuration = $recentMetrics->avg('duration_ms');
        $durationPenalty = 0;
        
        if ($avgDuration > 1000) { // Over 1 second
            $durationPenalty = min(20, ($avgDuration - 1000) / 100);
        }
        
        return max(0, round($successRate - $durationPenalty));
    }
    
    /**
     * Force open a circuit (for testing or emergency)
     */
    public function forceOpen(string $service): void
    {
        $config = config("circuit_breaker.services.{$service}", config('circuit_breaker'));
        $failureThreshold = $config['failure_threshold'] ?? 5;
        
        \Cache::put("circuit_breaker.{$service}.failures", $failureThreshold, 300);
        \Cache::put("circuit_breaker.{$service}.last_failure", time(), 300);
        
        Log::warning('Circuit breaker forced open', ['service' => $service]);
    }
}