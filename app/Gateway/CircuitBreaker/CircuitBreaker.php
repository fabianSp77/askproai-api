<?php

namespace App\Gateway\CircuitBreaker;

use Illuminate\Contracts\Cache\Repository as CacheInterface;
use Illuminate\Support\Facades\Log;
use Exception;

class CircuitBreaker
{
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';

    private CacheInterface $cache;
    private array $config;

    public function __construct(CacheInterface $cache, array $config = [])
    {
        $this->cache = $cache;
        $this->config = $config;
    }

    /**
     * Execute a callable with circuit breaker protection
     */
    public function call(string $service, callable $callback): mixed
    {
        $state = $this->getState($service);

        switch ($state) {
            case self::STATE_OPEN:
                if ($this->shouldAttemptReset($service)) {
                    $this->setState($service, self::STATE_HALF_OPEN);
                    return $this->executeWithFallback($service, $callback);
                }
                return $this->getFallbackResponse($service);

            case self::STATE_HALF_OPEN:
                return $this->executeWithMonitoring($service, $callback);

            case self::STATE_CLOSED:
            default:
                return $this->executeWithMonitoring($service, $callback);
        }
    }

    /**
     * Execute callback with monitoring and fallback
     */
    private function executeWithMonitoring(string $service, callable $callback): mixed
    {
        $startTime = microtime(true);

        try {
            $result = $callback();
            $duration = microtime(true) - $startTime;
            
            $this->recordSuccess($service, $duration);
            
            // If we were in half-open state and succeeded, close the circuit
            if ($this->getState($service) === self::STATE_HALF_OPEN) {
                if ($this->shouldCloseCircuit($service)) {
                    $this->setState($service, self::STATE_CLOSED);
                    $this->resetCounters($service);
                }
            }
            
            return $result;

        } catch (Exception $e) {
            $duration = microtime(true) - $startTime;
            $this->recordFailure($service, $e, $duration);

            if ($this->shouldOpenCircuit($service)) {
                $this->setState($service, self::STATE_OPEN);
                $this->setOpenTimestamp($service);
                
                Log::warning('Circuit breaker opened for service', [
                    'service' => $service,
                    'error' => $e->getMessage(),
                    'failure_count' => $this->getFailureCount($service),
                ]);
            }

            return $this->getFallbackResponse($service);
        }
    }

    /**
     * Execute with fallback handling
     */
    private function executeWithFallback(string $service, callable $callback): mixed
    {
        try {
            return $this->executeWithMonitoring($service, $callback);
        } catch (Exception $e) {
            return $this->getFallbackResponse($service);
        }
    }

    /**
     * Get current circuit state
     */
    private function getState(string $service): string
    {
        return $this->cache->get($this->getStateKey($service), self::STATE_CLOSED);
    }

    /**
     * Set circuit state
     */
    private function setState(string $service, string $state): void
    {
        $this->cache->put($this->getStateKey($service), $state, 3600);
        
        Log::info('Circuit breaker state changed', [
            'service' => $service,
            'state' => $state,
        ]);
    }

    /**
     * Check if circuit should attempt reset
     */
    private function shouldAttemptReset(string $service): bool
    {
        $openTime = $this->getOpenTimestamp($service);
        $timeout = $this->getConfig($service, 'timeout', 60);
        
        return $openTime && (time() - $openTime) >= $timeout;
    }

    /**
     * Check if circuit should open
     */
    private function shouldOpenCircuit(string $service): bool
    {
        $failures = $this->getFailureCount($service);
        $threshold = $this->getConfig($service, 'failure_threshold', 5);
        
        return $failures >= $threshold;
    }

    /**
     * Check if circuit should close (from half-open)
     */
    private function shouldCloseCircuit(string $service): bool
    {
        $successes = $this->getSuccessCount($service);
        $threshold = $this->getConfig($service, 'success_threshold', 3);
        
        return $successes >= $threshold;
    }

    /**
     * Record successful execution
     */
    private function recordSuccess(string $service, float $duration): void
    {
        $key = $this->getSuccessKey($service);
        $this->cache->increment($key);
        $this->cache->expire($key, 300); // 5 minutes
        
        // Reset failure count on success
        $this->cache->forget($this->getFailureKey($service));
        
        // Record metrics
        $this->recordMetrics($service, 'success', $duration);
    }

    /**
     * Record failed execution
     */
    private function recordFailure(string $service, Exception $exception, float $duration): void
    {
        $key = $this->getFailureKey($service);
        $this->cache->increment($key);
        $this->cache->expire($key, 300); // 5 minutes
        
        // Record metrics
        $this->recordMetrics($service, 'failure', $duration, $exception);
        
        Log::warning('Circuit breaker recorded failure', [
            'service' => $service,
            'error' => $exception->getMessage(),
            'duration' => $duration,
            'failure_count' => $this->getFailureCount($service),
        ]);
    }

