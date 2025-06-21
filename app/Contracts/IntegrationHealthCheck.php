<?php

namespace App\Contracts;

use App\Models\Company;

interface IntegrationHealthCheck
{
    /**
     * Run the health check for a specific company
     * 
     * @param Company $company
     * @return HealthCheckResult
     */
    public function check(Company $company): HealthCheckResult;
    
    /**
     * Get service name
     */
    public function getName(): string;
    
    /**
     * Get check priority (higher = more important)
     */
    public function getPriority(): int;
    
    /**
     * Whether this check is critical for operation
     */
    public function isCritical(): bool;
    
    /**
     * Get detailed diagnostics
     */
    public function getDiagnostics(): array;
    
    /**
     * Get suggested fixes for common issues
     * 
     * @param array $issues
     * @return array
     */
    public function getSuggestedFixes(array $issues): array;
    
    /**
     * Run automatic fixes if possible
     * 
     * @param Company $company
     * @param array $issues
     * @return bool
     */
    public function attemptAutoFix(Company $company, array $issues): bool;
}

class HealthCheckResult
{
    public const STATUS_HEALTHY = 'healthy';
    public const STATUS_DEGRADED = 'degraded';
    public const STATUS_UNHEALTHY = 'unhealthy';
    
    public function __construct(
        public readonly string $status,
        public readonly string $message,
        public readonly array $details = [],
        public readonly array $metrics = [],
        public readonly ?float $responseTime = null,
        public readonly array $issues = [],
        public readonly array $suggestions = []
    ) {
        if (!in_array($status, [self::STATUS_HEALTHY, self::STATUS_DEGRADED, self::STATUS_UNHEALTHY])) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }
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
    
    public function getColor(): string
    {
        return match($this->status) {
            self::STATUS_HEALTHY => 'success',
            self::STATUS_DEGRADED => 'warning',
            self::STATUS_UNHEALTHY => 'danger',
        };
    }
    
    public function getIcon(): string
    {
        return match($this->status) {
            self::STATUS_HEALTHY => 'heroicon-o-check-circle',
            self::STATUS_DEGRADED => 'heroicon-o-exclamation-triangle',
            self::STATUS_UNHEALTHY => 'heroicon-o-x-circle',
        };
    }
    
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'details' => $this->details,
            'metrics' => $this->metrics,
            'response_time' => $this->responseTime,
            'issues' => $this->issues,
            'suggestions' => $this->suggestions,
            'color' => $this->getColor(),
            'icon' => $this->getIcon(),
            'timestamp' => now()->toIso8601String(),
        ];
    }
    
    public static function healthy(string $message, array $details = [], array $metrics = []): self
    {
        return new self(
            status: self::STATUS_HEALTHY,
            message: $message,
            details: $details,
            metrics: $metrics
        );
    }
    
    public static function degraded(string $message, array $issues = [], array $suggestions = []): self
    {
        return new self(
            status: self::STATUS_DEGRADED,
            message: $message,
            issues: $issues,
            suggestions: $suggestions
        );
    }
    
    public static function unhealthy(string $message, array $issues = [], array $suggestions = []): self
    {
        return new self(
            status: self::STATUS_UNHEALTHY,
            message: $message,
            issues: $issues,
            suggestions: $suggestions
        );
    }
}

/**
 * Health Report containing multiple check results
 */
class HealthReport
{
    public function __construct(
        public readonly string $status,
        public readonly array $checks,
        public readonly array $criticalFailures = [],
        public readonly \DateTimeInterface $timestamp = new \DateTime(),
        public readonly float $totalExecutionTime = 0.0
    ) {}
    
    public function isHealthy(): bool
    {
        return $this->status === HealthCheckResult::STATUS_HEALTHY;
    }
    
    public function hasCriticalFailures(): bool
    {
        return count($this->criticalFailures) > 0;
    }
    
    public function getFailedChecks(): array
    {
        return array_filter($this->checks, fn($check) => $check->isUnhealthy());
    }
    
    public function getDegradedChecks(): array
    {
        return array_filter($this->checks, fn($check) => $check->isDegraded());
    }
    
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'timestamp' => $this->timestamp->format('Y-m-d H:i:s'),
            'total_execution_time' => round($this->totalExecutionTime, 3),
            'checks' => array_map(fn($check) => $check->toArray(), $this->checks),
            'critical_failures' => $this->criticalFailures,
            'summary' => [
                'total' => count($this->checks),
                'healthy' => count(array_filter($this->checks, fn($c) => $c->isHealthy())),
                'degraded' => count($this->getDegradedChecks()),
                'unhealthy' => count($this->getFailedChecks()),
            ],
        ];
    }
}