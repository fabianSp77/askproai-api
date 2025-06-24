<?php

namespace App\Contracts;

class HealthCheckResult
{
    const STATUS_HEALTHY = 'healthy';
    const STATUS_DEGRADED = 'degraded';
    const STATUS_UNHEALTHY = 'unhealthy';
    
    public string $status;
    public string $message;
    public float $responseTime;
    public array $metadata = [];
    
    public function __construct(
        string $status = self::STATUS_HEALTHY,
        string $message = '',
        float $responseTime = 0.0,
        array $metadata = []
    ) {
        $this->status = $status;
        $this->message = $message;
        $this->responseTime = $responseTime;
        $this->metadata = $metadata;
    }
    
    public function isHealthy(): bool
    {
        return $this->status === self::STATUS_HEALTHY;
    }
    
    public function isDegraded(): bool
    {
        return $this->status === self::STATUS_DEGRADED;
    }
    
    public function isUnhealthy(): bool
    {
        return $this->status === self::STATUS_UNHEALTHY;
    }
}