<?php

namespace App\Services\CircuitBreaker;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CircuitBreakerService
{
    protected array $configs = [];
    protected string $defaultConfig = 'default';
    
    public function __construct()
    {
        $this->loadConfigurations();
    }
    
    protected function loadConfigurations(): void
    {
        $this->configs = config('circuit_breaker', [
            'default' => [
                'failure_threshold' => 5,
                'recovery_timeout' => 60,
                'expected_exception' => null,
            ]
        ]);
    }
    
    public function call(string $service, callable $callback, ?string $fallback = null)
    {
        $breaker = $this->getBreaker($service);
        
        try {
            return $breaker->call($callback);
        } catch (\Exception $e) {
            if ($fallback && is_callable($fallback)) {
                return $fallback($e);
            }
            throw $e;
        }
    }
    
    public function getBreaker(string $service): CircuitBreaker
    {
        $config = $this->getConfig($service);
        
        return new CircuitBreaker(
            $service,
            $config['failure_threshold'] ?? 5,
            $config['recovery_timeout'] ?? 60
        );
    }
    
    protected function getConfig(string $service): array
    {
        return array_merge(
            $this->configs['default'] ?? [],
            $this->configs['services'][$service] ?? []
        );
    }
    
    public function isAvailable(string $service): bool
    {
        $breaker = $this->getBreaker($service);
        return $breaker->isAvailable();
    }
    
    public function getState(string $service): string
    {
        $breaker = $this->getBreaker($service);
        return $breaker->getState();
    }
    
    public function reset(string $service): void
    {
        $breaker = $this->getBreaker($service);
        $breaker->reset();
    }
    
    public function reportSuccess(string $service): void
    {
        $breaker = $this->getBreaker($service);
        $breaker->reportSuccess();
    }
    
    public function reportFailure(string $service): void
    {
        $breaker = $this->getBreaker($service);
        $breaker->reportFailure();
    }
}