    /**
     * Get failure count
     */
    private function getFailureCount(string $service): int
    {
        return $this->cache->get($this->getFailureKey($service), 0);
    }

    /**
     * Get success count
     */
    private function getSuccessCount(string $service): int
    {
        return $this->cache->get($this->getSuccessKey($service), 0);
    }

    /**
     * Set open timestamp
     */
    private function setOpenTimestamp(string $service): void
    {
        $this->cache->put($this->getOpenTimeKey($service), time(), 3600);
    }

    /**
     * Get open timestamp
     */
    private function getOpenTimestamp(string $service): ?int
    {
        return $this->cache->get($this->getOpenTimeKey($service));
    }

    /**
     * Reset all counters
     */
    private function resetCounters(string $service): void
    {
        $this->cache->forget($this->getFailureKey($service));
        $this->cache->forget($this->getSuccessKey($service));
        $this->cache->forget($this->getOpenTimeKey($service));
    }

    /**
     * Get fallback response
     */
    private function getFallbackResponse(string $service): mixed
    {
        // Try to get cached response first
        $cached = $this->getCachedResponse($service);
        if ($cached !== null) {
            return $cached;
        }

        // Return configured fallback
        $fallback = $this->getConfig($service, 'fallback');
        if ($fallback) {
            return $fallback;
        }

        // Default fallback response based on service type
        return $this->getDefaultFallback($service);
    }

    /**
     * Get cached response for fallback
     */
    private function getCachedResponse(string $service): mixed
    {
        // This would integrate with the cache manager to get last successful response
        // For now, return null to use configured fallback
        return null;
    }

    /**
     * Get default fallback based on service
     */
    private function getDefaultFallback(string $service): array
    {
        $fallbacks = config('gateway.circuit_breaker.fallbacks', []);
        
        if (isset($fallbacks[$service])) {
            return $fallbacks[$service];
        }

        // Generic fallback
        return [
            'error' => 'Service temporarily unavailable',
            'message' => "The {$service} service is currently experiencing issues. Please try again later.",
            'fallback' => true,
            'service' => $service,
            'retry_after' => $this->getConfig($service, 'timeout', 60),
        ];
    }

    /**
     * Get configuration for service
     */
    private function getConfig(string $service, string $key, mixed $default = null): mixed
    {
        // Service-specific config
        $serviceConfig = $this->config['services'][$service] ?? [];
        if (isset($serviceConfig[$key])) {
            return $serviceConfig[$key];
        }

        // Default config
        $defaultConfig = $this->config['default'] ?? [];
        if (isset($defaultConfig[$key])) {
            return $defaultConfig[$key];
        }

        return $default;
    }

    /**
     * Record metrics for monitoring
     */
    private function recordMetrics(string $service, string $type, float $duration, Exception $exception = null): void
    {
        if (app()->bound('gateway.metrics')) {
            app('gateway.metrics')->recordCircuitBreakerEvent($service, $type, $duration, $exception);
        }
    }

    /**
     * Get cache keys
     */
    private function getStateKey(string $service): string
    {
        return "circuit_breaker:state:{$service}";
    }

    private function getFailureKey(string $service): string
    {
        return "circuit_breaker:failures:{$service}";
    }

    private function getSuccessKey(string $service): string
    {
        return "circuit_breaker:successes:{$service}";
    }

    private function getOpenTimeKey(string $service): string
    {
        return "circuit_breaker:open_time:{$service}";
    }

    /**
     * Get status of all circuit breakers
     */
    public function getStatus(): array
    {
        $services = array_keys($this->config['services'] ?? []);
        $status = [];

        foreach ($services as $service) {
            $status[$service] = [
                'state' => $this->getState($service),
                'failures' => $this->getFailureCount($service),
                'successes' => $this->getSuccessCount($service),
                'open_since' => $this->getOpenTimestamp($service),
            ];
        }

        return $status;
    }

    /**
     * Manually reset circuit breaker
     */
    public function reset(string $service): void
    {
        $this->setState($service, self::STATE_CLOSED);
        $this->resetCounters($service);
        
        Log::info('Circuit breaker manually reset', ['service' => $service]);
    }

    /**
     * Get circuit breaker health
     */
    public function getHealth(): array
    {
        $status = $this->getStatus();
        $totalServices = count($status);
        $openServices = count(array_filter($status, fn($s) => $s['state'] === self::STATE_OPEN));
        
        return [
            'status' => $openServices === 0 ? 'healthy' : ($openServices === $totalServices ? 'unhealthy' : 'degraded'),
            'total_services' => $totalServices,
            'open_services' => $openServices,
            'services' => $status,
        ];
    }
}