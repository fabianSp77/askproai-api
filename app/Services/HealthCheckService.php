<?php

namespace App\Services;

use App\Contracts\IntegrationHealthCheck;
use App\Contracts\HealthCheckResult;
use App\Contracts\HealthReport;
use App\Models\Company;
use App\Services\HealthChecks\RetellHealthCheck;
use App\Services\HealthChecks\CalcomHealthCheck;
use App\Services\HealthChecks\PhoneRoutingHealthCheck;
use App\Services\HealthChecks\DatabaseHealthCheck;
use App\Services\HealthChecks\RedisHealthCheck;
use App\Services\HealthChecks\EmailHealthCheck;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class HealthCheckService
{
    protected array $checks = [];
    protected ?Company $company = null;
    
    /**
     * Initialize with company context
     */
    public function __construct(?Company $company = null)
    {
        $this->company = $company;
        $this->registerChecks();
    }
    
    /**
     * Set the company context
     */
    public function setCompany(Company $company): self
    {
        $this->company = $company;
        return $this;
    }
    
    /**
     * Register all available health checks
     */
    protected function registerChecks(): void
    {
        // Core integration checks
        $this->checks = [
            // Infrastructure checks
            app(DatabaseHealthCheck::class),
            app(RedisHealthCheck::class),
            app(EmailHealthCheck::class),
            
            // External service checks
            app(RetellHealthCheck::class),
            app(CalcomHealthCheck::class),
            app(PhoneRoutingHealthCheck::class),
        ];
        
        // Sort by priority (highest first)
        $this->checks = collect($this->checks)
            ->sortByDesc(fn($check) => $check->getPriority())
            ->values()
            ->toArray();
    }
    
    /**
     * Run all health checks
     */
    public function runAll(): HealthReport
    {
        if (!$this->company) {
            throw new \RuntimeException('Company context is required to run health checks');
        }
        
        $startTime = microtime(true);
        $results = [];
        $overallStatus = HealthCheckResult::STATUS_HEALTHY;
        $criticalFailures = [];
        
        foreach ($this->checks as $check) {
            try {
                $result = $this->runCheck($check);
                $results[$check->getName()] = $result;
                
                // Update overall status
                if ($result->isUnhealthy() && $check->isCritical()) {
                    $criticalFailures[] = $check->getName();
                    $overallStatus = HealthCheckResult::STATUS_UNHEALTHY;
                } elseif ($result->isDegraded() && $overallStatus === HealthCheckResult::STATUS_HEALTHY) {
                    $overallStatus = HealthCheckResult::STATUS_DEGRADED;
                }
                
            } catch (\Exception $e) {
                Log::error('Health check failed to execute', [
                    'check' => get_class($check),
                    'company_id' => $this->company->id,
                    'error' => $e->getMessage()
                ]);
                
                // Create error result
                $results[$check->getName()] = HealthCheckResult::unhealthy(
                    'Check failed to execute: ' . $e->getMessage(),
                    ['error' => $e->getMessage()],
                    ['Contact support']
                );
                
                if ($check->isCritical()) {
                    $criticalFailures[] = $check->getName();
                    $overallStatus = HealthCheckResult::STATUS_UNHEALTHY;
                }
            }
        }
        
        $totalExecutionTime = microtime(true) - $startTime;
        
        return new HealthReport(
            status: $overallStatus,
            checks: $results,
            criticalFailures: $criticalFailures,
            timestamp: now(),
            totalExecutionTime: $totalExecutionTime
        );
    }
    
    /**
     * Run a specific health check
     */
    public function runCheck(IntegrationHealthCheck $check): HealthCheckResult
    {
        if (!$this->company) {
            throw new \RuntimeException('Company context is required to run health checks');
        }
        
        $cacheKey = $this->getCacheKey($check->getName());
        $cacheTTL = $this->getCacheTTL($check);
        
        return Cache::remember($cacheKey, $cacheTTL, function() use ($check) {
            // Set company context for TenantScope
            app()->instance('current_company_id', $this->company->id);
            
            Log::info('Running health check', [
                'check' => $check->getName(),
                'company_id' => $this->company->id
            ]);
            
            $result = $check->check($this->company);
            
            // Log result
            Log::info('Health check completed', [
                'check' => $check->getName(),
                'company_id' => $this->company->id,
                'status' => $result->status,
                'response_time' => $result->responseTime
            ]);
            
            // Store result for historical tracking
            $this->storeHealthCheckResult($check->getName(), $result);
            
            // Clear company context
            app()->forgetInstance('current_company_id');
            
            return $result;
        });
    }
    
    /**
     * Run check by name
     */
    public function runCheckByName(string $name): ?HealthCheckResult
    {
        $check = collect($this->checks)->first(fn($c) => $c->getName() === $name);
        
        if (!$check) {
            return null;
        }
        
        return $this->runCheck($check);
    }
    
    /**
     * Get all critical checks
     */
    public function getCriticalChecks(): array
    {
        return collect($this->checks)
            ->filter(fn($check) => $check->isCritical())
            ->values()
            ->toArray();
    }
    
    /**
     * Get checks by status
     */
    public function getChecksByStatus(string $status): array
    {
        if (!$this->company) {
            return [];
        }
        
        $report = $this->runAll();
        
        return collect($report->checks)
            ->filter(fn($result) => $result->status === $status)
            ->toArray();
    }
    
    /**
     * Attempt auto-fix for all checks with issues
     */
    public function attemptAutoFix(): array
    {
        if (!$this->company) {
            throw new \RuntimeException('Company context is required for auto-fix');
        }
        
        $fixes = [];
        $report = $this->runAll();
        
        foreach ($this->checks as $check) {
            $result = $report->checks[$check->getName()] ?? null;
            
            if (!$result || $result->isHealthy()) {
                continue;
            }
            
            try {
                $fixed = $check->attemptAutoFix($this->company, $result->issues);
                
                if ($fixed) {
                    $fixes[$check->getName()] = [
                        'success' => true,
                        'message' => 'Auto-fix applied successfully'
                    ];
                    
                    // Clear cache to force re-check
                    Cache::forget($this->getCacheKey($check->getName()));
                }
                
            } catch (\Exception $e) {
                $fixes[$check->getName()] = [
                    'success' => false,
                    'message' => 'Auto-fix failed: ' . $e->getMessage()
                ];
            }
        }
        
        return $fixes;
    }
    
    /**
     * Get suggested fixes for all issues
     */
    public function getSuggestedFixes(): array
    {
        if (!$this->company) {
            return [];
        }
        
        $allFixes = [];
        $report = $this->runAll();
        
        foreach ($this->checks as $check) {
            $result = $report->checks[$check->getName()] ?? null;
            
            if (!$result || $result->isHealthy()) {
                continue;
            }
            
            $fixes = $check->getSuggestedFixes($result->issues);
            
            if (!empty($fixes)) {
                $allFixes[$check->getName()] = $fixes;
            }
        }
        
        return $allFixes;
    }
    
    /**
     * Get health status for admin badge
     */
    public function getHealthStatusForBadge(): array
    {
        if (!$this->company) {
            return [
                'status' => 'unknown',
                'color' => 'gray',
                'icon' => 'heroicon-o-question-mark-circle',
                'text' => '?',
                'tooltip' => 'No company context'
            ];
        }
        
        $cacheKey = "health_badge_{$this->company->id}";
        
        return Cache::remember($cacheKey, 60, function() {
            $report = $this->runAll();
            
            return match($report->status) {
                HealthCheckResult::STATUS_HEALTHY => [
                    'status' => 'healthy',
                    'color' => 'success',
                    'icon' => 'heroicon-o-check-circle',
                    'text' => null, // No badge when healthy
                    'tooltip' => 'All systems operational'
                ],
                HealthCheckResult::STATUS_DEGRADED => [
                    'status' => 'degraded',
                    'color' => 'warning',
                    'icon' => 'heroicon-o-exclamation-triangle',
                    'text' => '⚠️',
                    'tooltip' => 'Some issues detected'
                ],
                HealthCheckResult::STATUS_UNHEALTHY => [
                    'status' => 'unhealthy',
                    'color' => 'danger',
                    'icon' => 'heroicon-o-x-circle',
                    'text' => '❌',
                    'tooltip' => count($report->criticalFailures) . ' critical issues'
                ],
                default => [
                    'status' => 'unknown',
                    'color' => 'gray',
                    'icon' => 'heroicon-o-question-mark-circle',
                    'text' => '?',
                    'tooltip' => 'Unknown status'
                ]
            };
        });
    }
    
    /**
     * Clear all health check caches
     */
    public function clearCache(): void
    {
        if (!$this->company) {
            return;
        }
        
        foreach ($this->checks as $check) {
            Cache::forget($this->getCacheKey($check->getName()));
        }
        
        Cache::forget("health_badge_{$this->company->id}");
    }
    
    /**
     * Get cache key for a check
     */
    protected function getCacheKey(string $checkName): string
    {
        return sprintf(
            'health_check_%s_%s',
            $this->company->id,
            str_replace(' ', '_', strtolower($checkName))
        );
    }
    
    /**
     * Get cache TTL for a check
     */
    protected function getCacheTTL(IntegrationHealthCheck $check): int
    {
        // Critical checks have shorter cache time
        if ($check->isCritical()) {
            return 300; // 5 minutes
        }
        
        return 600; // 10 minutes
    }
    
    /**
     * Store health check result for historical tracking
     */
    protected function storeHealthCheckResult(string $checkName, HealthCheckResult $result): void
    {
        try {
            // Store in database if table exists
            if (\Schema::hasTable('health_check_results')) {
                \DB::table('health_check_results')->insert([
                    'company_id' => $this->company->id,
                    'check_name' => $checkName,
                    'status' => $result->status,
                    'message' => $result->message,
                    'details' => json_encode($result->details),
                    'metrics' => json_encode($result->metrics),
                    'issues' => json_encode($result->issues),
                    'response_time' => $result->responseTime,
                    'created_at' => now(),
                ]);
                
                // Clean up old results (keep last 30 days)
                \DB::table('health_check_results')
                    ->where('company_id', $this->company->id)
                    ->where('created_at', '<', now()->subDays(30))
                    ->delete();
            }
        } catch (\Exception $e) {
            // Don't fail the health check if storage fails
            Log::warning('Failed to store health check result', [
                'error' => $e->getMessage(),
                'check' => $checkName,
                'company_id' => $this->company->id
            ]);
        }
    }
    
    /**
     * Get historical health check results
     */
    public function getHistory(string $checkName, int $days = 7): Collection
    {
        if (!$this->company || !\Schema::hasTable('health_check_results')) {
            return collect();
        }
        
        return \DB::table('health_check_results')
            ->where('company_id', $this->company->id)
            ->where('check_name', $checkName)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($row) {
                $row->details = json_decode($row->details, true);
                $row->metrics = json_decode($row->metrics, true);
                $row->issues = json_decode($row->issues, true);
                return $row;
            });
    }
}