<?php

namespace App\Services\MCP;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CircuitBreakerService
{
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';

    private int $failureThreshold;
    private int $recoveryTime;
    private int $timeout;

    public function __construct()
    {
        $this->failureThreshold = config('retell-mcp.circuit_breaker.failure_threshold', 5);
        $this->recoveryTime = config('retell-mcp.circuit_breaker.recovery_time', 60);
        $this->timeout = config('retell-mcp.circuit_breaker.timeout', 10);
    }

    public function call(string $service, callable $callback, callable $fallback = null): mixed
    {
        $state = $this->getState($service);

        if ($state === self::STATE_OPEN) {
            if ($this->shouldAttemptReset($service)) {
                $this->setState($service, self::STATE_HALF_OPEN);
            } else {
                Log::warning("Circuit breaker open for service: {$service}");
                return $fallback ? $fallback() : throw new \Exception("Service {$service} is currently unavailable");
            }
        }

        try {
            $result = $callback();
            $this->onSuccess($service);
            return $result;
        } catch (\Exception $e) {
            $this->onFailure($service, $e);
            
            if ($fallback) {
                return $fallback();
            }
            throw $e;
        }
    }

    private function getState(string $service): string
    {
        return Cache::get("circuit_breaker:{$service}:state", self::STATE_CLOSED);
    }

    private function setState(string $service, string $state): void
    {
        Cache::put("circuit_breaker:{$service}:state", $state, 3600);
        
        if ($state === self::STATE_OPEN) {
            Cache::put("circuit_breaker:{$service}:opened_at", time(), 3600);
        }
    }

    private function shouldAttemptReset(string $service): bool
    {
        $openedAt = Cache::get("circuit_breaker:{$service}:opened_at");
        return $openedAt && (time() - $openedAt) >= $this->recoveryTime;
    }

    private function onSuccess(string $service): void
    {
        $state = $this->getState($service);
        
        if ($state === self::STATE_HALF_OPEN) {
            $this->setState($service, self::STATE_CLOSED);
            Cache::forget("circuit_breaker:{$service}:failures");
            Cache::forget("circuit_breaker:{$service}:opened_at");
        }
    }

    private function onFailure(string $service, \Exception $e): void
    {
        $failures = Cache::get("circuit_breaker:{$service}:failures", 0) + 1;
        Cache::put("circuit_breaker:{$service}:failures", $failures, 3600);

        Log::error("Circuit breaker failure for {$service}", [
            'failure_count' => $failures,
            'error' => $e->getMessage()
        ]);

        if ($failures >= $this->failureThreshold) {
            $this->setState($service, self::STATE_OPEN);
            Log::warning("Circuit breaker opened for service: {$service}");
        }
    }
}