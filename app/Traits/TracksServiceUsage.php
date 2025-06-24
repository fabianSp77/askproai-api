<?php

namespace App\Traits;

use App\Services\Monitoring\ServiceUsageTracker;

trait TracksServiceUsage
{
    /**
     * Track method execution automatically
     */
    protected function track(string $method, array $parameters = []): mixed
    {
        $tracker = app(ServiceUsageTracker::class);
        
        return $tracker->trackWithTiming(
            class_basename($this),
            $method,
            fn() => $this->$method(...$parameters),
            ['parameters' => $this->sanitizeParameters($parameters)]
        );
    }
    
    /**
     * Manual tracking for specific operations
     */
    protected function trackUsage(string $method, array $context = []): void
    {
        app(ServiceUsageTracker::class)->track(
            class_basename($this),
            $method,
            $context
        );
    }
    
    /**
     * Track and execute with custom callback
     */
    protected function trackAndExecute(string $method, callable $callback, array $context = [])
    {
        return app(ServiceUsageTracker::class)->trackWithTiming(
            class_basename($this),
            $method,
            $callback,
            $context
        );
    }
    
    /**
     * Sanitize parameters to avoid logging sensitive data
     */
    private function sanitizeParameters(array $parameters): array
    {
        return array_map(function ($param) {
            if (is_string($param) && strlen($param) > 100) {
                return substr($param, 0, 100) . '...';
            }
            if (is_array($param)) {
                // Remove sensitive keys
                $sensitiveKeys = ['password', 'api_key', 'secret', 'token', 'credential'];
                foreach ($sensitiveKeys as $key) {
                    if (isset($param[$key])) {
                        $param[$key] = '***';
                    }
                }
            }
            return $param;
        }, $parameters);
    }
}