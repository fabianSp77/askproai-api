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
    
    public function call(string $service, callable $callback, ?callable $fallback = null)
    {
        $breaker = $this->getBreaker($service);
        
        return $breaker->call($service, $callback, $fallback);
    }
    
    public function getBreaker(string $service): CircuitBreaker
    {
        $config = $this->getConfig($service);
        
        return new CircuitBreaker(
            $config['failure_threshold'] ?? 5,
            $config['success_threshold'] ?? 2,
            $config['recovery_timeout'] ?? 60,
            $config['half_open_requests'] ?? 3
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