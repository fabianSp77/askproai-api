<?php

namespace App\Contracts;

class HealthReport
{
    public string $status;
    public array $checks = [];
    public array $criticalFailures = [];
    public float $executionTime;
    public array $metadata = [];
    
    public function __construct(
        string $status = 'healthy',
        array $checks = [],
        array $criticalFailures = [],
        float $executionTime = 0.0,
        array $metadata = []
    ) {
        $this->status = $status;
        $this->checks = $checks;
        $this->criticalFailures = $criticalFailures;
        $this->executionTime = $executionTime;
        $this->metadata = $metadata;
    }
}